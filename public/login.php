<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new AuthController();
    $controller->login();
    exit;
}

$controller = new AuthController();
$controller->loginForm();
