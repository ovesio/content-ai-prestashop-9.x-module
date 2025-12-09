<?php
class OvesioModel
{
    public function install()
    {
        return Db::getInstance()->execute("CREATE TABLE IF NOT EXISTS `" . _DB_PREFIX_ . "ovesio_activity` (
            `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            `resource_type` VARCHAR(50) NOT NULL COLLATE 'utf8mb4_general_ci',
            `resource_id` BIGINT(20) NOT NULL,
            `activity_type` VARCHAR(20) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
            `lang` VARCHAR(10) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
            `activity_id` BIGINT(20) NOT NULL DEFAULT '0',
            `hash` VARCHAR(119) NOT NULL DEFAULT '' COLLATE 'utf8mb4_general_ci',
            `status` ENUM('started','completed','error','skipped') NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `request` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `response` MEDIUMTEXT NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `message` VARCHAR(255) NULL DEFAULT NULL COLLATE 'utf8mb4_general_ci',
            `stale` TINYINT(4) NOT NULL DEFAULT '0',
            `created_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
            `updated_at` TIMESTAMP NOT NULL DEFAULT current_timestamp(),
            PRIMARY KEY (`id`) USING BTREE,
            UNIQUE INDEX `resource_type_resource_id_activity_type_lang` (`resource_type`, `resource_id`, `activity_type`, `lang`) USING BTREE,
            INDEX `ovesio_activity_id` (`id`) USING BTREE,
            INDEX `resource_type` (`resource_type`) USING BTREE,
            INDEX `resource_id` (`resource_id`) USING BTREE,
            INDEX `lang` (`lang`) USING BTREE,
            INDEX `action` (`activity_type`) USING BTREE,
            INDEX `status` (`status`) USING BTREE,
            INDEX `ovesio_id` (`activity_id`) USING BTREE,
            INDEX `hash` (`hash`) USING BTREE,
            INDEX `updated_at` (`updated_at`) USING BTREE,
            INDEX `created_at` (`created_at`) USING BTREE,
            INDEX `stale` (`stale`) USING BTREE
        )
        COLLATE='utf8mb4_general_ci'
        ENGINE=InnoDB");
    }

    public function installConfig()
    {
        $hash = md5(uniqid(rand(), true));

        $defaultLang = new Language(Configuration::get('PS_LANG_DEFAULT'));
        $config_language = $defaultLang->iso_code;

        $defaults = [];
        $defaults['status']           = 0;
        $defaults['hash']             = $hash;
        $defaults['api_url']          = 'https://api.ovesio.com/v1/';
        $defaults['api_token']        = '';
        $defaults['default_language'] = substr($config_language, 0, 2);

        $defaults['generate_content_status']          = '';
        $defaults['generate_content_include_stock_0'] = 1;
        $defaults['generate_content_live_update']     = '';
        $defaults['generate_content_workflow']        = '';
        $defaults['generate_content_when_description_length'] = [
            'products'   => 500,
            'categories' => 300,
        ];
        $defaults['generate_content_include_disabled'] = [
            'products'   => 1,
            'categories' => 1,
        ];
        $defaults['generate_content_for'] = [
            'products'   => 1,
            'categories' => 1,
        ];

        $defaults['generate_seo_status']          = '';
        $defaults['generate_seo_only_for_action'] = 1;
        $defaults['generate_seo_include_stock_0'] = 1;
        $defaults['generate_seo_live_update']     = '';
        $defaults['generate_seo_workflow']        = '';
        $defaults['generate_seo_for'] = [
            'products'   => 1,
            'categories' => 1,
        ];
        $defaults['generate_seo_include_disabled'] = [
            'products'   => 1,
            'categories' => 1,
        ];

        $defaults['translate_status']          = '';
        $defaults['translate_include_stock_0'] = 1;
        $defaults['translate_workflow']        = '';
        $defaults['translate_for'] = [
            'products'   => 1,
            'categories' => 1,
            'attributes' => 1,
            'features'   => 1,
        ];
        $defaults['translate_include_disabled'] = [
            'products'   => 1,
            'categories' => 1,
        ];
        $defaults['translate_fields'] = [
            'products'   => [
                'name'             => 1,
                'description'      => 1,
                'tag'              => 1,
                'meta_title'       => 1,
                'meta_description' => 1,
                'meta_keyword'     => 1,
            ],
            'categories' => [
                'name'             => 1,
                'description'      => 1,
                'meta_title'       => 1,
                'meta_description' => 1,
                'meta_keyword'     => 1,
            ],
        ];

        $settings = [];
        foreach ($defaults as $key => $value) {
            $settings['ovesio_' . $key] = $value;
        }

        foreach ($settings as $key => $value) {
            $key = strtoupper($key);

            if (is_array($value)) {
                $value = json_encode($value);
            }

            Configuration::updateValue($key, $value);
        }
    }

    public function uninstall()
    {
        return Db::getInstance()->execute("DROP TABLE IF EXISTS `" . _DB_PREFIX_ . "ovesio_activity`");
    }

    public function setStale($resource_type, $resource_id, $stale)
    {
        Db::getInstance()->execute("UPDATE `" . _DB_PREFIX_ . "ovesio_activity` SET stale = '" . (int)$stale . "' WHERE resource_type = '" . pSQL($resource_type) . "' AND resource_id = '" . (int)$resource_id . "'");
    }

    public function getActivities($filters = [])
    {
        $page = isset($filters['page']) ? (int)$filters['page'] : 1;
        $page = max($page, 1);

        $limit = isset($filters['limit']) ? (int)$filters['limit'] : 20;
        $offset = ($page - 1) * $limit;

        $sql = "SELECT ova.*, COALESCE(p.name, c.name, i.text, f.name, o.name) as resource_name FROM `" . _DB_PREFIX_ . "ovesio_activity` ova";

        $language_id = Configuration::get('PS_LANG_DEFAULT');

        $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "product_lang` p ON (p.id_product = ova.resource_id AND ova.resource_type = 'product' AND p.id_lang = '" . (int)$language_id . "')";
        $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "category_lang` c ON (c.id_category = ova.resource_id AND ova.resource_type = 'category' AND c.id_lang = '" . (int)$language_id . "')";
        $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "info_lang` i ON (i.id_info = ova.resource_id AND ova.resource_type = 'information' AND i.id_lang = '" . (int)$language_id . "')";
        $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "feature_lang` f ON (f.id_feature = ova.resource_id AND ova.resource_type = 'attribute_group' AND f.id_lang = '" . (int)$language_id . "')";
        $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "attribute_group_lang` o ON (o.id_attribute_group = ova.resource_id AND ova.resource_type = 'feature' AND o.id_lang = '" . (int)$language_id . "')";

        $sql .= " WHERE 1";

        $sql = $this->applyFilters($sql, $filters);

        $sql .= " GROUP BY ova.id";
        $sql .= " ORDER BY ova.updated_at DESC";
        $sql .= " LIMIT $offset, $limit";

        $query = Db::getInstance()->executeS($sql);

        return $query;
    }

    public function getActivity($activity_id)
    {
        $query = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "ovesio_activity` WHERE id = '" . (int)$activity_id . "'");

        return $query;
    }

    public function getActivitiesTotal($filters = [])
    {
        $sql = "SELECT COUNT(*) AS total FROM `" . _DB_PREFIX_ . "ovesio_activity` as ova";

        if (!empty($filters['resource_name'])) {
            $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "product_description` p ON (p.product_id = ova.resource_id AND ova.resource_type = 'product')";
            $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "category_description` c ON (c.category_id = ova.resource_id AND ova.resource_type = 'category')";
            $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "information_description` i ON (i.information_id = ova.resource_id AND ova.resource_type = 'information')";
            $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "attribute_group_description` ag ON (ag.attribute_group_id = ova.resource_id AND ova.resource_type = 'attribute_group')";
            $sql .= " LEFT JOIN `" . _DB_PREFIX_ . "option_description` o ON (o.option_id = ova.resource_id AND ova.resource_type = 'option')";
        }

        $sql .= " WHERE 1";

        $sql = $this->applyFilters($sql, $filters);

        $query = Db::getInstance()->getRow($sql);

        return $query['total'];
    }

    private function applyFilters($sql, $filters)
    {
        if (!empty($filters['resource_name'])) {
            $sql .= "
                AND (      p.name LIKE '%Modules [AUTO - RO]%'
   OR c.name LIKE '%Modules [AUTO - RO]%'
   OR i.title LIKE '%Modules [AUTO - RO]%'
   OR ag.name LIKE '%Modules [AUTO - RO]%'
   OR o.name LIKE '%Modules [AUTO - RO]%')
            ";
        }

        if (!empty($filters['resource_type'])) {
            $sql .= " AND ova.resource_type = '" . pSQL($filters['resource_type']) . "'";
        }

        if (!empty($filters['resource_id'])) {
            $sql .= " AND ova.resource_id = '" . (int)$filters['resource_id'] . "'";
        }

        if (!empty($filters['status'])) {
            $sql .= " AND ova.status = '" . pSQL($filters['status']) . "'";
        }

        if (!empty($filters['activity_type'])) {
            $sql .= " AND ova.activity_type = '" . pSQL($filters['activity_type']) . "'";
        }

        if (!empty($filters['language'])) {
            $sql .= " AND ova.lang = '" . pSQL($filters['language']) . "'";
        }

        if (!empty($filters['date'])) {
            if ($filters['date'] == 'today') {
                $sql .= " AND DATE(ova.updated_at) = CURDATE()";
            } elseif ($filters['date'] == 'yesterday') {
                $sql .= " AND DATE(ova.updated_at) = DATE_SUB(CURDATE(), INTERVAL 1 DAY)";
            } elseif ($filters['date'] == 'last7days') {
                $sql .= " AND ova.updated_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)";
            } elseif ($filters['date'] == 'last30days') {
                $sql .= " AND ova.updated_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)";
            } elseif ($filters['date'] == 'thismonth') {
                $sql .= " AND MONTH(ova.updated_at) = MONTH(CURDATE()) AND YEAR(ova.updated_at) = YEAR(CURDATE())";
            } elseif ($filters['date'] == 'lastmonth') {
                $sql .= " AND MONTH(ova.updated_at) = MONTH(DATE_SUB(CURDATE(), INTERVAL 1 MONTH)) AND YEAR(ova.updated_at) = YEAR(DATE_SUB(CURDATE(), INTERVAL 1 MONTH))";
            } elseif ($filters['date'] == 'custom' && !empty($filters['date_from']) && !empty($filters['date_to'])) {
                $sql .= " AND DATE(ova.updated_at) BETWEEN '" . pSQL($filters['date_from']) . "' AND '" . pSQL($filters['date_to']) . "'";
            }
        }

        return $sql;
    }

    public function getAttributeGroupId($attribute_id)
    {
        $query = Db::getInstance()->getRow("SELECT attribute_group_id FROM `" . _DB_PREFIX_ . "attribute` WHERE attribute_id = '" . (int)$attribute_id . "'");

        return $query['attribute_group_id'] ?? null;
    }
}
