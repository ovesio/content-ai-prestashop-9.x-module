<?php

namespace PrestaShop\Module\Ovesio\Controller\Admin;

use Configuration;
use Context;
use DateTime;
use Exception;
use OvesioModel;
use PrestaShop\Module\Ovesio\Support\TplSupport;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\HttpFoundation\Response;
use Tools;

class ActivityListController extends PrestaShopAdminController
{
    use TplSupport;

    public const TAB_CLASS_NAME = 'AdminOvesioActivityList';

    /**
     * Module instance resolved by PrestaShop
     *
     * @var Ovesio
     */
    public $module;

    /**
     * Module Admin model instance
     *
     * @var OvesioModel
     */
    protected $model;

    /**
     * Module name to lower
     *
     * @var string
     */
    private $module_key = 'ovesio';

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('ovesio');
        $this->model = new OvesioModel();
    }

    public function index(): Response
    {
        $data = $this->getLoadLanguages();

        $page = Tools::getValue('page', 1);
        $page = max($page, 1);
        $limit = 20;

        $filters['page']  = $page;
        $filters['limit'] = $limit;
        $filters['resource_name'] = Tools::getValue('resource_name', '');
        $filters['resource_type'] = Tools::getValue('resource_type', '');
        $filters['resource_id']   = Tools::getValue('resource_id', '');
        $filters['status']        = Tools::getValue('status', '');
        $filters['activity_type'] = Tools::getValue('activity_type', '');
        $filters['language']      = Tools::getValue('language', '');
        $filters['date']          = Tools::getValue('date', '');
        $filters['date_from']     = Tools::getValue('date_from', '');
        $filters['date_to']       = Tools::getValue('date_to', '');

        $project  = explode(':', Configuration::get('OVESIO_API_TOKEN'));
        $project = $project[0];

        // get domain from api url
        $base_url   = Configuration::get('OVESIO_API_URL');
        $parsed_url = parse_url($base_url);
        $base_url   = $parsed_url['scheme'] . '://' . str_replace('api.', 'app.', $parsed_url['host']);

        $base_url .= "/account/$project";

        $activities = $this->model->getActivities($filters);
        $activities_total = $this->model->getActivitiesTotal($filters);

        $data = array_merge($data, $filters);

        $data['activities'] = [];
        $data['total']      = $activities_total;

        // Map resource types to display text and badge classes
        $resource_types = [
            'product'      => ['text' => $this->module->l('text_product'), 'class' => 'ov-badge-primary'],
            'category'     => ['text' => $this->module->l('text_category'), 'class' => 'ov-badge-info'],
            'manufacturer' => ['text' => $this->module->l('text_manufacturers'), 'class' => 'ov-badge-warning'],
            'information'  => ['text' => $this->module->l('text_information'), 'class' => 'ov-badge-secondary']
        ];

        // Map activity types to display text and badge classes
        $activity_types = [
            'generate_content' => ['text' => $this->module->l('activity_generate_content'), 'class' => 'ov-badge-info', 'url_pattern' => $base_url . '/ai/generate_descriptions/%s'],
            'generate_seo'     => ['text' => $this->module->l('activity_generate_seo'), 'class' => 'ov-badge-warning', 'url_pattern' => $base_url . '/ai/generate_seo/%s'],
            'translate'        => ['text' => $this->module->l('activity_translate'), 'class' => 'ov-badge-success', 'url_pattern' => $base_url . '/app/translate_requests/%s']
        ];

        // Map status to display text and badge classes
        $status_types = [
            'started'   => ['text' => $this->module->l('text_processing'), 'class' => 'ov-status-info'],
            'completed' => ['text' => $this->module->l('text_completed'), 'class' => 'ov-status-success'],
            'skipped'   => ['text' => $this->module->l('text_skipped'), 'class' => 'ov-status-warning'],
            'error'     => ['text' => $this->module->l('text_error'), 'class' => 'ov-status-danger']
        ];

        $language_options = [];

        $ovesio_languages = $this->getOvesioLanguages();

        $system_languages = \Language::getLanguages();
        $ovesio_languages = $ovesio_languages ?: array_column($activities, 'lang');

        $languages_info = [];
        foreach ($system_languages as $language) {
            foreach ($ovesio_languages as $ol) {
                if (stripos($language['iso_code'], $ol) === 0) {
                    $languages_info[$ol] = $language;
                    $language_options[$ol] = $language['name'];
                    break;
                }
            }
        }

        $data['language_options'] = $language_options;
        $data['activity_types']   = $activity_types;
        $data['status_types']     = $status_types;
        $data['resource_types']   = $resource_types;

        $context = Context::getContext();

        foreach ($activities as $activity) {
            // Get display values with fallbacks
            $resource_info = isset($resource_types[$activity['resource_type']]) ?
                $resource_types[$activity['resource_type']] :
                ['text' => ucfirst($activity['resource_type']), 'class' => 'ov-badge-secondary'];

            $activity_info = isset($activity_types[$activity['activity_type']]) ?
                $activity_types[$activity['activity_type']] :
                ['text' => ucfirst($activity['activity_type']), 'class' => 'ov-badge-secondary'];

            $status_info = isset($status_types[$activity['status']]) ?
                $status_types[$activity['status']] :
                ['text' => ucfirst($activity['status']), 'class' => 'ov-status-secondary'];

            // Calculate time ago
            $updated_time = new DateTime($activity['updated_at']);
            $now = new DateTime();
            $diff = $now->diff($updated_time);

            if ($diff->days > 0) {
                if ($diff->days > 7) {
                    $time_ago = $updated_time->format('d-m-Y H:i');
                } else {
                    $time_ago = $diff->days . ' ' . ($diff->days == 1 ? 'day' : 'days') . ' ago';
                }
            } elseif ($diff->h > 0) {
                $time_ago = $diff->h . ' ' . ($diff->h == 1 ? 'hour' : 'hours') . ' ago';
            } elseif ($diff->i > 0) {
                $time_ago = $diff->i . ' ' . $this->module->l('text_minutes_ago');
            } else {
                $time_ago = 'Just now';
            }

            // Add formatted data to activity
            $activity['resource_display']      = $resource_info;
            $activity['activity_display']      = $activity_info;
            $activity['status_display']        = $status_info;
            $activity['time_ago']              = $time_ago;
            $activity['lang_upper']            = strtoupper($activity['lang']);
            $activity['language_name']         = isset($languages_info[$activity['lang']]) ? $languages_info[$activity['lang']]['name'] : $activity['lang'];
            $activity['language_flag']         = isset($languages_info[$activity['lang']]) ? _MODULE_DIR_ . 'ovesio/views/img/flags/' . $languages_info[$activity['lang']]['iso_code'] . '.png' : '';
            $activity['resource_name_escaped'] = htmlspecialchars($activity['resource_name'], ENT_QUOTES, 'UTF-8');

            if ($activity['activity_id']) {
                $activity['activity_url'] = sprintf($activity_info['url_pattern'], $activity['activity_id']);
            } else {
                $activity['activity_url'] = '';
            }

            // resource url
            if ($activity['resource_type'] == 'product') {
                $activity['resource_url'] = $context->link->getAdminLink('AdminProducts') . '&id_product=' . $activity['resource_id'] . '&updateproduct';
            } elseif ($activity['resource_type'] == 'category') {
                $activity['resource_url'] = $context->link->getAdminLink('AdminCategories') . '&id_category=' . $activity['resource_id'] . '&updatecategory';
            } else {
                $activity['resource_url'] = '';
            }

            $data['activities'][] = $activity;
        }

        $context = Context::getContext();
        $hash = Configuration::get('OVESIO_HASH');
        $data['url_settings']      = $this->generateUrl('admin_ovesio_configure');
        $data['url_update_status'] = $context->link->getModuleLink('ovesio', 'callback', ['hash' => $hash, 'action' => 'updateActivityStatus'], true);

        // Add URLs for AJAX modal calls
        $data['url_view_request'] = $this->generateUrl('admin_ovesio_activity_view_request');
        $data['url_view_response'] = $this->generateUrl('admin_ovesio_activity_view_response');

        $content = $this->renderTemplate('ovesio_activity_list.tpl', $data);

        return $this->render('@Modules/ovesio/views/templates/admin/layout.html.twig', [
            'content' => $content,
            'layoutTitle' => $this->module->l('text_activity_list'),
        ]);
    }

    public function viewRequest(): Response
    {
        $activity_id = Tools::getValue('activity_id');

        $activity = $this->model->getActivity($activity_id);

        if ($activity['request']) {
            $request = json_decode($activity['request'], true);
        } else {
            $request = '';
        }

        $html = '<pre class="ov-well">' . htmlspecialchars(json_encode($request, JSON_PRETTY_PRINT)) . '</pre>';

        return new Response($html);
    }

    public function viewResponse(): Response
    {
        $activity_id = Tools::getValue('activity_id');

        $activity = $this->model->getActivity($activity_id);

        if ($activity['response']) {
            $response = json_decode($activity['response'], true);
        } else {
            $response = '';
        }

        $html = '<pre class="ov-well">' . htmlspecialchars(json_encode($response, JSON_PRETTY_PRINT)) . '</pre>';

        return new Response($html);
    }

    private function getLoadLanguages()
    {
        // Load languages
        foreach ($this->module->getKeyValueLanguage() as $key => $value) {
            if (strpos($key, 'error_') === 0) continue;
            $data[$key] = $this->module->l($key);
        }

        return $data;
    }

    private function getOvesioLanguages()
    {
        $client = $this->buildClient();
        // Caching could be implemented here using PrestaShop Cache
        try {
            $response = $client->languages()->list();
            $response = json_decode(json_encode($response), true);
            return !empty($response['data']) ? array_column($response['data'], 'code') : [];
        } catch (Exception $e) {
            return [];
        }
    }

    private function buildClient($api_url = null, $api_token = null)
    {
        $api_url = $api_url ?: Configuration::get('OVESIO_API_URL');
        $api_token = $api_token ?: Configuration::get('OVESIO_API_TOKEN');

        return new \Ovesio\OvesioAI($api_token, $api_url);
    }
}
