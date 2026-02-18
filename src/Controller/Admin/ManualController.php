<?php

namespace PrestaShop\Module\Ovesio\Controller\Admin;

use Configuration;
use PrestaShop\Module\Ovesio\Support\OvesioConfiguration;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

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
     * Main action for manual processing (legacy AJAX endpoint)
     */
    public function index()
    {
        $activity_type = \Tools::getValue('activity_type');
        $selected      = \Tools::getValue('selected');
        $from          = \Tools::getValue('from');

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
     * Bulk action handler for PrestaShop 9 grid bulk actions.
     * Receives form POST data from the grid filter form with selected item IDs.
     *
     * @param Request $request
     * @param string $resource_type product|category|attribute|feature
     * @param string $activity_type generate_content|generate_seo|translate
     * @return RedirectResponse
     */
    public function bulkAction(Request $request, string $resource_type, string $activity_type): RedirectResponse
    {
        // Map resource type to the grid field name used for bulk selection
        // The checkbox name follows the pattern: {gridId}_{columnId}[]
        // Products grid: grid_id=product, BulkActionColumn id=bulk -> product_bulk
        // Categories grid: grid_id=category, IdentifierColumn id=id_category -> category_id_category
        // Attribute Groups grid: grid_id=attribute_group, BulkActionColumn id=bulk -> attribute_group_bulk
        // Features grid: grid_id=feature, BulkActionColumn id=bulk -> feature_bulk
        $bulkFieldMap = [
            'product'   => 'product_bulk',
            'category'  => 'category_id_category',
            'attribute' => 'attribute_group_bulk',
            'feature'   => 'feature_bulk',
        ];

        // Map resource type to its redirect route
        $redirectRouteMap = [
            'product'   => 'admin_products_index',
            'category'  => 'admin_categories_index',
            'attribute' => 'admin_attribute_groups_index',
            'feature'   => 'admin_features_index',
        ];

        $redirectRoute = $redirectRouteMap[$resource_type] ?? 'admin_products_index';
        $bulkField = $bulkFieldMap[$resource_type] ?? null;

        if (!$bulkField) {
            $this->addFlash('error', $this->module->l('error_invalid_resource_type'));
            return $this->redirectToRoute($redirectRoute);
        }

        // Extract selected IDs from the form POST data
        $selected = $request->request->all($bulkField);

        if (empty($selected)) {
            $this->addFlash('error', $this->module->l('error_no_items_selected'));
            return $this->redirectToRoute($redirectRoute);
        }

        // Sanitize IDs
        $selected = array_map('intval', $selected);

        $this->forceSettings();

        try {
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
                '{resource_type}' => strtolower($resource_type_map[$resource_type] ?? $resource_type),
                '{activity_type}' => strtolower($activity_type_map[$activity_type] ?? $activity_type),
            ];

            $message = str_replace(array_keys($r), array_values($r), $this->module->l('text_resources_request_submitted'));

            $this->addFlash('success', $message);
        } catch (\Exception $e) {
            $this->addFlash('error', 'Ovesio Error: ' . $e->getMessage());
        }

        return $this->redirectToRoute($redirectRoute);
    }

    /**
     * Custom response (for legacy AJAX endpoint)
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
