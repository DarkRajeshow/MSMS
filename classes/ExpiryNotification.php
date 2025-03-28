<?php
// classes/ExpiryNotification.php
require_once '../config/database.php';

class ExpiryNotification {
    private $conn;
    private $warning_threshold = 30; // Days before expiry to send warning notification

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Check for medicines nearing expiry and expired medicines, create notifications
     * @return array Array of created notifications
     */
    public function checkMedicines() {
        $created_notifications = [];
        
        // Check for medicines nearing expiry (but not expired yet)
        $created_notifications = array_merge(
            $created_notifications,
            $this->checkExpiringMedicines()
        );

        // Check for expired medicines
        $created_notifications = array_merge(
            $created_notifications,
            $this->checkExpiredMedicines()
        );

        return $created_notifications;
    }

    /**
     * Check for medicines nearing expiry and create notifications
     * @return array Array of created notifications
     */
    private function checkExpiringMedicines() {
        $created_notifications = [];
        
        // First warning: 30 days before expiry
        $first_warning = "SELECT id, name, expiry_date 
                 FROM medicines 
                 WHERE expiry_date IS NOT NULL 
                 AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                 AND expiry_date > CURDATE()
                 AND available_quantity > 0";
        
        // Second warning: 7 days before expiry
        $second_warning = "SELECT id, name, expiry_date 
                 FROM medicines 
                 WHERE expiry_date IS NOT NULL 
                 AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                 AND expiry_date > CURDATE()
                 AND available_quantity > 0";

        // Check for first warning (30 days)
        $result = $this->conn->query($first_warning);
        while ($medicine = $result->fetch_assoc()) {
            if (!$this->notificationExists($medicine['id'], 'warning_30')) {
                $medicine['warning_type'] = '30 days';
                $notification = $this->createNotification($medicine, 'warning_30');
                if ($notification) {
                    $created_notifications[] = $notification;
                }
            }
        }

        // Check for second warning (7 days)
        $result = $this->conn->query($second_warning);
        while ($medicine = $result->fetch_assoc()) {
            if (!$this->notificationExists($medicine['id'], 'warning_7')) {
                $medicine['warning_type'] = '7 days';
                $notification = $this->createNotification($medicine, 'warning_7');
                if ($notification) {
                    $created_notifications[] = $notification;
                }
            }
        }

        return $created_notifications;
    }

    /**
     * Check for expired medicines and create notifications
     * @return array Array of created notifications
     */
    private function checkExpiredMedicines() {
        $created_notifications = [];
        
        // Get expired medicines
        $query = "SELECT id, name, expiry_date 
                 FROM medicines 
                 WHERE expiry_date IS NOT NULL 
                 AND expiry_date < CURDATE()
                 AND available_quantity > 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($medicine = $result->fetch_assoc()) {
            // Check if expired notification already exists
            if (!$this->notificationExists($medicine['id'], 'expired')) {
                // Create expired notification
                $notification = $this->createNotification($medicine, 'expired');
                if ($notification) {
                    $created_notifications[] = $notification;
                }
            }
        }

        return $created_notifications;
    }

    /**
     * Check if a notification already exists for the medicine
     * @param int $medicine_id Medicine ID
     * @param string $type Notification type ('warning' or 'expired')
     * @return bool True if notification exists, false otherwise
     */
    private function notificationExists($medicine_id, $type) {
        $query = "SELECT id FROM notifications 
                 WHERE medicine_id = ? 
                 AND notification_type = ?
                 AND status = 'unread'
                 AND expiry_date = (
                     SELECT expiry_date 
                     FROM medicines 
                     WHERE id = ?
                 )";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("isi", $medicine_id, $type, $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    /**
     * Create a new notification for a medicine
     * @param array $medicine Medicine data
     * @param string $type Notification type ('warning_30', 'warning_7' or 'expired')
     * @return array|false Created notification or false on failure
     */
    private function createNotification($medicine, $type) {
        switch ($type) {
            case 'warning_30':
                $message = sprintf(
                    "WARNING: Medicine '%s' will expire on %s (in 30 days)",
                    $medicine['name'],
                    date('Y-m-d', strtotime($medicine['expiry_date']))
                );
                break;
            case 'warning_7':
                $message = sprintf(
                    "URGENT: Medicine '%s' will expire on %s (in 7 days)",
                    $medicine['name'],
                    date('Y-m-d', strtotime($medicine['expiry_date']))
                );
                break;
            case 'expired':
                $message = sprintf(
                    "EXPIRED: Medicine '%s' has expired on %s. Remove from inventory immediately!",
                    $medicine['name'],
                    date('Y-m-d', strtotime($medicine['expiry_date']))
                );
                break;
            default:
                return false;
        }

        $notified_until = date('Y-m-d', strtotime($medicine['expiry_date']));
        $expiry_date = date('Y-m-d', strtotime($medicine['expiry_date']));

        // Check if notification already exists
        $check_query = "SELECT id FROM notifications 
                       WHERE medicine_id = ? 
                       AND notification_type = ? 
                       AND expiry_date = ?
                       AND status = 'unread'";
        
        $check_stmt = $this->conn->prepare($check_query);
        $check_stmt->bind_param("iss", $medicine['id'], $type, $expiry_date);
        $check_stmt->execute();
        $result = $check_stmt->get_result();
        
        if ($result->num_rows > 0) {
            return false; // Notification already exists
        }

        // Insert new notification
        $query = "INSERT INTO notifications 
                 (medicine_id, expiry_date, message, notification_type, notified_until, status) 
                 VALUES (?, ?, ?, ?, ?, 'unread')";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("issss", 
            $medicine['id'], 
            $expiry_date,
            $message, 
            $type, 
            $notified_until
        );
        
        try {
            if ($stmt->execute()) {
                return [
                    'id' => $stmt->insert_id,
                    'medicine_id' => $medicine['id'],
                    'expiry_date' => $expiry_date,
                    'message' => $message,
                    'notification_type' => $type,
                    'notified_until' => $notified_until
                ];
            }
        } catch (mysqli_sql_exception $e) {
            if ($e->getCode() == 1062) { // Duplicate entry error code
                return false;
            }
            throw $e; // Re-throw other SQL exceptions
        }
        
        return false;
    }

    /**
     * Get unread notifications
     * @return array Array of unread notifications
     */
    public function getUnreadNotifications() {
        $query = "SELECT n.*, m.name as medicine_name 
                 FROM notifications n
                 JOIN medicines m ON n.medicine_id = m.id
                 WHERE n.status = 'unread'
                 ORDER BY n.notification_type = 'expired' DESC, n.created_at DESC";
        
        $result = $this->conn->query($query);
        $notifications = [];
        
        while ($row = $result->fetch_assoc()) {
            $notifications[] = $row;
        }
        
        return $notifications;
    }

    /**
     * Mark notification as read
     * @param int $notification_id Notification ID
     * @return bool True if successful, false otherwise
     */
    public function markAsRead($notification_id) {
        $query = "UPDATE notifications 
                 SET status = 'read' 
                 WHERE id = ?";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $notification_id);
        
        return $stmt->execute();
    }

    /**
     * Set notification threshold days
     * @param int $days Number of days before expiry to notify
     */
    public function setNotificationThreshold($days) {
        $this->warning_threshold = $days;
    }

    /**
     * Check a single medicine for expiry or nearing expiry and create notifications
     * @param int $medicine_id Medicine ID
     * @return array|null Created notification or null if no notification created
     */
    public function checkSingleMedicine($medicine_id) {
        $query = "SELECT id, name, expiry_date 
                 FROM medicines 
                 WHERE id = ? AND expiry_date IS NOT NULL";
             
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($medicine = $result->fetch_assoc()) {
            $today = new DateTime();
            $expiry = new DateTime($medicine['expiry_date']);
            $diff = $today->diff($expiry)->days;
            
            if ($expiry < $today && !$this->notificationExists($medicine_id, 'expired')) {
                return $this->createNotification($medicine, 'expired');
            } elseif ($diff <= $this->warning_threshold && !$this->notificationExists($medicine_id, 'warning')) {
                return $this->createNotification($medicine, 'warning');
            }
        }
        return null;
    }
}