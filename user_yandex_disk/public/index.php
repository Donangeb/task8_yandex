<?php
session_start();

if (!isset($_SESSION['access_token'])) {
    // Редиректим на авторизацию
    header("Location: http://localhost");
    exit;
}

require_once __DIR__ . '/../../vendor/autoload.php';

use App\Controller\DiskController;

$controller = new DiskController();
$controller->handleRequest();