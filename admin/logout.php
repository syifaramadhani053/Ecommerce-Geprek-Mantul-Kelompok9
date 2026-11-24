<?php
/**
 * FILE: admin/logout.php -- 
 */
require_once __DIR__ . '/includes/bootstrap.php';
unset($_SESSION['admin_id'], $_SESSION['admin_nama'], $_SESSION['admin_peran']);
redirect(ADMIN_URL . '/login.php');
