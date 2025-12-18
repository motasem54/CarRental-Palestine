<?php
require_once '../config/settings.php';
require_once '../core/Auth.php';

$auth = new Auth();
$auth->logout();

header('Location: login.php');
exit;
?>