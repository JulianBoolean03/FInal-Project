<?php
/**
 * SQLite Database connection helper
 * NO MySQL PASSWORD NEEDED!
 */

require_once __DIR__ . '/config.php';

function getDB() {
    static $connection = null;
    
    if ($connection === null) {
        try {
            $dbPath = __DIR__ . '/../reindeer_games.db';
            $connection = new PDO('sqlite:' . $dbPath);
            $connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Create tables if they don't exist
            $schema = file_get_contents(__DIR__ . '/../schema_sqlite.sql');
            if ($schema) {
                $connection->exec($schema);
            }
        } catch (PDOException $e) {
            error_log("Database error: " . $e->getMessage());
            die("Database connection failed.");
        }
    }
    
    return $connection;
}

function executeQuery($query, $types = '', $params = []) {
    $db = getDB();
    
    try {
        $stmt = $db->prepare($query);
        if (!empty($params)) {
            $stmt->execute($params);
        } else {
            $stmt->execute();
        }
        
        // Create mysqli-compatible wrapper
        $wrapper = new class($stmt, $db) {
            private $stmt;
            private $db;
            public $insert_id;
            public $num_rows;
            
            public function __construct($stmt, $db) {
                $this->stmt = $stmt;
                $this->db = $db;
                $this->insert_id = $db->lastInsertId();
                $this->num_rows = $stmt->rowCount();
            }
            
            public function get_result() {
                return $this;
            }
            
            public function fetch_assoc() {
                return $this->stmt->fetch(PDO::FETCH_ASSOC);
            }
            
            public function close() {
                return true;
            }
        };
        
        return $wrapper;
    } catch (PDOException $e) {
        error_log("Query failed: " . $e->getMessage());
        return false;
    }
}

function generateRoomCode($length = 6) {
    $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $code = '';
    $max = strlen($characters) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[random_int(0, $max)];
    }
    
    return $code;
}

function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

function cleanupInactiveRooms() {
    // Optional cleanup function
}
?>
