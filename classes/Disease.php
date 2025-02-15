<?php
// classes/Company.php
require_once '../config/database.php';

class Disease
{
    private $conn;
    private $table_name = 'diseases';

    public $id;
    public $name;
    public $description;

    public function __construct($db)
    {
        $this->conn = $db;
    }

    public function create()
    {
        $query = "INSERT INTO " . $this->table_name . " (name, description) VALUES (?, ?)";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));

        $stmt->bind_param("ss", $this->name, $this->description);
        return $stmt->execute();
    }

    public function read($search = '', $sort_by = 'name', $sort_order = 'ASC')
    {
        $query = "SELECT * FROM " . $this->table_name;

        if (!empty($search)) {
            $search = $this->conn->real_escape_string($search);
            $query .= " WHERE name LIKE '%{$search}%' OR description LIKE '%{$search}%'";
        }

        $query .= " ORDER BY {$sort_by} {$sort_order}";
        return $this->conn->query($query);
    }

    public function update()
    {
        $query = "UPDATE " . $this->table_name . " SET name=?, description=? WHERE id=?";
        $stmt = $this->conn->prepare($query);

        $this->name = htmlspecialchars(strip_tags($this->name));
        $this->description = htmlspecialchars(strip_tags($this->description));
        $this->id = htmlspecialchars(strip_tags($this->id));

        $stmt->bind_param("ssi", $this->name, $this->description, $this->id);
        return $stmt->execute();
    }

    public function delete()
    {
        // First check if any medicines are using this disease
        $query = "SELECT COUNT(*) as count FROM medicines WHERE disease_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();

        if ($row['count'] > 0) {
            return false; // Cannot delete disease with associated medicines
        }

        $query = "DELETE FROM " . $this->table_name . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $this->id);
        return $stmt->execute();
    }
}

?>