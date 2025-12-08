<?php
/**
 * Admin Logout
 */

define('CMS_ROOT', dirname(__DIR__));
require_once CMS_ROOT . '/includes/config.php';
require_once CMS_ROOT . '/includes/user.php';

User::startSession();
User::logout();

header('Location: ' . ADMIN_URL . '/login.php');
exit;
