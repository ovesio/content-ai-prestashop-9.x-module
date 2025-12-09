<?php

// Autoload for OvesioModel
spl_autoload_register(function ($className) {
    if ($className === 'OvesioModel') {
        require_once __DIR__ . '/model/admin/OvesioModel.php';
        return;
    }

    if ($className === 'OvesioQueueModel') {
        require_once __DIR__ . '/model/OvesioQueueModel.php';
        return;
    }
});

// Autoload for Ovesio SDK
require_once __DIR__ . '/lib/sdk/autoload.php';
