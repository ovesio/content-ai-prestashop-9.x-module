<?php

use PrestaShop\Module\Ovesio\Support\OvesioConfiguration;

class OvesioCronjobModuleFrontController extends ModuleFrontController
{
    private $module_key = 'ovesio';
    private $config;

    /**
     * Module instance
     *
     * @var Ovesio
     */
    private $ovesio;

    public function __construct()
    {
        parent::__construct();
        require_once _PS_MODULE_DIR_ . 'ovesio/vendor/autoload.php';

        $this->config = OvesioConfiguration::getAll('ovesio');
    }

    public function postProcess()
    {
        $hash = Tools::getValue('hash');
        $stored_hash = $this->config->get($this->module_key . '_hash');

        // Verify hash
        if (empty($hash) || $hash !== $stored_hash) {
            return $this->setOutput(['error' => 'Invalid hash']);
        }

        $this->index();
    }

    private function index()
    {
        if (!$this->config->get($this->module_key . '_status')) {
            return $this->setOutput(['error' => 'Module is disabled']);
        }

        $query = Tools::getAllValues();

        $resource_type = !empty($query['resource_type']) ? $query['resource_type'] : null;
        $resource_id   = !empty($query['resource_id']) ? (int) $query['resource_id'] : null;
        $limit         = !empty($query['limit']) ? (int) $query['limit'] : 20;

        $status = 0;
        $status += (bool) $this->config->get($this->module_key . '_generate_content_status');
        $status += (bool) $this->config->get($this->module_key . '_generate_seo_status');
        $status += (bool) $this->config->get($this->module_key . '_translate_status');

        if ($status == 0) {
            return $this->setOutput(['error' => 'All operations are disabled']);
        }

        $this->ovesio = \Module::getInstanceByName('ovesio');

        /**
         * @var QueueHandler
         */
        $queue_handler = $this->ovesio->buildQueueHandler();

        $list = $queue_handler->processQueue([
            'resource_type' => $resource_type,
            'resource_id'   => $resource_id,
            'limit'         => $limit,
        ]);

        $queue_handler->showDebug();

        echo "Entries found: " . count($list);

        exit();
    }

    /**
     * Custom response
     */
    private function setOutput($response)
    {
        if (is_array($response)) {
            $response = json_encode($response);

            header('Content-Type: application/json');
        }

        return json_encode($response);
    }
}
