<?php

declare(strict_types=1);
require_once __DIR__ . '/vendor/autoload.php';
require __DIR__ . '/app/core.php';
require __DIR__ . '/app/two_factor.php';
require __DIR__ . '/app/auth.php';

load_env(APP_ROOT . '/.env');
init_storage();

$page = $_GET['page'] ?? (is_logged_in() ? 'dashboard' : 'login');

switch ($page) {
    case 'register':
        register_page();
        break;
    case 'verify_totp':
        verify_totp_page();
        break;
    case 'verify_sms':
        verify_sms_page();
        break;
    case 'verify_hardware':
        verify_hardware_page();
        break;
    case 'dashboard':
        dashboard_page();
        break;
    case 'logout':
        logout_user();
        break;
    case 'login':
    default:
        login_page();
        break;
}
