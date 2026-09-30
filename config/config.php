<?php
/**
 * =====================================================================
 * FILE INI: KONFIGURASI SITUS
 * =====================================================================
 */
if (session_status() === PHP_SESSION_NONE) session_start();

define('BASE_URL', '/geprek-mantul-erp');
define('SITE_NAME', 'Geprek Mantul');
define('WHATSAPP_ADMIN', '6281234567890');
define('ONGKIR_DEFAULT', 8000);

date_default_timezone_set('Asia/Jakarta');

require_once __DIR__ . '/database.php';
require_once __DIR__ . '/../includes/functions.php';
