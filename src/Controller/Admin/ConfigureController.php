<?php

namespace PrestaShop\Module\Ovesio\Controller\Admin;

use Configuration;
use Context;
use Exception;
use Ovesio\OvesioAI;
use OvesioModel;
use PrestaShop\Module\Ovesio\Support\OvesioConfiguration;
use PrestaShop\Module\Ovesio\Support\TplSupport;
use PrestaShopBundle\Controller\Admin\PrestaShopAdminController;
use Symfony\Component\HttpFoundation\Response;
use Tools;

class ConfigureController extends PrestaShopAdminController
{
    use TplSupport;

    public const TAB_CLASS_NAME = 'AdminOvesioConfigure';
    /**
     * Module instance resolved by PrestaShop
     *
     * @var Ovesio
     */
    public $module;

    /**
     * Module Admin model instance resolve in controller, loaded by autoloader, per scope (admin/front)
     *
     * @var OvesioModel
     */
    protected $model;

    /**
     * Config cross platform compatible
     */
    private $config;

    /**
     * Module name to lower
     *
     * @var string
     */
    private $module_key = 'ovesio';

    public function __construct()
    {
        $this->module = \Module::getInstanceByName('ovesio');
        $this->config = OvesioConfiguration::getAll('ovesio');
        $this->model = new OvesioModel();
    }

    public function index()
    {
        $data = $this->getLoadLanguages();

        $data['module_dir'] = _MODULE_DIR_ . 'ovesio/';

        $settings = $this->config->all();
        foreach ($settings as $key => $value) {
            $key = strtolower(str_ireplace($this->module_key . '_', '', $key));
            $data[$key] = $value;
        }

        $data['url_connect']    = $this->generateUrl('admin_ovesio_connect');
        $data['url_disconnect'] = $this->generateUrl('admin_ovesio_disconnect');
        $data['url_list']       = $this->generateUrl('admin_ovesio_activity_list');

        $hash = $this->config->get($this->module_key . '_hash');
        $context = Context::getContext();

        // Generate module URLs using link helper
        $data['url_callback']     = $context->link->getModuleLink('ovesio', 'callback', ['hash' => $hash], true);
        $data['url_cron']         = $context->link->getModuleLink('ovesio', 'cronjob', ['hash' => $hash], true);        $client = $this->buildClient();
        $data['default_language'] = $this->config->get($this->module_key . '_default_language');

        $data['errors'] = [];
        try {
            $client->languages()->list();
        } catch (Exception $e) {
            if ($this->config->get($this->module_key . '_api_token')) {
                $data['errors'][] = $e->getMessage();
            }
        }

        $data['api_url']   = $this->config->get($this->module_key . '_api_url');
        $data['api_token'] = $this->config->get($this->module_key . '_api_token');

        if ($this->config->get($this->module_key . '_status')) {
            $data['connected'] = true;
        } else {
            $data['connected'] = false;
        }

        $data['count_errors']    = $this->model->getActivitiesTotal(['status' => 'error']);
        $data['url_list_errors'] = $this->generateUrl('admin_ovesio_activity_list', ['status' => 'error']);

        // Render sub-templates
        $data['connect_form']          = $this->renderTemplate('ovesio_connect_form.tpl', $data);
        $data['generate_content_card'] = $this->generateContentCard(true);
        $data['generate_seo_card']     = $this->generateSeoCard(true);
        $data['translate_card']        = $this->translateCard(true);

        $content = $this->renderTemplate('ovesio.tpl', $data);

        return $this->render('@Modules/ovesio/views/templates/admin/layout.html.twig', [
            'content' => $content,
        ]);
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

    public function connect()
    {
        $api_url          = Tools::getValue('api_url');
        $api_token        = Tools::getValue('api_token');
        $default_language = Tools::getValue('default_language');

        if (!$default_language) { // step 1
            $settings[$this->module_key . '_' . 'api_url']   = $api_url;
            $settings[$this->module_key . '_' . 'api_token'] = $api_token;
        } else { // step 2
            $settings[$this->module_key . '_' . 'default_language'] = $default_language;
            $settings[$this->module_key . '_' . 'status']           = 1;
        }

         // Save configuration
        foreach ($settings as $key => $value) {
            Configuration::updateValue(strtoupper($key), $value);
        }

        $json = [];
        if (!$default_language) { // step 1
            $client = $this->buildClient($api_url, $api_token);

            try {

                $response = $client->languages()->list();

                $json = [
                    'success'   => true,
                    'message'   => $this->module->l('text_connection_valid'),
                    'languages' => $response->data,
                ];
            } catch (Exception $e) {
                $json = [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        } else {
            $json = [
                'success'   => true,
                'connected' => true,
                'message'   => $this->module->l('text_connection_success'),
            ];
        }

        return $this->jsonResponse($json);
    }

    public function disconnect()
    {
        Configuration::updateValue(strtoupper($this->module_key . '_status'), 0);

        $this->jsonResponse([
            'success' => true,
            'message' => $this->module->l('text_disconnection_success')
        ]);
    }

    public function generateContentCard($return = false)
    {
        $data = $this->getLoadLanguages();

        $data['generate_content_status']                  = $this->config->get($this->module_key . '_generate_content_status');
        $data['generate_content_for']                     = array_filter((array)$this->config->get($this->module_key . '_generate_content_for'));
        $data['generate_content_when_description_length'] = array_filter((array)$this->config->get($this->module_key . '_generate_content_when_description_length'));
        $data['generate_content_include_disabled']        = array_filter((array)$this->config->get($this->module_key . '_generate_content_include_disabled'));
        $data['generate_content_include_stock_0']         = $this->config->get($this->module_key . '_generate_content_include_stock_0');
        $data['generate_content_live_update']             = $this->config->get($this->module_key . '_generate_content_live_update');
        $data['generate_content_workflow']                = $this->config->get($this->module_key . '_generate_content_workflow');
        $data['workflows']                                = $this->getWorkflows('generate_content');

        $data['url_edit'] = $this->generateUrl('admin_ovesio_generate_content_form');

        $data['generate_content_sumary'] = [];
        foreach ($data['generate_content_for'] as $resource => $value) {
            $data['generate_content_sumary'][$resource] = trim(sprintf(
                $this->module->l('text_generate_content_sumary'),
                $this->module->l('text_' . $resource),
                !empty($data['generate_content_include_disabled'][$resource]) ? $this->module->l('text_including_disabled') : $this->module->l('text_excluding_disabled'),
                $data['generate_content_when_description_length'][$resource]
            ), ':');
        }

        $html = $this->renderTemplate('ovesio_generate_content_card.tpl', $data);

        if ($return) {
            return $html;
        }

        $this->jsonResponse(['html' => $html]);
    }

    public function generateContentForm()
    {
        $data = $this->getLoadLanguages();

        $settings = $this->config->all();
        foreach ($settings as $key => $value) {
            $key = strtolower(str_ireplace($this->module_key . '_', '', $key));
            $data[$key] = $value;
        }

        $data['resources_list'] = [
            'products'   => $this->module->l('text_products'),
            'categories' => $this->module->l('text_categories'),
        ];

        $data['workflows_list'] = $this->getWorkflows('generate_description');
$data['generate_content_workflow'] = '';
        $data['action'] = $this->generateUrl('admin_ovesio_generate_content_save');

        if ($data['workflows_list']) {
            $html = $this->renderTemplate('ovesio_generate_content_form.tpl', $data);
        } else {
            $html = $this->module->l('text_api_error');
        }

        return new Response($html);
    }

    public function generateContentFormSave()
    {
        $post = Tools::getAllValues();

        if (!isset($post['generate_content_when_description_length'])) {
            $post['generate_content_when_description_length'] = [];
        }

        if (!empty($post['generate_content_workflow'])) {
            $temp = explode('@', $post['generate_content_workflow']);
            $post['generate_content_workflow'] = [
                'id'   => $temp[0],
                'name' => isset($temp[1]) ? $temp[1] : '',
            ];
        }

        $errors = [];
        foreach ($post['generate_content_when_description_length'] as $key => $value) {
            if (empty($value) || !is_numeric($value) || $value < 0) {
                $errors['generate_content_when_description_length.' . $key] = $this->module->l('error_invalid_number');
            }
        }

        if ($errors) {
            return new Response(json_encode([
                'success' => false,
                'errors'  => $errors,
            ]), 422);
        }

        // Filter and save configuration
        foreach ($post as $key => $value) {
            if (strpos($key, 'generate_content_') === 0) {
                $key = $this->module_key . '_' . $key;

                if (is_array($value)) {
                    $value = json_encode($value);
                }
                Configuration::updateValue(strtoupper($key), $value);
            }
        }

        $this->config = OvesioConfiguration::getAll('ovesio');

        $json = [
            'success'   => true,
            'message'   => $this->module->l('text_settings_saved'),
            'card_html' => $this->generateContentCard(true),
        ];

        $this->jsonResponse($json);
    }

    public function generateSeoCard($return = false)
    {
        $data = $this->getLoadLanguages();

        $data['generate_seo_status']                  = $this->config->get($this->module_key . '_generate_seo_status');
        $data['generate_seo_for']                     = array_filter((array)$this->config->get($this->module_key . '_generate_seo_for'));
        $data['generate_seo_include_disabled']        = array_filter((array)$this->config->get($this->module_key . '_generate_seo_include_disabled'));
        $data['generate_seo_include_stock_0']         = $this->config->get($this->module_key . '_generate_seo_include_stock_0');
        $data['generate_seo_live_update']             = $this->config->get($this->module_key . '_generate_seo_live_update');
        $data['generate_seo_workflow']                = $this->config->get($this->module_key . '_generate_seo_workflow');

        $data['url_edit'] = $this->generateUrl('admin_ovesio_generate_seo_form');


        $data['generate_seo_sumary'] = [];
        foreach ($data['generate_seo_for'] as $resource => $value) {
            $data['generate_seo_sumary'][$resource] = trim(sprintf(
                $this->module->l('text_generate_seo_sumary'),
                $this->module->l('text_' . $resource),
                !empty($data['generate_seo_include_disabled'][$resource]) ? $this->module->l('text_including_disabled') : $this->module->l('text_excluding_disabled'),
            ), ':');
        }

        $html = $this->renderTemplate('ovesio_generate_seo_card.tpl', $data);

        if ($return) {
            return $html;
        }

        $this->jsonResponse(['html' => $html]);
    }

    public function generateSeoForm()
    {
        $data = $this->getLoadLanguages();

        $settings = $this->config->all();
        foreach ($settings as $key => $value) {
            $key = strtolower(str_ireplace($this->module_key . '_', '', $key));
            $data[$key] = $value;
        }

        $data['resources_list'] = [
            'products'   => $this->module->l('text_products'),
            'categories' => $this->module->l('text_categories'),
        ];

        $data['workflows_list'] = $this->getWorkflows('generate_seo');

        $data['action'] = $this->generateUrl('admin_ovesio_generate_seo_save');

        if ($data['workflows_list']) {
            $html = $this->renderTemplate('ovesio_generate_seo_form.tpl', $data);
        } else {
            $html = $this->module->l('text_api_error');
        }

        return new Response($html);
    }

    public function generateSeoFormSave()
    {
        $post = Tools::getAllValues();

        if (!empty($post['generate_seo_workflow'])) {
            $temp = explode('@', $post['generate_seo_workflow']);
            $post['generate_seo_workflow'] = [
                'id'   => $temp[0],
                'name' => isset($temp[1]) ? $temp[1] : '',
            ];
        }

        $errors = [];

        if ($errors) {
            return new Response(json_encode([
                'success' => false,
                'errors'  => $errors,
            ]), 422);
        }

        // Filter and save configuration
        foreach ($post as $key => $value) {
            if (strpos($key, 'generate_seo_') === 0) {
                $key = $this->module_key . '_' . $key;

                if (is_array($value)) {
                    $value = json_encode($value);
                }
                Configuration::updateValue(strtoupper($key), $value);
            }
        }

        $this->config = OvesioConfiguration::getAll('ovesio');

        $json = [
            'success'   => true,
            'message'   => $this->module->l('text_settings_saved'),
            'card_html' => $this->generateSeoCard(true),
        ];

        $this->jsonResponse($json);
    }

    public function translateCard($return = false)
    {
        $data = $this->getLoadLanguages();

        $data['translate_status']           = $this->config->get($this->module_key . '_translate_status');
        $data['translate_include_disabled'] = array_filter((array)$this->config->get($this->module_key . '_translate_include_disabled'));
        $data['translate_include_stock_0']  = $this->config->get($this->module_key . '_translate_include_stock_0');
        $data['translate_live_update']      = $this->config->get($this->module_key . '_translate_live_update');
        $data['translate_workflow']         = $this->config->get($this->module_key . '_translate_workflow');
        $data['translate_fields']           = array_filter((array)$this->config->get($this->module_key . '_translate_fields'));
        $data['language_settings']          = array_filter((array)$this->config->get($this->module_key . '_language_settings'));

        $translate_for = $this->config->get($this->module_key . '_translate_for');
        $data['translate_for'] = [];

        foreach ($data['translate_fields'] as $resource => $fields) {
            if (empty($translate_for[$resource])) {
                continue;
            }

            $fields = implode(', ', array_keys(array_filter($fields)));

            if ($fields) {
                $data['translate_for'][$resource] = $fields ? 1 : 0;
                $data['translate_fields'][$resource] = $fields;
            }
        }

        $system_languages = \Language::getLanguages();

        $languages = array_column($system_languages, null, 'id_lang');

        foreach ($data['language_settings'] as $language_id => $setting) {
            if (!isset($languages[$language_id])) { // lang local sters
                unset($data['language_settings'][$language_id]);
                continue;
            } else {
                $language = $languages[$language_id];
                $language_code = strtolower(substr($language['iso_code'], 0, 2));
            }
            $data['language_settings'][$language_id]['name'] = $language['name'];
            $data['language_settings'][$language_id]['flag'] = _MODULE_DIR_ . 'ovesio/views/img/flags/' . $language_code . '.png';
        }

        $data['url_edit'] = $this->generateUrl('admin_ovesio_translate_form');

        $data['translate_sumary'] = [];
        foreach ($data['translate_for'] as $resource => $value) {
            $data['translate_sumary'][$resource] = trim(sprintf(
                $this->module->l('text_translate_sumary'),
                $this->module->l('text_' . $resource),
                !empty($data['translate_include_disabled'][$resource]) ? $this->module->l('text_including_disabled') : $this->module->l('text_excluding_disabled'),
                $data['translate_fields'][$resource]
            ), ':');
        }

        if (!empty($translate_for['attributes'])) {
            $data['translate_for']['attributes']    = 1;
            $data['translate_sumary']['attributes'] = $this->module->l('text_attributes');
        }

        if (!empty($translate_for['features'])) {
            $data['translate_for']['features']    = 1;
            $data['translate_sumary']['features'] = $this->module->l('text_features');
        }

        $html = $this->renderTemplate('ovesio_translate_card.tpl', $data);

        if ($return) {
            return $html;
        }

        $this->jsonResponse(['html' => $html]);
    }

    public function translateForm()
    {
        $data = $this->getLoadLanguages();

        $settings = $this->config->all();
        foreach ($settings as $key => $value) {
            $key = strtolower(str_ireplace($this->module_key . '_', '', $key));
            $data[$key] = $value;
        }

        $default_language = $data['default_language'];

        $data['language_settings'] = array_filter((array)$this->config->get($this->module_key . '_language_settings'));

        $data['resources_list'] = [
            'products'   => $this->module->l('text_products'),
            'categories' => $this->module->l('text_categories'),
            'attributes' => $this->module->l('text_attributes'),
            'features'   => $this->module->l('text_features'),
        ];

        $data['translate_fields_schema']['products'] = [
            'name'             => $this->module->l('text_name'),
            'description'      => $this->module->l('text_description'),
            'meta_title'       => $this->module->l('text_meta_title'),
            'meta_description' => $this->module->l('text_meta_description'),
            'meta_keyword'     => $this->module->l('text_meta_keyword'),
        ];

        $data['translate_fields_schema']['categories'] = [
            'name'             => $this->module->l('text_name'),
            'description'      => $this->module->l('text_description'),
            'meta_title'       => $this->module->l('text_meta_title'),
            'meta_description' => $this->module->l('text_meta_description'),
            'meta_keyword'     => $this->module->l('text_meta_keyword'),
        ];

        $data['translate_fields_schema']['attributes'] = [
            'name' => $this->module->l('text_name'),
        ];

        $data['translate_fields_schema']['features'] = [
            'name' => $this->module->l('text_name'),
        ];

        $system_languages = \Language::getLanguages();

        usort($system_languages, function ($a, $b) {
            return strcmp($a['name'], $b['name']);
        });

        $data['system_languages'] = array_column($system_languages, null, 'id_lang');

        $client = $this->buildClient();

        $response = $client->languages()->list();
        $response = json_decode(json_encode($response), true);

        $ovesio_languages = [];
        if (!empty($response['data'])) {
            $ovesio_languages = $response['data'];
            $ovesio_languages = array_column($ovesio_languages, null, 'code');
        }

        $data['ovesio_languages'] = $ovesio_languages;

        foreach ($system_languages as $language) {
            $language_id = $language['id_lang'];
            $local_code = strtolower(substr($language['iso_code'], 0, 2));

            $ovesio_code = null;
            if (isset($ovesio_languages[$local_code])) {
                $ovesio_code = $local_code;
            }

            if ($ovesio_code == $default_language) {
                unset($data['system_languages'][$language_id]);
                continue;
            }

            if (empty($data['language_settings'][$language_id])) {
                $data['language_settings'][$language_id] = [
                    'name'           => $language['name'],
                    'code'           => $ovesio_code,
                    'translate'      => 0,
                    'translate_from' => $default_language,
                ];
            }
        }

        $data['workflows_list'] = $this->getWorkflows('translate');

        $data['action'] = $this->generateUrl('admin_ovesio_translate_save');

        if ($data['workflows_list'] && $data['ovesio_languages']) {
            $html = $this->renderTemplate('ovesio_translate_form.tpl', $data);
        } else {
            $html = $this->module->l('text_api_error');
        }

        return new Response($html);
    }

    public function translateFormSave()
    {
        $post = Tools::getAllValues();

        if (!empty($post['translate_workflow'])) {
            $temp = explode('@', $post['translate_workflow']);
            $post['translate_workflow'] = [
                'id'   => $temp[0],
                'name' => isset($temp[1]) ? $temp[1] : '',
            ];
        }

        $default_language = $this->config->get($this->module_key . '_default_language');

        $errors = [];

        if (!isset($post['language_settings'])) {
            $post['language_settings'] = [];
        }

        foreach ($post['language_settings'] as $key => $lang) {
            if (empty($lang['code'])) {
                $errors['language_settings.' . $key . '.code'] = $this->module->l('error_code');
            }

            if ($lang['code'] == $lang['translate_from']) {
                $errors['language_settings.' . $key . '.translate_from'] = $this->module->l('error_from_language');
            }

            $translate_from_id = '';
            foreach ($post['language_settings'] as $k => $l) {
                if ($l['code'] == $lang['translate_from']) {
                    $translate_from_id = $k;
                    break;
                }
            }

            if (empty($post['language_settings'][$translate_from_id]['translate'])) {
                if ($default_language != $lang['translate_from']) {
                    $errors['language_settings.' . $key . '.translate_from'] = $this->module->l('error_from_language1');
                }
            }
        }

        if ($errors) {
            return new Response(json_encode([
                'success' => false,
                'errors'  => $errors,
            ]), 422);
        }

        // Filter and save configuration
        foreach ($post as $key => $value) {
            if (strpos($key, 'translate_') === 0 || $key === 'language_settings') {
                $key = $this->module_key . '_' . $key;

                if (is_array($value)) {
                    $value = json_encode($value);
                }
                Configuration::updateValue(strtoupper($key), $value);
            }
        }

        $this->config = OvesioConfiguration::getAll('ovesio');

        $json = [
            'success'   => true,
            'message'   => $this->module->l('text_settings_saved'),
            'card_html' => $this->translateCard(true),
        ];

        $this->jsonResponse($json);
    }

    private function buildClient($api_url = null, $api_token = null)
    {
        $api_url = $api_url ?: Configuration::get('OVESIO_API_URL');
        $api_token = $api_token ?: Configuration::get('OVESIO_API_TOKEN');

        return new OvesioAI($api_token, $api_url);
    }

    private function getWorkflows($type = null)
    {
        $client = $this->buildClient();

        try {
            $response = $client->workflows()->list();
        } catch (Exception $e) {
            return [];
        }

        $workflows = json_decode(json_encode($response->data), true);

        if ($type) {
            $workflows = array_filter($workflows, function($workflow) use ($type) {
                return $workflow['type'] == $type;
            });
        }

        return $workflows;
    }

    private function getResourceTypeFromRoute($route)
    {
        if (strpos($route, 'product') !== false || strpos($route, 'Product') !== false) {
            return 'product';
        }
        if (strpos($route, 'category') !== false || strpos($route, 'Category') !== false) {
            return 'category';
        }
        return null;
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

    /**
     * Helper method to send JSON response and exit
     */
    private function jsonResponse($data)
    {
        header('Content-Type: application/json');
        die(json_encode($data));
    }
}
