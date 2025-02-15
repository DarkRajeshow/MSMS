<?php
// classes/Purchase.php
require_once '../config/database.php';
require_once 'Medicine.php';

class Purchase
{
    private $conn;
    private $table_name = 'purchases';

    public $id;
    public $medicine_id;
    public $quantity;
    public $purchase_price;
    public $purchase_date;
    public $expiry_date;
    public $total_cost;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create() {
        $this->conn->begin_transaction();
    
        try {
            // Validate medicine stock level
            $medicine = $this->getMedicineById($this->medicine_id);
            if (!$medicine) {
                throw new Exception("Medicine not found");
            }
    
            // Insert the purchase record into the purchases table
            $query = "INSERT INTO " . $this->table_name . " 
                      SET medicine_id=?, quantity=?, purchase_price=?, 
                          purchase_date=?, expiry_date=?, total_cost=?";  // Added expiry_date
    
            $stmt = $this->conn->prepare($query);
            $this->total_cost = $this->quantity * $this->purchase_price;
    
            $stmt->bind_param(
                "idsssd",  // Added s for expiry_date
                $this->medicine_id,
                $this->quantity,
                $this->purchase_price,
                $this->purchase_date,
                $this->expiry_date,  // Bound expiry_date
                $this->total_cost
            );
    
            if (!$stmt->execute()) {
                throw new Exception("Could not record purchase");
            }
    
            // Update the expiry date in the medicines table
            $update_medicine_query = "UPDATE medicines 
                                      SET expiry_date = ? 
                                      WHERE id = ?";
            
            $update_stmt = $this->conn->prepare($update_medicine_query);
            $update_stmt->bind_param("si", $this->expiry_date, $this->medicine_id);
    
            if (!$update_stmt->execute()) {
                throw new Exception("Could not update medicine expiry date");
            }
    
            // Update the stock quantity of the medicine
            $update_stock_query = "UPDATE medicines 
                                   SET available_quantity = available_quantity + ? 
                                   WHERE id = ?";
            
            $stock_stmt = $this->conn->prepare($update_stock_query);
            $stock_stmt->bind_param("ii", $this->quantity, $this->medicine_id);
    
            if (!$stock_stmt->execute()) {
                throw new Exception("Could not update medicine stock");
            }
    
            $this->conn->commit();
            return true;
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log($e->getMessage());
            return false;
        }
    }
    



    public function read($page = 1, $limit = 10)
    {
        $offset = ($page - 1) * $limit;

        $query = "SELECT p.*, m.name as medicine_name, m.expiry_date,
                  (SELECT COUNT(*) FROM " . $this->table_name . ") as total_count 
                  FROM " . $this->table_name . " p
                  JOIN medicines m ON p.medicine_id = m.id
                  ORDER BY p.purchase_date DESC
                  LIMIT ?, ?";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $offset, $limit);
        $stmt->execute();
        return $stmt->get_result();
    }

    public function getMedicines()
    {
        $query = "SELECT id, name, expiry_date, available_quantity FROM medicines ORDER BY name ASC";
        $result = $this->conn->query($query);
        return $result;
    }

    public function getMedicineById($id)
    {
        $query = "SELECT * FROM medicines WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        return $stmt->get_result()->fetch_assoc();
    }

    public function getPurchaseStats()
    {
        $query = "SELECT 
                    COUNT(*) as total_purchases,
                    SUM(total_cost) as total_spent,
                    AVG(total_cost) as avg_purchase,
                    MAX(total_cost) as max_purchase
                  FROM " . $this->table_name;
        $result = $this->conn->query($query);
        return $result->fetch_assoc();
    }

    public function getRecentPurchases($limit = 5)
    {
        $query = "SELECT p.*, m.name as medicine_name
                  FROM " . $this->table_name . " p
                  JOIN medicines m ON p.medicine_id = m.id
                  ORDER BY p.purchase_date DESC
                  LIMIT ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $limit);
        $stmt->execute();
        return $stmt->get_result();
    }
}
