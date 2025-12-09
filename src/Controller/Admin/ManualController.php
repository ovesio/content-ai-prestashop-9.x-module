<?php

namespace PrestaShop\Module\Ovesio\Controller\Admin;

use Configuration;
use PrestaShop\Module\Ovesio\Support\OvesioConfiguration;
use PrestaShop\Module\Ovesio\Support\TplSupport;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Tools;

class ManualController extends PrestaShopAdminController
{
    public const TAB_CLASS_NAME = 'AdminOvesioManual';

    /**
     * Module instance resolved by PrestaShop
     *
     * @var Ovesio
     */
    public $module;

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('ovesio');
    }

    /**
     * Main action for manual processing
     */
    public function index()
    {
        $activity_type = Tools::getValue('activity_type');
        $selected      = Tools::getValue('selected');
        $from          = Tools::getValue('from');

        if (empty($selected) || !is_array($selected)) {
            return $this->setOutput(json_encode([
                'success' => false,
                'message' => $this->module->l('error_no_items_selected'),
            ]));
        }

        $this->forceSettings();

        $ovesio_route_resource_type = [
            'AdminProducts'         => 'product',
            'AdminCategories'       => 'category',
            'AdminAttributesGroups' => 'attribute',
            'AdminFeatures'         => 'feature',
        ];

        if (empty($ovesio_route_resource_type[$from])) {
            return $this->setOutput(json_encode([
                'success' => false,
                'message' => $this->module->l('error_invalid_resource_type'),
            ]));
        }

        $resource_type = $ovesio_route_resource_type[$from];

        $queue_handler = $this->module->buildQueueHandler(true);

        $debug = [];
        foreach ($selected as $resource_id) {
            $queue_handler->processQueue([
                'force_stale'   => true,
                'resource_type' => $resource_type,
                'resource_id'   => $resource_id,
                'activity_type' => $activity_type,
            ]);

            $debug = array_merge($debug, $queue_handler->getDebug());
        }

        $started = 0;
        foreach ($debug as $resource => $activities) {
            foreach ($activities as $at => $activity) {
                if ($activity_type != $at) {
                    continue;
                }

                if ($activity['status'] == 'started' || $activity['code'] == 'new') {
                    $started++;
                }
            }
        }

        $activity_type_map = [
            'generate_content' => $this->module->l('text_generate_content_item'),
            'generate_seo'     => $this->module->l('text_generate_seo_item'),
            'translate'        => $this->module->l('text_translate_item'),
        ];

        $resource_type_map = [
            'product'   => $this->module->l('text_products'),
            'category'  => $this->module->l('text_categories'),
            'attribute' => $this->module->l('text_attributes'),
            'feature'   => $this->module->l('text_features'),
        ];

        $r = [
            '{started}'       => $started,
            '{resource_type}' => strtolower($resource_type_map[$resource_type]),
            '{activity_type}' => strtolower($activity_type_map[$activity_type]),
        ];

        $message = str_replace(array_keys($r), array_values($r), $this->module->l('text_resources_request_submitted'));

        $this->setOutput(json_encode([
            'success' => true,
            'message' => $message
        ]));
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

    private function forceSettings()
    {
        $options['generate_content_include_disabled'] = [
            'products'   => true,
            'categories' => true,
        ];

        $options['generate_content_for'] = [
            'products'   => true,
            'categories' => true,
        ];

        $options['generate_content_include_stock_0'] = true;

        $options['generate_content_when_description_length'] = [
            'products'   => 999999999,
            'categories' => 999999999,
        ];

        $options['generate_seo_include_disabled'] = [
            'products'   => true,
            'categories' => true,
        ];

        $options['generate_seo_for'] = [
            'products'   => true,
            'categories' => true,
        ];

        $options['generate_seo_include_stock_0'] = true;

        $options['translate_include_disabled'] = [
            'products'   => true,
            'categories' => true,
        ];

        $options['generate_seo_only_for_action'] = false;

        $options['translate_include_stock_0'] = true;

        $options['translate_for'] = [
            'products'   => true,
            'categories' => true,
            'attributes' => true,
            'options'    => true,
        ];

        foreach ($options as $key => $value) {
            Configuration::set(strtoupper('ovesio_' . $key), json_encode($value));
        }
    }
}
