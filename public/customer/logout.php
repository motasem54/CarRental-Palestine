<?php
require_once '../../config/settings.php';
session_destroy();
redirect(BASE_URL . '/public/customer/login.php');
?>