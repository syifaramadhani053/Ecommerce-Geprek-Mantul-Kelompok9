<?php
// FILE INI: LOGOUT -- 
require_once __DIR__ . '/../../config/config.php';
session_destroy();
session_start();
set_flash('success', 'Anda berhasil keluar.');
redirect(BASE_URL . '/index.php');
