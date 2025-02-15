<?php
// classes/ExpiryNotification.php
require_once '../config/database.php';

class ExpiryNotification {
    private $conn;
    private $notification_threshold = 30; // Days before expiry to send notification

    public function __construct($db) {
        $this->conn = $db;
    }

    /**
     * Check for medicines nearing expiry and create notifications
     * @return array Array of created notifications
     */
    public function checkExpiringMedicines() {
        $created_notifications = [];
        
        // Get medicines nearing expiry
        $query = "SELECT id, name, expiry_date 
                 FROM medicines 
                 WHERE expiry_date IS NOT NULL 
                 AND expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                 AND available_quantity > 0";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->notification_threshold);
        $stmt->execute();
        $result = $stmt->get_result();

        while ($medicine = $result->fetch_assoc()) {
            // Check if notification already exists
            if (!$this->notificationExists($medicine['id'])) {
                // Create notification
                $notification = $this->createNotification($medicine);
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
     * @return bool True if notification exists, false otherwise
     */
    private function notificationExists($medicine_id) {
        $query = "SELECT id FROM notifications 
                 WHERE medicine_id = ? 
                 AND notified_until >= CURDATE()";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        return $result->num_rows > 0;
    }

    /**
     * Create a new notification for a medicine
     * @param array $medicine Medicine data
     * @return array|false Created notification or false on failure
     */
    private function createNotification($medicine) {
        $message = sprintf(
            "Medicine '%s' will expire on %s (within %d days)",
            $medicine['name'],
            $medicine['expiry_date'],
            $this->notification_threshold
        );

        $notified_until = date('Y-m-d', strtotime($medicine['expiry_date']));

        $query = "INSERT INTO notifications (medicine_id, message, notified_until) 
                 VALUES (?, ?, ?)";
        
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("iss", $medicine['id'], $message, $notified_until);
        
        if ($stmt->execute()) {
            return [
                'id' => $stmt->insert_id,
                'medicine_id' => $medicine['id'],
                'message' => $message,
                'notified_until' => $notified_until
            ];
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
                 ORDER BY n.created_at DESC";
        
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
        $this->notification_threshold = $days;
    }
}