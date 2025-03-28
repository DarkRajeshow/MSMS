<?php
// classes/Medicine.php
require_once '../config/database.php';

class Medicine
{
    private $conn;
    private $table_name = 'medicines';

    public $id;
    public $name;
    public $use;
    public $selling_price;
    public $available_quantity;
    public $expiry_date;
    public $company_id;
    public $disease_id;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " 
                  SET name=?, `use`=?, selling_price=?, available_quantity=?, 
                      expiry_date=?, company_id=?, disease_id=?";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->use = htmlspecialchars(strip_tags($this->use));
        $this->selling_price = htmlspecialchars(strip_tags($this->selling_price));
        $this->available_quantity = htmlspecialchars(strip_tags($this->available_quantity));
        $this->company_id = htmlspecialchars(strip_tags($this->company_id));
        $this->disease_id = htmlspecialchars(strip_tags($this->disease_id));

        $stmt->bind_param(
            "ssdiiii",
            $this->name,
            $this->use,
            $this->selling_price,
            $this->available_quantity,
            $this->expiry_date,
            $this->company_id,
            $this->disease_id
        );

        return $stmt->execute();
    }

    public function read($search = '', $sort_by = 'name', $sort_order = 'ASC')
    {
        $query = "SELECT m.*, c.name as company_name, d.name as disease_name 
                  FROM " . $this->table_name . " m
                  LEFT JOIN companies c ON m.company_id = c.id
                  LEFT JOIN diseases d ON m.disease_id = d.id";

        if (!empty($search)) {
            $search = $this->conn->real_escape_string($search);
            $query .= " WHERE m.name LIKE '%{$search}%' 
                       OR m.`use` LIKE '%{$search}%'
                       OR c.name LIKE '%{$search}%'
                       OR d.name LIKE '%{$search}%'";
        }

        $query .= " ORDER BY m.{$sort_by} {$sort_order}";

        return $this->conn->query($query);
    }

    public function update()
    {
        $query = "UPDATE " . $this->table_name . " 
                  SET name=?, `use`=?, selling_price=?, available_quantity=?, 
                      expiry_date=?, company_id=?, disease_id=?
                  WHERE id=?";

        $stmt = $this->conn->prepare($query);

        // Sanitize
        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->use = htmlspecialchars(strip_tags($this->use));
        $this->selling_price = htmlspecialchars(strip_tags($this->selling_price));
        $this->available_quantity = htmlspecialchars(strip_tags($this->available_quantity));
        $this->company_id = htmlspecialchars(strip_tags($this->company_id));
        $this->disease_id = htmlspecialchars(strip_tags($this->disease_id));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bind_param(
            // "ssdiiiis",
            "ssdisiii",
            $this->name,
            $this->use,
            $this->selling_price,
            $this->available_quantity,
            $this->expiry_date,
            $this->company_id,
            $this->disease_id,
            $this->id
        );

        return $stmt->execute();
    }

    // Read Medicines with optional search and sorting
    // public function read($search = '', $sort_by = 'name', $sort_order = 'ASC')
    // {
    //     $query = "SELECT * FROM " . $this->table_name;

    //     if (!empty($search)) {
    //         $search = $this->conn->real_escape_string($search);
    //         $query .= " WHERE name LIKE '%{$search}%' OR `use` LIKE '%{$search}%'";
    //     }

    //     $query .= " ORDER BY {$sort_by} {$sort_order}";

    //     $result = $this->conn->query($query);
    //     return $result;
    // }

    // Get single medicine details
    public function readOne()
    {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Update Medicine
    // public function update()
    // {
    //     $query = "UPDATE " . $this->table_name . " 
    //               SET name=?, `use`=?, selling_price=?, available_quantity=?, expiry_date=? 
    //               WHERE id=?";

    //     $stmt = $this->conn->prepare($query);

    //     // Sanitize
    //     $this->name = htmlspecialchars(strip_tags($this->name));
    //     $this->use = htmlspecialchars(strip_tags($this->use));
    //     $this->selling_price = htmlspecialchars(strip_tags($this->selling_price));
    //     $this->available_quantity = htmlspecialchars(strip_tags($this->available_quantity));
    //     $this->id = htmlspecialchars(strip_tags($this->id));

    //     // Bind values
    //     $stmt->bind_param(
    //         "ssdisi",
    //         $this->name,
    //         $this->use,
    //         $this->selling_price,
    //         $this->available_quantity,
    //         $this->expiry_date,
    //         $this->id
    //     );

    //     return $stmt->execute() ? true : false;
    // }

    public function delete()
    {
        // First, check if there are any related sales records
        $query = "SELECT COUNT(*) as count FROM sales WHERE medicine_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        // If there are related sales records, proceed with deletion of sales and bill_sales records first
        if ($row['count'] > 0) {
            // First, delete from bill_sales where sale_id refers to the sales records
            $query = "DELETE FROM bill_sales WHERE sale_id IN (SELECT id FROM sales WHERE medicine_id = ?)";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->id);
            $stmt->execute();

            // Then, delete related sales records
            $query = "DELETE FROM sales WHERE medicine_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
        }

        // Now handle related purchases records
        $query = "SELECT COUNT(*) as count FROM purchases WHERE medicine_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            // Delete related purchases records
            $query = "DELETE FROM purchases WHERE medicine_id = ?";
            $stmt = $this->conn->prepare($query);
            $stmt->bind_param("i", $this->id);
            $stmt->execute();
        }

        // Finally, delete the medicine itself
        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();

        return true; // Success
    }




    // Get Low Stock Medicines
    public function getLowStock($threshold = 10)
    {
        $query = "SELECT * FROM " . $this->table_name . " 
                  WHERE available_quantity <= ? 
                  ORDER BY available_quantity ASC";

        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $threshold);
        $stmt->execute();
        return $stmt->get_result();
    }

    // Get Expiring Medicines
    // public function getExpiring($days = 30)
    // {
    //     $query = "SELECT * FROM " . $this->table_name . " 
    //               WHERE expiry_date IS NOT NULL 
    //               AND expiry_date <= DATE_ADD(CURRENT_DATE, INTERVAL ? DAY)
    //               AND available_quantity > 0
    //               ORDER BY expiry_date ASC";

    //     $stmt = $this->conn->prepare($query);
    //     $stmt->bind_param("i", $days);
    //     $stmt->execute();
    //     return $stmt->get_result();
    // }

    // Modify the getExpiring method to exclude expired medicines:
    public function getExpiring($days)
    {
        $query = "SELECT * FROM medicines 
                      WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL $days DAY) 
                      ORDER BY expiry_date ASC";
        return $this->conn->query($query);
    }

    // Get Medicine Statistics
    public function getStatistics()
    {
        $stats = array();

        // Total medicines
        $query = "SELECT COUNT(*) as total FROM " . $this->table_name;
        $result = $this->conn->query($query);
        $stats['total_medicines'] = $result->fetch_assoc()['total'];

        // Out of stock medicines
        $query = "SELECT COUNT(*) as out_of_stock FROM " . $this->table_name . " WHERE available_quantity = 0";
        $result = $this->conn->query($query);
        $stats['out_of_stock'] = $result->fetch_assoc()['out_of_stock'];

        // Total stock value
        $query = "SELECT SUM(available_quantity * selling_price) as total_value FROM " . $this->table_name;
        $result = $this->conn->query($query);
        $stats['total_stock_value'] = $result->fetch_assoc()['total_value'];

        return $stats;
    }

    public function getExpired()
    {
        $query = "SELECT * FROM medicines WHERE expiry_date < CURDATE() ORDER BY expiry_date ASC";
        return $this->conn->query($query);
    }
}
