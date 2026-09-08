<?php
require_once __DIR__ . '/../controllers/AuthController.php';

$auth = new AuthController($conn);
$auth->logout();

header('Location: ../telas/login/index.php');
exit();
