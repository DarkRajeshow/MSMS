<?php
// classes/Sale.php
require_once '../config/database.php';

class Sale
{
    private $conn;
    private $table_name = 'sales';

    public function __construct($db)
    {
        $this->conn = $db;
    }

    // Create Sale with enhanced error handling and validation
    public function create($sales_items, $customer_name)
    {
        $this->conn->begin_transaction();

        try {
            $total_bill_amount = 0;
            $sale_ids = [];
            $sale_date = date('Y-m-d');

            // Enhanced prepared statements with better error handling
            $sales_query = "INSERT INTO sales (medicine_id, quantity_sold, sale_price, sale_date) 
                           VALUES (?, ?, (SELECT selling_price FROM medicines WHERE id = ?), ?)";
            $sales_stmt = $this->conn->prepare($sales_query);

            $stock_query = "UPDATE medicines 
                           SET available_quantity = available_quantity - ? 
                           WHERE id = ? AND available_quantity >= ?";
            $stock_stmt = $this->conn->prepare($stock_query);

            // Enhanced validation and processing for each sale item
            foreach ($sales_items as $item) {
                $medicine_id = $item['medicine_id'];
                $quantity = $item['quantity'];

                // Validate stock availability first
                $stock_check = $this->conn->query("SELECT available_quantity FROM medicines WHERE id = $medicine_id");
                $stock_data = $stock_check->fetch_assoc();

                if ($stock_data['available_quantity'] < $quantity) {
                    throw new Exception("Insufficient stock for medicine ID: $medicine_id. Available: {$stock_data['available_quantity']}");
                }

                // Create sale record with enhanced error handling
                $sales_stmt->bind_param("iiis", $medicine_id, $quantity, $medicine_id, $sale_date);
                if (!$sales_stmt->execute()) {
                    throw new Exception("Failed to record sale for medicine ID: $medicine_id");
                }
                $sale_ids[] = $this->conn->insert_id;

                // Update stock with validation
                $stock_stmt->bind_param("iii", $quantity, $medicine_id, $quantity);
                if (!$stock_stmt->execute() || $stock_stmt->affected_rows == 0) {
                    throw new Exception("Stock update failed for medicine ID: $medicine_id");
                }

                // Calculate total with enhanced precision
                $price_query = "SELECT sale_price FROM sales WHERE id = LAST_INSERT_ID()";
                $price_result = $this->conn->query($price_query);
                $price_row = $price_result->fetch_assoc();
                $total_bill_amount += $quantity * $price_row['sale_price'];
            }

            // Create bill with customer name
            $bill_query = "INSERT INTO bills (total_amount, bill_date, customer_name) VALUES (?, ?, ?)";
            $bill_stmt = $this->conn->prepare($bill_query);
            $bill_stmt->bind_param("dss", $total_bill_amount, $sale_date, $customer_name);

            if (!$bill_stmt->execute()) {
                throw new Exception("Bill creation failed");
            }
            $bill_id = $this->conn->insert_id;

            // Link sales to bill with enhanced error handling
            $bill_sales_query = "INSERT INTO bill_sales (bill_id, sale_id) VALUES (?, ?)";
            $bill_sales_stmt = $this->conn->prepare($bill_sales_query);

            foreach ($sale_ids as $sale_id) {
                $bill_sales_stmt->bind_param("ii", $bill_id, $sale_id);
                if (!$bill_sales_stmt->execute()) {
                    throw new Exception("Failed to link sale ID: $sale_id to bill ID: $bill_id");
                }
            }

            $this->conn->commit();
            return [
                'success' => true,
                'bill_id' => $bill_id,
                'total_amount' => $total_bill_amount,
                'items_count' => count($sale_ids),
                'customer_name' => $customer_name
            ];
        } catch (Exception $e) {
            $this->conn->rollback();
            error_log($e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    // Enhanced getBillDetails with additional information
    public function getBillDetails($bill_id)
    {
        $query = "SELECT b.id as bill_id, b.bill_date, b.total_amount, b.customer_name,
                         s.id as sale_id, s.quantity_sold as quantity, s.sale_price as price,
                         m.name as medicine_name, m.use as medicine_use
                  FROM bills b
                  JOIN bill_sales bs ON b.id = bs.bill_id
                  JOIN sales s ON bs.sale_id = s.id
                  JOIN medicines m ON s.medicine_id = m.id
                  WHERE b.id = ?";

        try {
            $stmt = $this->conn->prepare($query);
            if ($stmt === false) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }

            $stmt->bind_param("i", $bill_id);

            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }

            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                return null;
            }

            $items = [];
            $bill_details = null;

            while ($row = $result->fetch_assoc()) {
                if ($bill_details === null) {
                    $bill_details = [
                        'bill_id' => $row['bill_id'],
                        'formatted_date' => date('M j, Y', strtotime($row['bill_date'])),
                        'total_amount' => $row['total_amount'],
                        'customer_name' => $row['customer_name'],
                        'items' => []
                    ];
                }

                $items[] = [
                    'medicine_name' => $row['medicine_name'],
                    'medicine_use' => $row['medicine_use'],
                    'quantity' => $row['quantity'],
                    'price' => $row['price']
                ];
            }

            $bill_details['items'] = $items;
            return $bill_details;
        } catch (Exception $e) {
            error_log("Error in getBillDetails: " . $e->getMessage());
            return null;
        }
    }

    public function getSalesHistory($filters = [])
    {
        $where_conditions = [];
        $params = [];
        $types = "";

        if (isset($filters['date_from']) && !empty($filters['date_from'])) {
            $where_conditions[] = "DATE(b.bill_date) >= ?";
            $params[] = $filters['date_from'];
            $types .= "s";
        }

        if (isset($filters['date_to']) && !empty($filters['date_to'])) {
            $where_conditions[] = "DATE(b.bill_date) <= ?";
            $params[] = $filters['date_to'];
            $types .= "s";
        }

        $where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

        $query = "SELECT s.*, m.name as medicine_name, b.id as bill_id, 
                         b.total_amount as bill_total, b.bill_date,
                         b.customer_name, -- Added customer_name
                         COUNT(bs2.sale_id) as items_in_bill
                  FROM sales s
                  JOIN medicines m ON s.medicine_id = m.id
                  JOIN bill_sales bs ON s.id = bs.sale_id
                  JOIN bills b ON bs.bill_id = b.id
                  LEFT JOIN bill_sales bs2 ON b.id = bs2.bill_id
                  $where_clause
                  GROUP BY b.id, s.id
                  ORDER BY b.bill_date DESC, b.id DESC";

        try {
            if (!empty($params)) {
                $stmt = $this->conn->prepare($query);
                if ($stmt === false) {
                    throw new Exception("Prepare failed: " . $this->conn->error);
                }
                $stmt->bind_param($types, ...$params);
                if (!$stmt->execute()) {
                    throw new Exception("Execute failed: " . $stmt->error);
                }
                $result = $stmt->get_result();
            } else {
                $result = $this->conn->query($query);
                if ($result === false) {
                    throw new Exception("Query failed: " . $this->conn->error);
                }
            }
            return $result;
        } catch (Exception $e) {
            error_log("Database error: " . $e->getMessage());
            return $this->conn->query("SELECT * FROM sales WHERE 1=0");
        }
    }



    // Enhanced getMedicinesForSale with additional information
    public function getMedicinesForSale()
    {
        $query = "SELECT `id`, `name`, `use`, `selling_price`, `expiry_date`, `available_quantity`, 
                         CASE 
                             WHEN available_quantity <= 10 THEN 'low'
                             WHEN available_quantity <= 30 THEN 'medium'
                             ELSE 'high'
                         END as stock_status
                  FROM medicines 
                  WHERE available_quantity > 0
                  ORDER BY name ASC";
        $result = $this->conn->query($query);
        return $result;
    }
}
