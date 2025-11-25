<?php
require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../db/db_connection.php';

$auth = new AuthController($conn);
$auth->logout();
header('Location: ../../src/telas/login/index.php');
exit();
?>
