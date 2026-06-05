<?php
require_once 'src/db/db_connection.php';

try {
    echo "Connected successfully\n";
    
    // Check if table exists
    $tables = ['funcionario'];
    foreach ($tables as $table) {
        $stmt = $conn->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "Table '$table' exists.\n";
            
            // Show columns
            $stmt = $conn->query("DESCRIBE $table");
            echo "Columns in '$table':\n";
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                print_r($row);
            }

            // Check for users
            $stmt = $conn->query("SELECT * FROM $table");
            $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (count($users) > 0) {
                echo "Found " . count($users) . " users in '$table':\n";
                foreach ($users as $user) {
                    print_r($user);
                }
            } else {
                echo "No users found in '$table' table.\n";
            }
        } else {
            echo "Table '$table' DOES NOT EXIST.\n";
        }
    }
    
    // List all tables
    $stmt = $conn->query("SHOW TABLES");
    echo "Tables in DB:\n";
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
        echo $row[0] . "\n";
    }
    
} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
