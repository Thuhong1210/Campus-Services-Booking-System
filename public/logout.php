<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/config/config.php';

$controller = new AuthController();
$controller->logout();
