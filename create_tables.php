<?php
require_once 'src/db/db_connection.php';

try {
    // Create funcionario table
    $sql = "CREATE TABLE IF NOT EXISTS funcionario (
        cod_funcionario INT AUTO_INCREMENT PRIMARY KEY,
        login_funcionario VARCHAR(50) NOT NULL,
        senha VARCHAR(255) NOT NULL,
        nome VARCHAR(100) NOT NULL,
        email VARCHAR(255) NOT NULL,
        tipo VARCHAR(20) DEFAULT 'user'
    )";
    
    $conn->exec($sql);
    echo "Table 'funcionario' created successfully.\n";

    // Check if admin exists
    $stmt = $conn->prepare("SELECT * FROM funcionario WHERE email = ?");
    $stmt->execute(['admin@chemicall.com']);
    
    if ($stmt->rowCount() == 0) {
        // Insert admin user
        $sql = "INSERT INTO funcionario (login_funcionario, senha, nome, email, tipo) VALUES (?, ?, ?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute(['admin', $password, 'Administrador', 'admin@chemicall.com', 'admin']);
        echo "Admin user created successfully.\n";
    } else {
        echo "Admin user already exists.\n";
        // Update password just in case
        $sql = "UPDATE funcionario SET senha = ? WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $password = password_hash('admin123', PASSWORD_DEFAULT);
        $stmt->execute([$password, 'admin@chemicall.com']);
        echo "Admin password updated.\n";
    }

} catch(PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
