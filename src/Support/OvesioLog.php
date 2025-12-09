<?php

namespace PrestaShop\Module\Ovesio\Support;

class OvesioLog {
	private $handle;

	public function __construct() {
        $filename = _PS_ROOT_DIR_ . '/var/logs/ovesio.log';

		$this->handle = fopen($filename, 'a');
	}

	public function write($message) {
		fwrite($this->handle, date('Y-m-d G:i:s') . ' - ' . print_r($message, true) . "\n");
	}

	public function __destruct() {
		fclose($this->handle);
	}
}