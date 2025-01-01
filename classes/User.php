<?php
class User {
    private $conn;
    private $table_name = "users";

    public $id;
    public $username;
    public $password;
    public $email;
    public $full_name;
    public $role;
    public $created_at;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function login($username, $password) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE username = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->execute([$username]);
        
        if($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            if(password_verify($password, $row['password'])) {
                foreach($row as $key => $value) {
                    if(property_exists($this, $key)) {
                        $this->$key = $value;
                    }
                }
                return true;
            }
        }
        return false;
    }

    public function register($data) {
        try {
            $query = "INSERT INTO " . $this->table_name . " 
                    (username, password, email, full_name, role) 
                    VALUES (:username, :password, :email, :full_name, :role)";
            
            $stmt = $this->conn->prepare($query);
            
            $password_hash = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Bind parameters
            $params = [
                ':username' => $data['username'],
                ':password' => $password_hash,
                ':email' => $data['email'],
                ':full_name' => $data['full_name'],
                ':role' => 'member'
            ];
            
            $result = $stmt->execute($params);
            
            if (!$result) {
                error_log("Registration failed. Error info: " . print_r($stmt->errorInfo(), true));
                return false;
            }
            
            return true;
            
        } catch (PDOException $e) {
            error_log("Registration error: " . $e->getMessage());
            throw $e;
        }
    }
}
?>
