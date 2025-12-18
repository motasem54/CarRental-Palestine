<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
$auth->logout();

redirect(ADMIN_URL . '/login.php');
?>