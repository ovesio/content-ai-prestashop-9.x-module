<?php

use Ovesio\OvesioAI;
use Ovesio\QueueHandler;
use PrestaShop\Module\Ovesio\Support\OvesioConfiguration;

class OvesioCallbackModuleFrontController extends ModuleFrontController
{
    private $output = [];
    private $module_key = 'ovesio';
    private $config;
    private $queue_handler;
    private $model;

    public function __construct()
    {
        parent::__construct();
        require_once _PS_MODULE_DIR_ . 'ovesio/model/admin/OvesioModel.php';
        require_once _PS_MODULE_DIR_ . 'ovesio/vendor/autoload.php';

        $this->config = OvesioConfiguration::getAll('ovesio');

        $ovesio = \Module::getInstanceByName('ovesio');

        /**
         * @var QueueHandler
         */
        $this->queue_handler = $ovesio->buildQueueHandler();
        $this->model = $this->queue_handler->getModel();
    }

    public function initContent()
    {
        // No template needed - JSON response only
        $this->ajax = true;
    }

    public function postProcess()
    {
        if (!$this->config->get($this->module_key . '_status')) {
            return $this->setOutput(['error' => 'Module is disabled']);
        }

        $hash = Tools::getValue('hash');
        if (!$hash || $hash !== $this->config->get($this->module_key . '_hash')) {
            return $this->setOutput(['error' => 'Invalid Hash!']);
        }

        $action = Tools::getValue('action');
        if ($action == 'updateActivityStatus') {
            return $this->updateActivityStatus();
        }

        // Takes raw data from the request
        $json = file_get_contents('php://input');
        $data = json_decode($json, true);

        try {
            $output = $this->handle($data);
        } catch (\Exception $e) {
            $this->setOutput(array_merge($this->output, [
                'error' => $e->getMessage()
            ]));
        }

        $this->setOutput($output);
    }

    protected function handle($data)
    {
        $activity_type = Tools::getValue('type');
        $manual        = Tools::getValue('manual');

        if (!$this->config->get($this->module_key . '_status')) {
            return $this->setOutput(['error' => 'Module is disabled']);
        }

        if (empty($data)) {
            throw new Exception('No data received');
        }

        if (empty($data['content'])) {
            throw new Exception('Data received has empty content');
        }

        list($resource, $identifier) = explode('/', $data['ref']);
        $ovesio_language_code = $data['to'];

        $status = 0;
        if (!$activity_type) {
            throw new Exception('Wrong request');
        }

        if ($activity_type == 'generate_content') {
            $status = $this->config->get($this->module_key . '_generate_content_status');
        } elseif ($activity_type == 'generate_seo' || $activity_type == 'metatags') {
            $status = $this->config->get($this->module_key . '_generate_seo_status');
        } elseif ($activity_type == 'translate') {
            $status = $this->config->get($this->module_key . '_translate_status');
        }

        if (empty($activity_type)) {
            throw new Exception('Data received has empty type');
        }

        if (in_array($resource, ['product', 'category', 'attribute_group', 'feature']) && !$status) {
            return $this->setOutput(['error' => 'This operation is disabled!']);
        }

        if (empty($identifier)) {
            throw new Exception('Identifier cannot be empty');
        }

        $language_id  = $this->queue_handler->getDefaultLanguageId();

        $language_settings = $this->config->get($this->module_key . '_language_settings');
        foreach ($language_settings as $match_language_id => $lang) {
            if (!empty($lang['code']) && $lang['code'] == $ovesio_language_code) {
                $language_id = $match_language_id;
                break;
            }
        }

        $local_lang = new \Language($language_id);

        if (!$local_lang->id) {
            throw new Exception('Language id "' . $ovesio_language_code . '" not found');
        }

        $data['language_id'] = $language_id;

        try {
            if ($activity_type == 'generate_content') {
                $this->generateDescription($resource, $identifier, $data);
            } elseif ($activity_type == 'generate_seo' || $activity_type == 'metatags') {
                $activity_type = 'generate_seo'; // backwords compatibility
                $this->generateSeo($resource, $identifier, $data);
            } elseif ($activity_type == 'translate') {
                $this->translate($resource, $identifier, $data);
            } else {
                throw new Exception('Activity of type "' . $activity_type . '" could not be handled');
            }

        } catch (Throwable $e) {
            // stop updating list
            throw new Exception('Error processing ' . $activity_type . ': ' . $e->getMessage() . ' ' . $e->getFile() . ':' . $e->getLine());
        }

        // Update log table
        list($resource, $resource_id) = explode('/', $data['ref']);

        $this->model->addList([
            'resource_type' => $resource,
            'resource_id'   => $resource_id,
            'activity_type' => $activity_type,
            'lang'          => $ovesio_language_code,
            'status'        => 'completed',
            'response'      => json_encode($data['content']),
        ]);

        if (!defined('OVESIO_CALLBACK_QUEUE_PROCESSING') || !OVESIO_CALLBACK_QUEUE_PROCESSING) {
            if (!$manual) { // daca nu a fost manual facut request-ul
                $this->queue_handler->processQueue([
                    'resource_type' => $resource,
                    'resource_id'   => $resource_id,
                    'from_callback' => true,
                ]);
            }
        }

        $output = array_merge($this->output, [
            'success' => true,
            'queue'   => $this->queue_handler->getDebug(),
            'manual'  => $manual,
        ]);

        return $output;
    }

    protected function generateDescription($resource, $identifier, $data)
    {
        if ($resource == 'product') {
            $this->generateDescriptionProduct($identifier, $data);
        } elseif ($resource == 'category') {
            $this->generateDescriptionCategory($identifier, $data);
        } else {
            throw new Exception('Resource of type "' . $resource . '" could not be handled for description generation');
        }
    }

    protected function generateDescriptionProduct($product_id, $data)
    {
        $product_description['description'] = $data['content']['description'];

        $this->model->updateProductDescription($product_id, $data['language_id'], $product_description);

        $this->seoProduct($product_id, $data['language_id'], $product_description);
    }

    protected function generateDescriptionCategory($category_id, $data)
    {
        $category_description['description'] = $data['content']['description'];

        $this->model->updateCategoryDescription($category_id, $data['language_id'], $category_description);

        $this->seoCategory($category_id, $data['language_id'], $category_description);
    }

    protected function generateSeo($resource, $identifier, $data)
    {
        if ($resource == 'product') {
            $this->metatagsProduct($identifier, $data);
        } elseif ($resource == 'category') {
            $this->metatagsCategory($identifier, $data);
        } else {
            throw new Exception('Resource of type "' . $resource . '" could not be handled for SEO generation');
        }
    }

    protected function metatagsProduct($product_id, $data)
    {
        //seo_h1, seo_h2, seo_h3, meta_title, meta_description, meta_keywords

        $language_id = $data['language_id'];
        $seo = $this->model->getProductForSeo($product_id, $language_id);
        $content = $this->populateCompatibilityContent($data['content']);

        $metatags = [];
        foreach ($content as $key => $value) {
            if (isset($seo['product_description'][$language_id][$key])) {
                $metatags[$key] = $value;
            }
        }

        $this->model->updateProductDescription($product_id, $language_id, $metatags);
    }

    protected function metatagsCategory($category_id, $data)
    {
        //seo_h1, seo_h2, seo_h3, meta_title, meta_description, meta_keywords

        $language_id = $data['language_id'];
        $seo = $this->model->getCategoryForSeo($category_id, $language_id);
        $content = $this->populateCompatibilityContent($data['content']);

        $metatags = [];
        foreach ($content as $key => $value) {
            if (isset($seo['category_description'][$language_id][$key])) {
                $metatags[$key] = $value;
            }
        }

        $this->model->updateCategoryDescription($category_id, $language_id, $metatags);
    }

    protected function translate($resource, $identifier, $data)
    {
        if ($resource == 'product') {
            $this->translateProduct($identifier, $data);
        } elseif ($resource == 'category') {
            $this->translateCategory($identifier, $data);
        } elseif ($resource == 'attribute_group') {
            $this->translateAttributeGroup($identifier, $data);
        } elseif ($resource == 'feature') {
            $this->translateFeature($identifier, $data);
        } else {
            throw new Exception('Resource of type "' . $resource . '" could not be handled for translation');
        }
    }

    protected function translateProduct($product_id, $data)
    {
        $translate_fields = $this->config->get($this->module_key . '_translate_fields');
        $translate_fields = array_filter($translate_fields['products']);

        $product_description = [];
        $attribute_values = [];

        foreach ($data['content'] as $item) {
            // ? if order matters
            if (strpos($item['key'], 'a-') === 0) {
                $attribute_values[str_replace('a-', '', $item['key'])] = $item['value'];
            } elseif (!empty($translate_fields[$item['key']])) {
                $product_description[str_replace('p-', '', $item['key'])] = $item['value'];
            } elseif (!isset($translate_fields[$item['key']])) {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }

        if (!empty($product_description)) {
            $this->model->updateProductDescription($product_id, $data['language_id'], $product_description);
        }

        foreach ($attribute_values as $attribute_id => $text) {
            $this->model->updateAttributeValueDescription($product_id, $attribute_id, $data['language_id'], $text);
        }

        if (!empty($product_description)) {
            $this->seoProduct($product_id, $data['language_id'], $product_description);
        }
    }

    protected function translateCategory($category_id, $data)
    {
        $translate_fields = $this->config->get($this->module_key . '_translate_fields');
        $translate_fields = array_filter($translate_fields['categories']);

        $category_description = [];

        foreach ($data['content'] as $item) {
            if (!empty($translate_fields[$item['key']])) {
                $category_description[$item['key']] = $item['value'];
            } elseif (!isset($translate_fields[$item['key']])) {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }

        if (!empty($category_description)) {
            $this->model->updateCategoryDescription($category_id, $data['language_id'], $category_description);

            $this->seoCategory($category_id, $data['language_id'], $category_description);
        }
    }

    protected function translateAttributeGroup($attribute_group_id, $data)
    {
        foreach ($data['content'] as $item) {
            if (strpos($item['key'], 'ag-') === 0) {
                $attribute_group_id = str_replace('ag-', '', $item['key']);
                $this->model->updateAttributeGroupDescription($attribute_group_id, $data['language_id'], $item['value']);
            } elseif (strpos($item['key'], 'a-') === 0) {
                $attribute_id = str_replace('a-', '', $item['key']);
                $this->model->updateAttributeDescription($attribute_id, $data['language_id'], $item['value']);
            } else {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }
    }

    protected function translateFeature($feature_id, $data)
    {
        foreach ($data['content'] as $item) {
            if (strpos($item['key'], 'o-') === 0) {
                $feature_id = str_replace('o-', '', $item['key']);
                $this->model->updateFeatureDescription($feature_id, $data['language_id'], $item['value']);
            } elseif (strpos($item['key'], 'ov-') === 0) {
                $feature_value_id = str_replace('ov-', '', $item['key']);
                $this->model->updateFeatureValueDescription($feature_value_id, $data['language_id'], $item['value']);
            } else {
                $this->output['warnings'][] = 'Unknown key "' . $item['key'] . '"';
            }
        }
    }

    protected function populateCompatibilityContent($content)
    {
        return $content; // extra fields

        // General Mappings
        $content['meta_keyword'] = $content['meta_keywords'];

        // Complete SEO module mappings
        $content['image_title'] = $content['seo_h1'];
        $content['image_alt'] = $content['seo_h2'];
        $content['seo_keyword'] = $content['meta_keywords'];

        // SEO Mega KIT PLUS mappings
        //$content['meta_title_ag'] = $data['meta_title'];
        $content['smp_h1_title'] = $content['seo_h1'];
        $content['smp_alt_images'] = $content['seo_h1'];
        $content['smp_title_images'] = $content['seo_h2'];

        return $content;
    }

    /**
     * Internal SEO methods - compatible with Complete SEO module
     *
     */
    private function seoProduct($product_id, $language_id, $product_description)
    {

    }

    private function seoCategory($category_id, $language_id, $category_description)
    {

    }

    /**
     * Called from activity list, by refresh click
     */
    public function updateActivityStatus()
    {
        $hash        = Tools::getValue('hash');
        $activity_id = Tools::getValue('activity_id');

        if (!$hash || $hash !== $this->config->get($this->module_key . '_hash')) {
            return $this->setOutput(['error' => 'Invalid Hash!']);
        }

        $activity = $this->model->getActivityById($activity_id);

        $payload = null;

        $api_url   = $this->config->get($this->module_key . '_api_url');
        $api_token = $this->config->get($this->module_key . '_api_token');

        $api = new OvesioAI($api_token, $api_url);

        try {
            if ($activity['activity_type'] == 'generate_content') {
                $response = $api->generateDescription()->status($activity['activity_id']);
                $payload = isset($response->data) ? $response->data : null;
            } elseif ($activity['activity_type'] == 'generate_seo') {
                $response = $api->generateSeo()->status($activity['activity_id']);
                $payload = isset($response->data) ? $response->data : null;
            } elseif ($activity['activity_type'] == 'translate') {
                $response = $api->translate()->status($activity['activity_id']);

                if ($response->success) {
                    foreach ($response->data->data as $item) {
                        if ($item->to == $activity['lang']) {
                            $payload = $item;
                            $payload->ref = $response->data->ref;
                            break;
                        }
                    }
                }
            }
        } catch (Throwable $e) {}

        if (empty($payload)) {
            return $this->setOutput(json_encode([
                'success' => false,
                'error'   => $this->module->l('error_fetching_status')
            ]));
        }

        $payload = json_decode(json_encode($payload), true);

        if ($payload && $payload['status'] == 'completed') {
            // set data on php://input then call handle
            $_GET['type']   = $activity['activity_type'];
            $_GET['manual'] = true;

            $payload['to'] = $payload['to'] ?: 'auto';
            $payload['content'] = $payload['content'] ?? $payload['data'];
            unset($payload['data']);

            $this->handle($payload);
        }

        $activity = $this->model->getActivityById($activity_id);

        $status_types = [
            'started'   => ['text' => $this->module->l('text_processing'), 'class' => 'ov-status-info'],
            'completed' => ['text' => $this->module->l('text_completed'), 'class' => 'ov-status-success'],
            'error'     => ['text' => $this->module->l('text_error'), 'class' => 'ov-status-danger']
        ];

        $status_display = $status_types[$activity['status']];

        return $this->setOutput([
            'success'        => true,
            'status'         => $activity['status'],
            'status_display' => $status_display,
            'updated_at'     => $activity['updated_at']
        ]);
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

        echo $response;

        exit();
    }
}
