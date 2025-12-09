<?php

namespace Ovesio;

use OvesioQueueModel;

/**
 * QueueHandler
 *
 * This class handles queue operations such as enqueueing, dequeueing,
 * and processing queued items. It manages the lifecycle of items in
 * the queue and provides methods for interacting with the queue system.
 *
 * Common responsibilities might include:
 * - Adding items to the queue
 * - Retrieving items from the queue
 * - Processing queued jobs or tasks
 * - Managing queue priorities or delays
 */
class QueueHandler
{
    /**
     * @var OvesioQueueModel The model instance for database interactions
     */
    private $model;

    private $api;
    private $options;
    private $log;
    private $debug = [];
    private $to_langs = [];
    private $force_stale = false;

    private $from_callback = false;

    /**
     * The list of activities that will be processed in order of priority
     */
    private $activity_groups = [
        'generate_content' => [],
        'generate_seo'     => [],
        'translate'        => [],
    ];

    private $activity_hash = [
        'generate_content' => [],
        'generate_seo'     => [],
        'translate'        => [],
    ];

    public function __construct($model, OvesioAI $api, $options = [], $log = null)
    {
        $this->model = $model;
        $this->api = $api;
        $this->options = $options;
        $this->log = $log;

        $languages = $this->getOption('language_settings', []);
        $from_lang = $this->getOption('default_language');

        $to_langs = [];
        foreach ($languages as $language) {
            if (empty($language['translate']) || $language['code'] === $from_lang || empty($language['translate_from'])) continue;

            $to_langs[] = $language['code'];
        }

        $this->to_langs = $to_langs;
    }

    /**
     * Get option value
     */
    private function getOption($key, $default = null)
    {
        $options = $this->options;

        return isset($options[$key]) ? $options[$key] : $default;
    }

    public function processQueue($params = [])
    {
        $this->force_stale = !empty($params['force_stale']);

        $list = $this->model->getCronList($params);

        $this->from_callback = !empty($params['from_callback']);

        // setup activity groups
        foreach ($list as $resource => $activities) {
            foreach ($activities as $activity_type => $activity) {
                $this->activity_groups[$activity_type][$resource] = $activity;
            }
        }

        if (!empty($params['activity_type'])) {
            if ($params['activity_type'] == 'generate_content') {
                $this->handleGenerateContentQueue();
            } elseif ($params['activity_type'] == 'generate_seo') {
                $this->handleGenerateSeoQueue();
            } elseif ($params['activity_type'] == 'translate') {
                $this->handleTranslateQueue();
            }
        } else {
            $this->handleGenerateContentQueue();
            $this->handleGenerateSeoQueue();
            $this->handleTranslateQueue();
        }

        $this->activity_groups = array_fill_keys(array_keys($this->activity_groups), []); // reset

        return $list;
    }

    /**
     * Add message to debug
     *
     * @return void
     */
    public function debug($resource, $resource_ids, $event_type, $code, $status = '')
    {
        foreach ((array) $resource_ids as $resource_id) {
            $this->debug["$resource/$resource_id"][$event_type] = [
                'code'   => $code,
                'status' => $status
            ];
        }
    }

    /**
     * Show debug messages
     *
     * @return void
     */
    public function showDebug()
    {
        if (empty($this->debug)) {
            echo "<p>No debug information available.</p>";
            return;
        }

        echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin: 20px 0; font-family: monospace;'>";
        echo "<thead>";
        echo "<tr style='background-color: #333; color: #fff;'>";
        echo "<th>Resource</th>";
        echo "<th>Generate Content</th>";
        echo "<th>Generate SEO</th>";
        echo "<th>Translate</th>";
        echo "</tr>";
        echo "</thead>";
        echo "<tbody>";

        $code_colors = [
            'not_found'         => '#999',      // Gray - initial state
            'new'               => '#0066cc',   // Blue - new item
            'changed'           => '#ff9800',   // Orange - content changed
            'unchanged'         => '#666',      // Dark gray - no changes
            'min_length_not_met'=> '#9c27b0',   // Purple - validation issue
            'translate'         => '#4caf50',   // Green - ready for translation
            'skipped'           => '#ff5722',   // Deep orange - skipped due to settings
        ];

        foreach ($this->debug as $resource => $events) {
            echo "<tr>";
            echo "<td style='font-weight: bold;'>{$resource}</td>";

            // Generate Content column
            if (isset($events['generate_content'])) {
                $code   = $events['generate_content']['code'];
                $status = $events['generate_content']['status'];
                $color = isset($code_colors[$code]) ? $code_colors[$code] : '#000';
                echo "<td style='color: {$color};'>{$code} {$status}</td>";
            } else {
                echo "<td><span style='color: #999;'>-</span></td>";
            }

            // Generate SEO column
            if (isset($events['generate_seo'])) {
                $code   = $events['generate_seo']['code'];
                $status = $events['generate_seo']['status'];
                $color = isset($code_colors[$code]) ? $code_colors[$code] : '#000';
                echo "<td style='color: {$color};'>{$code} {$status}</td>";
            } else {
                echo "<td><span style='color: #999;'>-</span></td>";
            }

            // Translate column
            if (isset($events['translate'])) {
                $code = $events['translate']['code'];
                $status = $events['translate']['status'];
                $color = isset($code_colors[$code]) ? $code_colors[$code] : '#000';
                echo "<td style='color: {$color};'>{$code} {$status}</td>";
            } else {
                echo "<td><span style='color: #999;'>-</span></td>";
            }

            echo "</tr>";
        }

        echo "</tbody>";
        echo "</table>";
    }

    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Decode HTML content in case of encoded
     */
    private function decode($data)
    {
        return $data; // Prestashop does not encode HTML entities by default

        if (is_array($data)) {
            foreach ($data as $key => $value) {
                $data[$key] = $this->decode($value);
            }
        } else {
            $data = html_entity_decode($data, ENT_COMPAT, 'UTF-8');
        }

        return $data;
    }

    protected function ignoreMoveOnNextEvent($resource_type, $resource_ids, $activity_type, $message = null, $status = 'completed', $request = null)
    {
        $resource_ids = (array) $resource_ids;

        if ($activity_type == 'translate') { // scalar
            foreach ($this->to_langs as $lang) {
                foreach ($resource_ids as $resource_id) {
                    $list_item = [
                        'resource_type' => $resource_type,
                        'resource_id'   => $resource_id,
                        'lang'          => $lang,
                        'activity_type' => $activity_type,
                        'stale'         => 0,
                        'updated_at'    => date('Y-m-d H:i:s')
                    ];

                    if ($request) {
                        $list_item['request'] = json_encode($request);
                    }

                    if ($message) {
                        $list_item['message'] = $message;
                    }

                    if ($status) {
                        $list_item['status'] = $status;
                    }

                    $this->model->addList($list_item);
                }
            }

        } else {

            $default_language = $this->getOption('default_language');

            foreach ($resource_ids as $resource_id) {
                $list_item = [
                    'resource_type' => $resource_type,
                    'resource_id'   => $resource_id,
                    'lang'          => $default_language,
                    'activity_type' => $activity_type,
                    'stale'         => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ];

                if ($request) {
                    $list_item['request'] = json_encode($request);
                }

                if ($message) {
                    $list_item['message'] = $message;
                }

                if ($status) {
                    $list_item['status'] = $status;
                }

                $this->model->addList($list_item);
            }
        }
    }

    /*********************************** GENERATE CONTENT ***********************************/

    protected function handleGenerateContentQueue()
    {
        $default_language                  = $this->getOption('default_language');
        $generate_content_status           = (bool) $this->getOption('generate_content_status');
        $generate_content_include_disabled = array_filter((array) $this->getOption('generate_content_include_disabled', []));
        $generate_content_for              = array_filter((array) $this->getOption('generate_content_for', []));
        $send_stock_0_products             = (bool) $this->getOption('generate_content_include_stock_0');

        if (!$generate_content_status) {
            return;
        }

        $activities = $this->activity_groups['generate_content'];
        $product_activities  = [];
        $category_activities = [];

        foreach ($activities as $resource => $started_activity) {
            list($resource_type, $resource_id) = explode('/', $resource);

            if (!$this->getOption('manual') && $started_activity) {
                if ($started_activity['status'] == 'started') { // if activity is started, we keep it staled until completion
                    $this->debug($resource_type, $resource_id, 'generate_content', 'in_progress', 'started');
                    $this->discardNextEvents('generate_content', $resource);
                    continue;
                } elseif ($started_activity['status'] == 'error' && !$started_activity['stale']) {
                    // allow re-processing errored activities only if staled
                    $this->debug($resource_type, $resource_id, 'generate_content', 'error', 'is in error status and not staled');
                    $this->discardNextEvents('generate_content', $resource);
                    continue;
                }
            }

            if ($resource_type == 'product') {
                $product_activities[$resource_id] = $started_activity;
            } elseif ($resource_type == 'category') {
                $category_activities[$resource_id] = $started_activity;
            }
        }

        $hash         = $this->getOption('hash');
        $server       = $this->getOption('server_url', '');
        $workflow     = $this->getOption('generate_content_workflow');
        $callback_url = $server . '/module/ovesio/callback?type=generate_content&hash=' . $hash;
        if ($this->getOption('manual')) {
            $callback_url .= '&manual=true';
        }

        $to_lang      = $this->getOption('default_language');

        $request = $this->api->generateDescription()
        ->workflow($workflow['id'])
        ->callbackUrl($callback_url);
        if ($to_lang != 'auto') {
            $request = $request->to($to_lang);
        }

        if (!empty($product_activities)) {
            if (!empty($generate_content_for['products'])) {
                $this->pushGenerateProductDescriptionRequests($request, $product_activities, !empty($generate_content_include_disabled['products']), $send_stock_0_products);
            }
        }

        if (!empty($category_activities)) {
            if (!empty($generate_content_for['categories'])) {
                $this->pushGenerateCategoryDescriptionRequests($request, $category_activities, !empty($generate_content_include_disabled['categories']));
            }
        }

        if (!$request->getData()) {
            $this->debug('total', '', 'generate_content', 0);
            return;
        }

        $request_data = array_column($request->getData(), 'content', 'ref');

        // unset further processing for these resources
        foreach ($request_data as $_resource => $item) {
            $this->discardNextEvents('generate_content', $_resource);
        }

        try {
            $response = $request->request();
        } catch (\Exception $e) {
            return $this->log->write('Ovesio QueueHandler generate content error: ' . $e->getMessage());
        }

        $response = json_decode(json_encode($response), true);

        if (!empty($response['success'])) {
            foreach ($response['data'] as $item) {
                $hash = $this->activity_hash['generate_content'][$item['ref']];

                list($resource_type, $resource_id) = explode('/', $item['ref']);

                $this->model->addList([
                    'resource_type' => $resource_type,
                    'resource_id'   => $resource_id,
                    'lang'          => $default_language,
                    'activity_type' => 'generate_content',
                    'activity_id'   => $item['id'],
                    'hash'          => $hash,
                    'status'        => 'started',
                    'request'       => json_encode($request_data[$item['ref']]),
                    'response'      => json_encode($item),
                    'stale'         => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
            }

            $this->debug('total', '', 'generate_content', count($response['data']));
        } else {
            $this->massLogErrors($response, $request->getData(), 'generate_content');
            $this->debug('total', '', 'generate_content', 0);
        }
    }

    protected function pushGenerateProductDescriptionRequests($request, $activities, $include_disabled = false, $include_stock_0 = true)
    {
        $product_ids = array_keys($activities);
        $this->debug('product', $product_ids, 'generate_content', 'not_found'); // we make presence known on each iteration

        $products = $this->model->getProducts($product_ids, $include_disabled, $include_stock_0);

        if (empty($products)) {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'generate_content', "Not found, disabled or out of stock", 'skipped');
            return;
        }

        // chunk get features based on product_id
        $product_features = $this->model->getProductsFeatures($product_ids);
        $product_categories = $this->model->getProductCategories($product_ids);

        foreach ($products as $i => $product) {
            $push = [
                'ref' => 'product/' . $product['product_id'],
                'content' => [
                    'name' => $product['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($product['description']));
            if (strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description)) != strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($product['name']))))) {
                $push['content']['description'] = $product['description'];
            }

            foreach (($product_categories[$product['product_id']] ?? []) as $category) {
                $push['content']['categories'][] = $category;
            }

            foreach (($product_features[$product['product_id']] ?? []) as $feature_id => $feature) {
                if (empty($feature['values'])) {
                    continue;
                }

                $push['content']['additional'][] = $feature['name'] . ': ' . implode(', ', $feature['values']);
            }

            $push['content'] = $this->decode($push['content']);

            foreach ($push['content'] as $k => $v) {
                if (is_array($v)) {
                    sort($push['content'][$k]);
                }
            }

            // remove description from hash to avoid recreating it everytime
            $hashable_push = $push;
            unset($hashable_push['content']['description']);

            $hash = $this->contentHash($hashable_push['content']);
            $this->activity_hash['generate_content'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'product', $product['product_id'], $hash, 'generate_content')) {
                continue;
            }

            $description_lengths_min = $this->getOption('generate_content_when_description_length');

            $payload_length = $this->getAiRequestPayloadLength($push['content']);
            if ($payload_length <= 25) { // fallback
                if (isset($push['content']['description'])) {
                    $push['content']['description'] = $push['content']['name'] . "\n" . $push['content']['description'];
                } else {
                    $push['content']['description'] = $push['content']['name'];
                }

                $payload_length = $this->getAiRequestPayloadLength($push['content']);
            }

            if (strlen($_description) > $description_lengths_min['products']) {
                $this->debug('product', $product['product_id'], 'generate_content', 'max_length_not_met');
                $this->ignoreMoveOnNextEvent('product', $product['product_id'], 'generate_content', "Description exceeds the " . $description_lengths_min['products'] . ' character limit' , 'skipped');
                continue;
            }

            $this->debug('product', $product['product_id'], 'generate_content', 'new');

            $this->discardNextEvents('generate_description', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }
    }

    protected function pushGenerateCategoryDescriptionRequests($request, $activities, $include_disabled = false)
    {
        $category_ids = array_keys($activities);
        $this->debug('category', $category_ids, 'generate_content', 'not_found'); // we make presence known on each iteration

        $categories = $this->model->getCategories($category_ids, $include_disabled);

        if (empty($categories)) {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'generate_content', "Not found or disabled", 'skipped');
            return;
        }

        foreach ($categories as $i => $category) {
            $push = [
                'ref' => 'category/' . $category['category_id'],
                'content' => [
                    'name' => $category['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($category['description']));
            if (strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description)) != strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($category['name']))))) {
                $push['content']['description'] = $category['description'];
            }

            $push['content'] = $this->decode($push['content']);

            // remove description from hash to avoid recreating it everytime
            $hashable_push = $push;
            unset($hashable_push['content']['description']);

            $hash = $this->contentHash($hashable_push['content']);
            $this->activity_hash['generate_content'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'category', $category['category_id'], $hash, 'generate_content')) {
                continue;
            }

            $description_lengths_min = $this->getOption('generate_content_when_description_length');

            // try to save resource from being ignored by minimum 25 chars limit
            $payload_length = $this->getAiRequestPayloadLength($push['content']);
            if ($payload_length <= 25) { // fallback
                if (isset($push['content']['description'])) {
                    $push['content']['description'] = $push['content']['name'] . "\n" . $push['content']['description'];
                } else {
                    $push['content']['description'] = $push['content']['name'];
                }

                $payload_length = $this->getAiRequestPayloadLength($push['content']);
            }

            if (strlen($_description) > $description_lengths_min['categories']) {
                $this->debug('category', $category['category_id'], 'generate_content', 'max_length_not_met');
                $this->ignoreMoveOnNextEvent('category', $category['category_id'], 'generate_content', "Description exceeds the " . $description_lengths_min['categories'] . ' character limit' , 'skipped');
                continue;
            }

            $this->debug('category', $category['category_id'], 'generate_content', 'new');

            $this->discardNextEvents('generate_description', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }
    }

    /*********************************** GENERATE SEO ***********************************/

    protected function handleGenerateSeoQueue()
    {
        $default_language              = $this->getOption('default_language');
        $generate_seo_status           = (bool) $this->getOption('generate_seo_status');
        $generate_seo_include_disabled = array_filter((array) $this->getOption('generate_seo_include_disabled', []));
        $generate_seo_for              = array_filter((array) $this->getOption('generate_seo_for', []));
        $send_stock_0_products         = (bool) $this->getOption('generate_seo_include_stock_0');

        if (!$generate_seo_status) {
            return;
        }

        $activities = $this->activity_groups['generate_seo'];
        $product_activities  = [];
        $category_activities = [];

        foreach ($activities as $resource => $started_activity) {
            list($resource_type, $resource_id) = explode('/', $resource);

            if (!$this->getOption('manual') && $started_activity) { // if activity is started, we keep it staled until completion
                if ($started_activity['status'] == 'started') {
                    $this->debug($resource_type, $resource_id, 'generate_seo', 'in_progress', 'started');
                    $this->discardNextEvents('generate_seo', $resource);
                    continue;
                } elseif ($started_activity['status'] == 'error' && !$started_activity['stale']) {
                    // allow re-processing errored activities only if staled
                    $this->debug($resource_type, $resource_id, 'generate_seo', 'error', 'is in error status and not staled');
                    $this->discardNextEvents('generate_seo', $resource);
                    continue;
                }
            }

            if ($resource_type == 'product') {
                $product_activities[$resource_id] = $started_activity;
            } elseif ($resource_type == 'category') {
                $category_activities[$resource_id] = $started_activity;
            }
        }

        $hash         = $this->getOption('hash');
        $server       = $this->getOption('server_url', '');
        $workflow     = $this->getOption('generate_seo_workflow');
        $callback_url = $server . '/module/ovesio/callback?type=generate_seo&hash=' . $hash;
        if ($this->getOption('manual')) {
            $callback_url .= '&manual=true';
        }

        $to_lang      = $this->getOption('default_language');

        $request = $this->api->generateSeo()
        ->workflow($workflow['id'])
        ->callbackUrl($callback_url);
        if ($to_lang != 'auto') {
            $request = $request->to($to_lang);
        }

        if (!empty($product_activities)) {
            if (!empty($generate_seo_for['products'])) {
                $this->pushGenerateProductSeoRequests($request, $product_activities, !empty($generate_seo_include_disabled['products']), $send_stock_0_products);
            }
        }

        if (!empty($category_activities)) {
            if (!empty($generate_seo_for['categories'])) {
                $this->pushGenerateCategorySeoRequests($request, $category_activities, !empty($generate_seo_include_disabled['categories']));
            }
        }

        if (!$request->getData()) {
            $this->debug('total', '', 'generate_seo', 0);
            return;
        }

        $request_data = array_column($request->getData(), 'content', 'ref');

        // unset further processing for these resources
        foreach ($request_data as $_resource => $item) {
            $this->discardNextEvents('generate_seo', $_resource);
        }

        try {
            $response = $request->request();
        } catch (\Exception $e) {
            return $this->log->write('Ovesio QueueHandler generate SEO error: ' . $e->getMessage());
        }

        $response = json_decode(json_encode($response), true);

        if (!empty($response['success'])) {
            foreach ($response['data'] as $item) {
                $hash = $this->activity_hash['generate_seo'][$item['ref']];

                list($resource_type, $resource_id) = explode('/', $item['ref']);

                $this->model->addList([
                    'resource_type' => $resource_type,
                    'resource_id'   => $resource_id,
                    'lang'          => $default_language,
                    'activity_type' => 'generate_seo',
                    'activity_id'   => $item['id'],
                    'hash'          => $hash,
                    'status'        => 'started',
                    'request'       => json_encode($request_data[$item['ref']]),
                    'response'      => json_encode($item),
                    'stale'         => 0,
                    'updated_at'    => date('Y-m-d H:i:s')
                ]);
            }

            $this->debug('total', '', 'generate_seo', count($response['data']));
        } else {
            $this->debug('total', '', 'generate_seo', 0);
            $this->massLogErrors($response, $request->getData(), 'generate_seo');
        }
    }

    protected function pushGenerateProductSeoRequests($request, $activities, $include_disabled = false, $include_stock_0 = true)
    {
        $product_ids = array_keys($activities);
        $this->debug('product', $product_ids, 'generate_seo', 'not_found'); // we make presence known on each iteration
        $only_for_action = $this->getOption('generate_seo_only_for_action');

        if ($only_for_action) {
            $this->debug('product', $product_ids, 'generate_seo', 'skipped'); // we make presence known on each iteration
            $products = $this->model->getProductsWithDescriptionDependency($product_ids, $include_disabled, $include_stock_0);
        } else {
            $this->debug('product', $product_ids, 'generate_seo', 'not_found'); // we make presence known on each iteration
            $products = $this->model->getProducts($product_ids, $include_disabled, $include_stock_0);
        }

        if (empty($products)) {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'generate_seo', "Not found, disabled or out of stock", 'skipped');
            return;
        }

        // chunk get features based on product_id
        $product_features = $this->model->getProductsFeatures($product_ids);
        $product_categories = $this->model->getProductCategories($product_ids);

        foreach ($products as $i => $product) {
            $push = [
                'ref' => 'product/' . $product['product_id'],
                'content' => [
                    'name' => $product['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($product['description']));
            if (trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description))) != trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($product['name'])))))) {
                $push['content']['description'] = $product['description'];
            }

            foreach (($product_categories[$product['product_id']] ?? []) as $category) {
                $push['content']['categories'][] = $category;
            }

            foreach (($product_features[$product['product_id']] ?? []) as $feature_id => $feature) {
                if (empty($feature['values'])) {
                    continue;
                }

                $push['content']['additional'][] = $feature['name'] . ': ' . implode(', ', $feature['values']);
            }

            $push['content'] = $this->decode($push['content']);

            $hash = $this->contentHash($push['content']);
            $this->activity_hash['generate_seo'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'product', $product['product_id'], $hash, 'generate_seo')) {
                continue;
            }

            $this->debug('product', $product['product_id'], 'generate_seo', 'new');

            $this->discardNextEvents('generate_seo', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }
    }

    protected function pushGenerateCategorySeoRequests($request, $activities, $include_disabled = false)
    {
        $category_ids = array_keys($activities);
        $this->debug('category', $category_ids, 'generate_seo', 'not_found'); // we make presence known on each iteration
        $only_for_action = $this->getOption('generate_seo_only_for_action');

        if ($only_for_action) {
            $this->debug('category', $category_ids, 'generate_seo', 'skipped', 'needs_content_generation'); // we make presence known on each iteration
            $categories = $this->model->getCategoriesWithDescriptionDependency($category_ids, $include_disabled);
        } else {
            $this->debug('category', $category_ids, 'generate_seo', 'not_found'); // we make presence known on each iteration
            $categories = $this->model->getCategories($category_ids, $include_disabled);
        }

        if (empty($categories)) {
            if ($only_for_action) {
                // he would have, if it wasn't for that condition
                $categories = $this->model->getCategories($category_ids, $include_disabled);
                if (empty($categories)) {
                    // still not found
                    $this->ignoreMoveOnNextEvent('category', $category_ids, 'generate_seo', "Not found or disabled", 'skipped');
                }
            }

            return;
        }

        foreach ($categories as $i => $category) {
            $push = [
                'ref' => 'category/' . $category['category_id'],
                'content' => [
                    'name' => $category['name']
                ]
            ];

            // only if is different from name...usual mistake
            $_description = strip_tags($this->decode($category['description']));
            if (trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', $_description))) != trim(strtolower(preg_replace("/[^A-Za-z0-9 ]/", '', strip_tags($this->decode($category['name'])))))) {
                $push['content']['description'] = $category['description'];
            }

            $push['content'] = $this->decode($push['content']);

            $hash = $this->contentHash($push['content']);
            $this->activity_hash['generate_seo'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'category', $category['category_id'], $hash, 'generate_seo')) {
                continue;
            }

            $this->debug('category', $category['category_id'], 'generate_seo', 'new');

            $this->discardNextEvents('generate_seo', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }
    }

    /*********************************** TRANSLATE ***********************************/

    protected function handleTranslateQueue()
    {
        if (empty($this->to_langs)) {
            $this->model->skipRunningTranslations();
            return;
        }

        $default_language           = $this->getOption('default_language');
        $translate_status           = (bool) $this->getOption('translate_status');
        $translate_include_disabled = array_filter((array) $this->getOption('translate_include_disabled', []));
        $send_stock_0_products      = (bool) $this->getOption('translate_include_stock_0');

        if (!$translate_status) {
            return;
        }

        $translate_for = $this->getTranslateFor();

        if (empty($translate_for)) {
            return;
        }

        $activities = $this->activity_groups['translate'];
        $product_activities         = [];
        $category_activities        = [];
        $attribute_group_activities = [];
        $attribute_activities       = [];
        $feature_activities         = [];

        foreach ($activities as $resource => $started_translate_activities) {
            list($resource_type, $resource_id) = explode('/', $resource);

            foreach ($started_translate_activities as $started_activity) {
                if (!$this->getOption('manual')) { // if activity is started, we keep it staled until completion
                    if ($started_activity['status'] == 'started') {
                        $this->debug($resource_type, $resource_id, 'translate', 'in_progress', 'started');
                        continue 2; // we do not start new translations until the current translation session on the respective resource is finished
                    } elseif ($started_activity['status'] == 'error' && !$started_activity['stale']) {
                        // allow re-processing errored activities only if staled
                        $this->debug($resource_type, $resource_id, 'translate', 'error', 'is in error status and not staled');
                        continue 2;
                    }
                }
            }

            if ($resource_type == 'product') {
                $product_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'category') {
                $category_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'attribute_group') {
                $attribute_group_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'attribute') {
                $attribute_activities[$resource_id] = $started_translate_activities;
            } elseif ($resource_type == 'feature') {
                $feature_activities[$resource_id] = $started_translate_activities;
            }
        }

        $hash     = $this->getOption('hash');
        $server   = $this->getOption('server_url', '');
        $workflow = $this->getOption('translate_workflow');

        $callback_url = $server . '/module/ovesio/callback?type=translate&hash=' . $hash;
        if ($this->getOption('manual')) {
            $callback_url .= '&manual=true';
        }

        $from_lang    = $this->getOption('default_language', 'en');

        $languages = $this->getOption('language_settings', []);

        $to_langs = $this->to_langs;
        $conditions = [];

        foreach ($languages as $language) {
            // apply conditions in case the from language is not the default language
            if ($from_lang != $language['translate_from'] && in_array($language['code'], $to_langs)) {
                $conditions[$language['code']] = $language['translate_from'];
            }
        }

        $request = $this->api->translate()
        ->workflow($workflow['id'])
        ->from($from_lang)
        ->to($to_langs)
        ->conditions($conditions)
        ->callbackUrl($callback_url);

        if (!empty($attribute_group_activities)) {
            if (!empty($translate_for['attributes'])) {
                $this->pushTranslateAttributeGroupRequests($request, $attribute_group_activities, !empty($translate_include_disabled['attributes']));
            }
        }

        if (!empty($attribute_activities)) {
            if (!empty($translate_for['attributes'])) {
                $this->pushTranslateAttributeRequests($request, $attribute_activities, !empty($translate_include_disabled['attributes']));
            }
        }

        if (!empty($feature_activities)) {
            if (!empty($translate_for['features'])) {
                $this->pushTranslateFeatureRequests($request, $feature_activities, !empty($translate_include_disabled['features']));
            }
        }

        if (!empty($product_activities)) {
            if (!empty($translate_for['products'])) {
                $this->pushTranslateProductRequests($request, $product_activities, !empty($translate_include_disabled['products']), $send_stock_0_products);
            }
        }

        if (!empty($category_activities)) {
            if (!empty($translate_for['categories'])) {
                $this->pushTranslateCategoryRequests($request, $category_activities, !empty($translate_include_disabled['categories']));
            }
        }

        if (!$request->getData()) {
            $this->debug('total', '', 'translate', 0);
            return;
        }

        $request_data = array_column($request->getData(), 'content', 'ref');

        try {
            $response = $request->request();
        } catch (\Exception $e) {
            return $this->log->write('Ovesio QueueHandler translate error: ' . $e->getMessage());
        }

        $response = json_decode(json_encode($response), true);

        if (!empty($response['success'])) {
            foreach ($response['data'] as $item) {
                $hash = $this->activity_hash['translate'][$item['ref']] ?? '';

                list($resource_type, $resource_id) = explode('/', $item['ref']);

                foreach ($to_langs as $lang) {
                    $this->model->addList([
                        'resource_type' => $resource_type,
                        'resource_id'   => $resource_id,
                        'lang'          => $lang,
                        'activity_type' => 'translate',
                        'activity_id'   => $item['id'],
                        'hash'          => $hash,
                        'status'        => 'started',
                        'request'       => json_encode($request_data[$item['ref']]),
                        'response'      => json_encode($item),
                        'stale'         => 0,
                        'updated_at'    => date('Y-m-d H:i:s')
                    ]);
                }
            }

            $this->debug('total', '', 'translate', count($response['data']));
        } else {
            $this->debug('total', '', 'translate', 0);
            $this->massLogErrors($response, $request->getData(), 'translate');
        }
    }

    protected function pushTranslateCategoryRequests($request, $activities, $include_disabled = false)
    {
        $category_ids = array_keys($activities);
        $this->debug('category', $category_ids, 'translate', 'not_found'); // we make presence known on each iteration

        $translate_for = $this->getTranslateFor();
        $translate_fields = (array) $this->getOption('translate_fields', []);

        if (empty($translate_for['categories'])) {
            $this->debug('category', $category_ids, 'translate', 'skipped', 'disabled');
            return;
        }

        $translate_fields = $translate_fields['categories'];

        $categories = $this->model->getCategories($category_ids, $include_disabled);

        if (empty($categories)) {
            $this->ignoreMoveOnNextEvent('category', $category_ids, 'translate', "Not found or disabled", 'skipped');
            return;
        }

        foreach ($categories as $i => $category) {
            $push = [
                'ref'     => 'category/' . $category['category_id'],
                'content' => []
            ];

            foreach ($translate_fields as $key => $send) {
                if (!$send || empty($category[$key])) continue;

                $push['content'][] = [
                    'key'   => $key,
                    'value' => $category[$key]
                ];
            }

            if (empty($push['content'])) {
                $this->ignoreMoveOnNextEvent('category', $category['category_id'], 'translate', "Empty content to translate", 'skipped');
                $this->debug('category', $category['category_id'], 'translate', 'skipped', 'empty_content');
                continue;
            }

            $push['content'] = $this->decode($push['content']);

            $hash = $this->contentHash($push['content']);
            $this->activity_hash['translate'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'category', $category['category_id'], $hash, 'translate')) {
                continue;
            }

            $this->debug('category', $category['category_id'], 'translate', 'new');

            $this->discardNextEvents('translate', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }
    }

    protected function pushTranslateProductRequests($request, $activities, $include_disabled = false, $include_stock_0 = true)
    {
        $product_ids = array_keys($activities);
        $this->debug('product', $product_ids, 'translate', 'not_found'); // we make presence known on each iteration

        $translate_fields = (array) $this->getOption('translate_fields', []);
        $translate_for = array_filter($translate_fields, function ($item) {
            return array_filter($item);
        });

        if (empty($translate_for['products'])) {
            $this->debug('product', $product_ids, 'translate', 'skipped', 'disabled');
            return;
        }

        $translate_for = (array) $this->getOption('translate_for', []);
        $translate_fields = $translate_fields['products'];

        if (!empty($translate_for['attributes'])) {
            $translate_fields['attributes'] = '1';
        }

        if (!empty($translate_for['features'])) {
            $translate_fields['features'] = '1';
        }

        $products = $this->model->getProducts($product_ids, $include_disabled, $include_stock_0);

        if (empty($products)) {
            $this->ignoreMoveOnNextEvent('product', $product_ids, 'translate', "Not found, disabled or out of stock", 'skipped');
            return;
        }

        foreach ($products as $i => $product) {
            $push = [
                'ref'     => 'product/' . $product['product_id'],
                'content' => []
            ];

            foreach ($translate_fields as $key => $send) {
                if (!$send || empty($product[$key])) continue;

                $push['content'][] = [
                    'key'   => $key,
                    'value' => $product[$key]
                ];
            }

            // if (!empty($attribute_translate_fields['value'])) {
            //     foreach (($product_attributes[$product['product_id']] ?? []) as $attribute_id => $attribute_text) {
            //         $push['content'][] = [
            //             'key'     => 'a-' . $attribute_id,
            //             'value'   => $attribute_text,
            //             'context' => $attributes[$attribute_id]
            //         ];
            //     }
            // }

            if (empty($push['content'])) {
                $this->debug('product', $product['product_id'], 'translate', 'skipped', 'empty_content');
                $this->ignoreMoveOnNextEvent('product', $product['product_id'], 'translate', "Empty content to translate", 'skipped');
                continue;
            }

            $push['content'] = $this->decode($push['content']);

            $hash = $this->contentHash($push['content']);
            $this->activity_hash['translate'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'product', $product['product_id'], $hash, 'translate')) {
                continue;
            }

            $this->debug('product', $product['product_id'], 'translate', 'new');

            $this->discardNextEvents('translate', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }
    }

    protected function pushTranslateAttributeGroupRequests($request, $activities, $include_disabled = false)
    {
        $attribute_group_ids = array_keys($activities);
        $this->debug('attribute_group', $attribute_group_ids, 'translate', 'not_found'); // we make presence known on each iteration

        $translate_for = $this->getTranslateFor();

        if (empty($translate_for['attributes'])) {
            $this->debug('attribute_group', $attribute_group_ids, 'translate', 'skipped', 'disabled');
            $this->ignoreMoveOnNextEvent('attribute_group', $attribute_group_ids, 'translate', "Groups and Attributes translation is disabled", 'skipped');
            return;
        }

        $attribute_groups = $this->model->getAttributeGroups($attribute_group_ids);
        $attribute_groups = array_column($attribute_groups, null, 'attribute_group_id');

        if (empty($attribute_groups)) {
            $this->ignoreMoveOnNextEvent('attribute_group', $attribute_group_ids, 'translate', "No attribute groups found", 'skipped');
            return;
        }

        $attributes = $this->model->getGroupsAttributes($attribute_group_ids);

        $groups = [];
        foreach ($attribute_group_ids as $attribute_group_id) {
            if (!isset($attribute_groups[$attribute_group_id])) continue;

            $groups[$attribute_group_id] = [
                'ref' => 'attribute_group/' . $attribute_group_id,
                'content' => [
                    [
                        'key'   => 'ag-' . $attribute_group_id,
                        'value' => $attribute_groups[$attribute_group_id]['name']
                    ]
                ]
            ];
        }

        foreach ($attributes as $attribute) {
            if (!isset($groups[$attribute['attribute_group_id']])) continue;

            $groups[$attribute['attribute_group_id']]['content'][] = [
                'key'     => 'a-' . $attribute['attribute_id'],
                'context' => $attribute_groups[$attribute['attribute_group_id']]['name'],
                'value'   => $attribute['name'],
            ];
        }

        foreach ($groups as $attribute_group_id => $push) {
            $push['content'] = $this->decode($push['content']);

            $hash = $this->contentHash($push['content']);
            $this->activity_hash['translate'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'attribute_group', $attribute_group_id, $hash, 'translate')) {
                continue;
            }

            $this->debug('attribute_group', $attribute_group_id, 'translate', 'new');

            $this->discardNextEvents('translate', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }

        if (!$request->getData()) {
            $this->ignoreMoveOnNextEvent('attribute_group', $attribute_group_ids, 'translate', "No data left to process", 'skipped');
        }
    }

    protected function pushTranslateAttributeRequests($request, $activities, $include_disabled = false)
    {
        $attribute_ids = array_keys($activities);

        $translate_for = $this->getTranslateFor();

        if (empty($translate_for['attributes'])) {
            $attributes          = $this->model->getAttributes($attribute_ids);
            $attribute_group_ids = array_column($attributes, 'attribute_group_id');
            $this->debug('attribute_group', $attribute_group_ids, 'translate', 'skipped', 'disabled');
            $this->ignoreMoveOnNextEvent('attribute', $attribute_ids, 'translate', "Groups and Attributes translation is disabled", 'skipped');
            return;
        }

        $attribute_groups = $this->model->getAttributeGroups();
        if (empty($attribute_groups)) {
            $this->ignoreMoveOnNextEvent('attribute', $attribute_ids, 'translate', "No attribute groups found", 'skipped');
            return;
        }

        $attribute_groups = array_column($attribute_groups, null, 'attribute_group_id');

        $attributes          = $this->model->getAttributes($attribute_ids);
        $attribute_group_ids = array_column($attributes, 'attribute_group_id');
        $attributes          = $this->model->getGroupsAttributes($attribute_group_ids);

        $this->debug('attribute_group', $attribute_group_ids, 'translate', 'not_found'); // we make presence known on each iteration

        $groups = [];
        foreach ($attribute_group_ids as $attribute_group_id) {
            if (!isset($attribute_groups[$attribute_group_id])) continue;

            $groups[$attribute_group_id] = [
                'ref' => 'attribute_group/' . $attribute_group_id,
                'content' => [
                    [
                        'key'   => 'ag-' . $attribute_group_id,
                        'value' => $attribute_groups[$attribute_group_id]['name']
                    ]
                ]
            ];
        }

        foreach ($attributes as $attribute) {
            if (!isset($groups[$attribute['attribute_group_id']])) continue;

            $groups[$attribute['attribute_group_id']]['content'][] = [
                'key'     => 'a-' . $attribute['attribute_id'],
                'context' => $attribute_groups[$attribute['attribute_group_id']]['name'],
                'value'   => $attribute['name'],
            ];
        }

        foreach (array_values($groups) as $push) {
            $push['content'] = $this->decode($push['content']);

            $hash = $this->contentHash($push['content']);
            $this->activity_hash['translate'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'attribute_group', $attribute['attribute_group_id'], $hash, 'translate')) {
                continue;
            }

            $this->debug('attribute_group', $attribute['attribute_group_id'], 'translate', 'new');

            $this->discardNextEvents('translate', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }

        if (!$request->getData()) {
            $this->ignoreMoveOnNextEvent('attribute', $attribute_ids, 'translate', "No data left to process", 'skipped');
        }
    }

    public function pushTranslateFeatureRequests($request, $activities, $include_disabled = false)
    {
        $feature_ids = array_keys($activities);

        $translate_for = $this->getTranslateFor();

        if (empty($translate_for['features'])) {
            $this->debug('feature', $feature_ids, 'translate', 'skipped', 'disabled');
            $this->ignoreMoveOnNextEvent('feature', $feature_ids, 'translate', "Features translation is disabled", 'skipped');
            return;
        }

        $features = $this->model->getFeatures($feature_ids);
        if (empty($features)) {
            $this->ignoreMoveOnNextEvent('feature', $feature_ids, 'translate', "No features found", 'skipped');
            return;
        }

        $feature_values = $this->model->getFeatureValues($feature_ids);

        $_feature_values = [];
        foreach ($feature_values as $feature_value) {
            $_feature_values[$feature_value['feature_id']][] = $feature_value;
        }

        $feature_values = $_feature_values;
        unset($_feature_values);

        foreach ($features as $feature) {
            $push = [
                'ref'     => 'feature/' . $feature['feature_id'],
                'content' => []
            ];

            $push['content'][] = [
                'key'   => 'f-' . $feature['feature_id'],
                'value' => $feature['name'],
            ];

            foreach (($feature_values[$feature['feature_id']] ?? []) as $feature_value) {
                $push['content'][] = [
                    'key'     => 'fv-' . $feature_value['feature_value_id'],
                    'context' => $feature['name'],
                    'value'   => $feature_value['name'],
                ];
            }

            if (empty($push['content'])) {
                $this->ignoreMoveOnNextEvent('feature', $feature['feature_id'], 'translate', "Empty content to translate", 'skipped');
                $this->debug('feature', $feature['feature_id'], 'translate', 'skipped', 'empty_content');
                continue;
            }

            $push['content'] = $this->decode($push['content']);

            $hash = $this->contentHash($push['content']);
            $this->activity_hash['translate'][$push['ref']] = $hash;

            if (!$this->activityIsStaled($activities, 'feature', $feature['feature_id'], $hash, 'translate')) {
                continue;
            }

            $this->debug('feature', $feature['feature_id'], 'translate', 'new');

            $this->discardNextEvents('translate', $push['ref']);

            $request->data($push['content'], $push['ref']);
        }

        if (!$request->getData()) {
            $this->ignoreMoveOnNextEvent('feature', $feature_ids, 'translate', "No data left to process");
        }
    }

    private function massLogErrors($response, $data, $activity_type)
    {
        // gather errors per item index (avoid duplicate)
        $item_errors = [];
        foreach ($response['errors'] as $key => $error) {
            if (stripos($key, 'data.') === 0) {
                $temp = explode('.', $key);
                $index = $temp[1];

                $item_errors[$index] = $error;
            }
        }

        $default_language = $this->getOption('default_language');

        foreach ($item_errors as $index => $error) {
            $request = $data[$index];

            list($resource_type, $resource_id) = explode('/', $request['ref']);

            $this->model->addList([
                'resource_type' => $resource_type,
                'resource_id'   => $resource_id,
                'lang'          => $default_language,
                'activity_type' => $activity_type,
                'hash'          => $this->activity_hash[$activity_type][$request['ref']] ?? '',
                'status'        => 'error',
                'message'       => $error,
                'request'       => json_encode($request),
                'stale'         => 0,
                'updated_at'    => date('Y-m-d H:i:s')
            ]);

            $this->debug($resource_type, $resource_id, $activity_type, 'error', $error);
        }

        foreach ($data as $index => $_response) {
            if (!isset($item_errors[$index])) {
                list($resource_type, $resource_id) = explode('/', $_response['ref']);

                $this->debug($resource_type, $resource_id, $activity_type, 'not_sent', 'Item is ok, but package sending failed because of other items errors');
            }
        }
    }

    private function activityIsStaled($activities, $resource_type, $resource_id, $hash, $activity_type)
    {
        if ($this->force_stale) {
            $this->debug($resource_type, $resource_id, $activity_type, 'force_stale');
            return true;
        }

        if (empty($activities[$resource_id])) {
            $this->debug($resource_type, $resource_id, $activity_type, 'new');
            return true;
        }

        $activity = $activities[$resource_id];

        if ($activity_type == 'translate') {
            // if a new language has been added, stale is true for sure
            $existing_langs = array_column($activity, 'lang');
            if (array_diff($this->to_langs, $existing_langs)) {
                $this->debug($resource_type, $resource_id, $activity_type, 'changed', 'new_language_added');
                return true;
            }

            $activity = reset($activity);
        }

        $message = null; // do not alter message, only if completed
        if ($activity['status'] == 'completed' && !$this->from_callback) { // da impresia pe callback ca ar face o dubla procesare
            $message = "Hash did not changed";
        }

        $staled = false;
        if ($this->getOption("{$activity_type}_live_update")) {
            $old_hash = $activity['hash'];

            if ($old_hash == $hash) {
                $this->debug($resource_type, $resource_id, $activity_type, 'unchanged', $activity['status']);
                $this->ignoreMoveOnNextEvent($resource_type, $resource_id, $activity_type, $message, null);
            } else {
                $this->debug($resource_type, $resource_id, $activity_type, 'changed');
                $staled = true;
            }
        } else {
            $this->debug($resource_type, $resource_id, $activity_type, 'unchanged', $activity['status']);
            $this->ignoreMoveOnNextEvent($resource_type, $resource_id, $activity_type, $message, null);
        }

        return $staled;
    }

    private function discardNextEvents($activity_type, $resource)
    {
        if ($activity_type == 'generate_content') {
            unset($this->activity_groups['generate_content'][$resource]);
            unset($this->activity_groups['generate_seo'][$resource]);
            unset($this->activity_groups['translate'][$resource]);
        }

        if ($activity_type == 'generate_seo') {
            unset($this->activity_groups['generate_seo'][$resource]);
            unset($this->activity_groups['translate'][$resource]);
        }

        if ($activity_type == 'translate') {
            unset($this->activity_groups['translate'][$resource]);
        }
    }

    private function contentHash($data)
    {
        foreach ($data as $k => $v) {
            if (is_array($v)) {
                sort($data[$k]);

                $data[$k] = implode('___', $data[$k]);
            }
        }

        $separator = ';;;';
        $hash = [];
        foreach ($data as $value) {
            $push = trim(preg_replace('/[\n\r\t\s]+/u', ' ', $value));
            $hash[] = $push;
        }

        $hash_string = implode($separator, $hash);

        // sha256
        $alpha_num = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $hash_string));
        $prefix    = str_pad(substr($alpha_num, 0, 3), 3, "_", STR_PAD_LEFT);
        $suffix    = str_pad(substr($alpha_num, -3), 3, "_", STR_PAD_RIGHT);

        $hash_string = $prefix . '.' . hash('sha256', $hash_string) . '.' . $suffix;

        return $hash_string;
    }

    private function getTranslateFor()
    {
        $translate_fields = (array) $this->getOption('translate_fields', []);

        $translate_for = [];
        foreach ((array) $this->getOption('translate_for', []) as $resource => $status) {
            if (!$status) {
                continue;
            }

            if (in_array($resource, ['categories', 'products'])) {
                $status = array_filter($translate_fields[$resource] ?? []);
            }

            if ($status) {
                $translate_for[$resource] = 1;
            }
        }

        return $translate_for;
    }

    private function getAiRequestPayloadLength($content)
    {
        $payload_length = 0;
        foreach ($content as $key => $val) {
            if (in_array($key, ['name', 'description'])) {
                $payload_length += strlen($val);
            }
        }

        return $payload_length;
    }

    public function getDefaultLanguageId()
    {
        return $this->model->getDefaultLanguageId();
    }

    public function getModel()
    {
        return $this->model;
    }
}
