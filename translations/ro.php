<?php

global $_MODULE;
$_MODULE = [];

// Load OpenCart-style language file
$_ = [];
$langFile = dirname(__DIR__) . '/languages/ro/ovesio.php';
if (file_exists($langFile)) {
    $_ = include $langFile;
}

// Convert to PrestaShop format
foreach ($_ as $key => $value) {
    $_MODULE['<{ovesio}prestashop>ovesio_' . $key] = $value;
}