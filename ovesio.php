<?php
if (!defined('_PS_VERSION_')) {
    exit;
}

require_once __DIR__ . '/autoload.php';
require_once __DIR__ . '/vendor/autoload.php';

use Ovesio\OvesioAI;
use Ovesio\QueueHandler;
use PrestaShop\Module\Ovesio\Controller\Admin\ConfigureController;
use PrestaShop\Module\Ovesio\Controller\Admin\ManualController;
use PrestaShop\Module\Ovesio\Support\OvesioConfiguration;
use PrestaShop\Module\Ovesio\Support\OvesioLog;

class Ovesio extends Module
{
    private $keyValueLanguage = [];

    public function __construct()
    {
        $this->name = 'ovesio';
        $this->tab = 'administration';
        $this->version = '1.2';
        $this->author = 'Aweb Design';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '9.0.0', 'max' => '9.99.99'];

        parent::__construct();

        $this->displayName = $this->l('Ovesio AI');
        $this->description = $this->l('AI Content Generation for PrestaShop');

        // Load language strings
        $this->loadKeyValueLanguages();

        // Define tabs
        $tabNames = [];
        foreach (Language::getLanguages(true) as $lang) {
            $tabNames[$lang['locale']] = $this->l('Ovesio AI');
        }

        $this->tabs = [
            [
                'route_name' => 'admin_ovesio_configure',
                'class_name' => ConfigureController::TAB_CLASS_NAME,
                'visible' => true,
                'name' => $tabNames,
                'icon' => 'science',
                'parent_class_name' => 'AdminCatalog',
            ],
        ];
    }

    protected function loadKeyValueLanguages()
    {
        $langFile = __DIR__ . '/languages/en/ovesio.php';

        $_ = include $langFile;
        $this->keyValueLanguage = $_;
    }

    public function getKeyValueLanguage()
    {
        return $this->keyValueLanguage;
    }

    /**
     * @override - ?reason = PrestaShop does not support module translation files in key-value format, but we have our language files in that format and would be difficult to track cross-platforms (eg. Opencart/Wordpress/Prestashop) differences them in time
     */
    public function l($string, $specific = false, $locale = null)
    {
        // First check if it's a key in our OpenCart-style language array
        if (isset($this->keyValueLanguage[$string])) {
            return $this->keyValueLanguage[$string];
        }

        // Otherwise use parent's l() method
        return parent::l($string, $specific, $locale);
    }

    public function getContent()
    {
        Tools::redirectAdmin(
            $this->context->link->getAdminLink(ConfigureController::TAB_CLASS_NAME)
        );
    }

    public function install()
    {
        $model = new OvesioModel();

        return parent::install() &&
            $model->install() &&
            $model->installConfig() &&
            $this->registerHook('moduleRoutes') &&
            $this->registerHook('actionObjectProductUpdateAfter') &&
            $this->registerHook('actionObjectCategoryUpdateAfter') &&
            $this->registerHook('actionObjectFeatureUpdateAfter') &&
            $this->registerHook('actionObjectFeatureValueAddAfter') &&
            $this->registerHook('actionObjectFeatureValueUpdateAfter') &&
            $this->registerHook('actionObjectAttributeGroupUpdateAfter') &&
            $this->registerHook('actionObjectProductAttributeAddAfter') &&
            $this->registerHook('actionObjectProductAttributeUpdateAfter') &&
            $this->registerHook('displayDashboardToolbarTopMenu') &&
            $this->registerHook('actionAdminControllerSetMedia');
    }

    public function hookModuleRoutes()
    {
        return [
            'module-ovesio-callback' => [
                'controller' => 'callback',
                'rule' => 'ovesio/callback',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'ovesio',
                    'controller' => 'callback',
                ],
            ],
            'module-ovesio-cron' => [
                'controller' => 'cron',
                'rule' => 'ovesio/cron',
                'keywords' => [],
                'params' => [
                    'fc' => 'module',
                    'module' => 'ovesio',
                    'controller' => 'cron',
                ],
            ],
        ];
    }

    public function uninstall()
    {
        $model = new OvesioModel();

        return $model->uninstall() &&
            parent::uninstall();
    }

    /**
     * Hook called after a product is added
     */
    public function hookActionObjectProductAddAfter($params)
    {
        $this->markResourceAsStale('product', $params['object']->id);
    }

    /**
     * Hook called after a product is updated
     */
    public function hookActionObjectProductUpdateAfter($params)
    {
        $this->markResourceAsStale('product', $params['object']->id);
    }

    /**
     * Hook called after a category is added
     */
    public function hookActionObjectCategoryAddAfter($params)
    {
        $this->markResourceAsStale('category', $params['object']->id);
    }

    /**
     * Hook called after a category is updated
     */
    public function hookActionObjectCategoryUpdateAfter($params)
    {
        $this->markResourceAsStale('category', $params['object']->id);
    }

    /**
     * Hook called after a feature is added
     */
    public function hookActionObjectFeatureAddAfter($params)
    {
        $this->markResourceAsStale('feature', $params['object']->id);
    }

    /**
     * Hook called after a feature is updated
     */
    public function hookActionObjectFeatureUpdateAfter($params)
    {
        $this->markResourceAsStale('feature', $params['object']->id);
    }

    /**
     * Hook called after a feature value is added
     */
    public function hookActionObjectFeatureValueAddAfter($params)
    {
        // Mark the parent feature as stale
        $this->markResourceAsStale('feature', $params['object']->id_feature);
    }

    /**
     * Hook called after a feature value is updated
     */
    public function hookActionObjectFeatureValueUpdateAfter($params)
    {
        // Mark the parent feature as stale
        $this->markResourceAsStale('feature', $params['object']->id_feature);
    }

    /**
     * Hook called after an attribute group is added
     */
    public function hookActionObjectAttributeGroupAddAfter($params)
    {
        $this->markResourceAsStale('attribute_group', $params['object']->id);
    }

    /**
     * Hook called after an attribute group is updated
     */
    public function hookActionObjectAttributeGroupUpdateAfter($params)
    {
        $this->markResourceAsStale('attribute_group', $params['object']->id);
    }

    /**
     * Hook called after an attribute is added
     */
    public function hookActionObjectProductAttributeAddAfter($params)
    {
        // Mark the parent attribute group as stale
        $this->markResourceAsStale('attribute_group', $params['object']->id_attribute_group);
    }

    /**
     * Hook called after an attribute is updated
     */
    public function hookActionObjectProductAttributeUpdateAfter($params)
    {
        // Mark the parent attribute group as stale
        $this->markResourceAsStale('attribute_group', $params['object']->id_attribute_group);
    }

    /**
     * Mark a resource as stale in the queue
     */
    private function markResourceAsStale($resource_type, $resource_id)
    {
        if (empty($resource_id)) {
            return;
        }

        // Update all activities for this resource to be stale
        Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "ovesio_activity
            SET stale = 1
            WHERE resource_type = '" . pSQL($resource_type) . "'
            AND resource_id = '" . (int)$resource_id . "'"
        );
    }

    /**
     * Hook to add custom buttons in the admin toolbar
     * This hook is called in the toolbar area on various admin pages
     */
    /**
     * Get toolbar buttons configuration
     */
    private function getToolbarButtons()
    {
        $action = Tools::getValue('action');
        if ($action && stripos($action, 'view') === false) {
            return [];
        }

        $ovesio_status                  = \Configuration::get('OVESIO_STATUS');
        $ovesio_generate_content_status = \Configuration::get('OVESIO_GENERATE_CONTENT_STATUS');
        $ovesio_generate_seo_status     = \Configuration::get('OVESIO_GENERATE_SEO_STATUS');
        $ovesio_translate_status        = \Configuration::get('OVESIO_TRANSLATE_STATUS');

        $ovesio_route_resource = [
            'AdminProducts'         => 'products',
            'AdminCategories'       => 'categories',
            'AdminAttributesGroups' => 'attributes',
            'AdminFeatures'         => 'features',
        ];

        $controller_name = \Context::getContext()->controller->controller_name;

        if (!isset($ovesio_route_resource[$controller_name])) {
            return [];
        }

        $resource = $ovesio_route_resource[$controller_name];

        $ovesio_generate_content_status = $ovesio_status && $ovesio_generate_content_status && in_array($resource, ['products', 'categories']);
        $ovesio_generate_seo_status     = $ovesio_status && $ovesio_generate_seo_status && in_array($resource, ['products', 'categories']);
        $ovesio_translate_status        = $ovesio_status && $ovesio_translate_status;

        $ovesio_manual_url = $this->context->link->getAdminLink(ManualController::TAB_CLASS_NAME) . '&type=' . rtrim($resource, 's');
        $ovesio_resource = $resource;
        $ovesio_route    = $controller_name;

        $buttons = [];
        $text_generate_content = $this->l('text_generate_content_with_ovesio');
        $text_generate_seo     = $this->l('text_generate_seo_with_ovesio');
        $text_translate        = $this->l('text_translate_with_ovesio');

        if (!empty($ovesio_generate_content_status)) {
            $buttons[] = [
                'label'   => $text_generate_content,
                'onclick' => 'ovesio.generateContent(event)',
                'style'   => 'background: #0dcaf0; color: white; font-weight: bold;',
                'data'    => [
                    'resource' => $ovesio_resource,
                    'route'    => $ovesio_route,
                    'href'     => $ovesio_manual_url
                ]
            ];
        }

        if (!empty($ovesio_generate_seo_status)) {
            $buttons[] = [
                'label'   => $text_generate_seo,
                'onclick' => 'ovesio.generateSeo(event)',
                'style'   => 'background: #ffc107; color: white; font-weight: bold;',
                'data'    => [
                    'resource' => $ovesio_resource,
                    'route'    => $ovesio_route,
                    'href'     => $ovesio_manual_url
                ]
            ];
        }

        if (!empty($ovesio_translate_status)) {
            $buttons[] = [
                'label'   => $text_translate,
                'onclick' => 'ovesio.translate(event)',
                'style'   => 'background: #198754; color: white; font-weight: bold;',
                'data'    => [
                    'resource' => $ovesio_resource,
                    'route'    => $ovesio_route,
                    'href'     => $ovesio_manual_url
                ]
            ];
        }

        return $buttons;
    }

    /**
     * Hook to add custom buttons in the admin toolbar
     * This hook is called in the toolbar area on various admin pages
     */
    public function hookDisplayDashboardToolbarTopMenu($params)
    {
        $buttons_data = $this->getToolbarButtons();
        $output = '';

        foreach ($buttons_data as $btn) {
            $data_attrs = '';
            foreach ($btn['data'] as $key => $val) {
                $data_attrs .= ' data-' . $key . '="' . $val . '"';
            }
            $output .= '<button type="button" class="btn ovesio-toolbar-btn" ' . $data_attrs . ' style="' . $btn['style'] . '" onclick="' . $btn['onclick'] . '">' . $btn['label'] . '</button>&nbsp;';
        }

        return $output;
    }

    /**
     * Hook to add CSS/JS in admin pages
     */
    public function hookActionAdminControllerSetMedia($params)
    {
        $action = Tools::getValue('action');
        if ($action && stripos($action, 'view') === false) {
            return;
        }

        $controllerName = \Context::getContext()->controller->controller_name;

        // Add CSS/JS only on relevant pages
        if (strpos($controllerName, 'AdminProducts') !== false ||
            strpos($controllerName, 'AdminCategories') !== false ||
            strpos($controllerName, 'AdminFeatures') !== false ||
            strpos($controllerName, 'AdminAttributesGroups') !== false) {

            $this->context->controller->addCss($this->_path . 'views/css/ovesio.css');
            $this->context->controller->addJS($this->_path . 'views/js/ovesio.js');

            // Pass toolbar buttons to JS for Symfony pages support
            \Media::addJsDef([
                'ovesio_toolbar_buttons' => $this->getToolbarButtons()
            ]);
        }
    }

    public function buildQueueHandler($manual = false)
    {
        $options = [];

        $config = OvesioConfiguration::getAll('ovesio');
        $data = $config->all();

        foreach ($data as $key => $value) {
            $key = strtolower(str_ireplace('ovesio_', '', $key));
            $options[$key] = $value;
        }

        $system_language = new \Language(Configuration::get('PS_LANG_DEFAULT'));

        $default_language   = $config->get('ovesio_default_language');
        $config_language    = $system_language->iso_code;
        $config_language_id = $system_language->id;

        if (stripos($default_language, $config_language) === 0 || $default_language == 'auto') {
            $default_language_id = $config_language_id;
        } else {
            $query = Db::getInstance()->getRow("SELECT id_lang FROM " . _DB_PREFIX_ . "language WHERE code LIKE '" . pSQL($default_language) . "%' LIMIT 1");

            if (empty($query->row['id_lang'])) {
                throw new Exception("Could not detect local default language based on language code '$default_language'");
            }

            $default_language_id = $query->row['id_lang'];
        }

        // Add additional options
        $options['server_url']          = Tools::getShopDomainSsl(true);
        $options['default_language_id'] = $default_language_id;
        $options['manual']              = $manual;

        $api_url   = $config->get('ovesio_api_url');
        $api_token = $config->get('ovesio_api_token');

        $api = new OvesioAI($api_token, $api_url);

        $model = new OvesioQueueModel($default_language_id);

        return new QueueHandler(
            $model,
            $api,
            $options,
            new OvesioLog()
        );
    }
}
