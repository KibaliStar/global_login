<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require_once __DIR__ . '/../config/2fa-config.php';
echo "TFA_MODE: " . (defined('TFA_MODE') ? TFA_MODE : 'NOT DEFINED') . "\n";
echo "Current file: " . __FILE__ . "\n";
?>
