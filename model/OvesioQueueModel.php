<?php

use PrestaShop\Module\Ovesio\Support\OvesioConfiguration;

class OvesioQueueModel
{
    private $config;
    private $default_language_id;
    private $module_key = 'ovesio';

    public function __construct($default_language_id)
    {
        $this->config = OvesioConfiguration::getAll('ovesio');

        $this->default_language_id = $default_language_id;
    }

    public function getCategories($category_ids, $status)
    {
        $where = '';
        if (!empty($category_ids)) {
            $where = 'AND c.id_category IN (' . implode(',', $category_ids) . ')';
        }

        if (!$status) {
            $where .= " AND c.active = 1";
        }

        $query = Db::getInstance()->executeS("SELECT cd.*, cd.id_category as category_id FROM " . _DB_PREFIX_ . "category_lang cd
                JOIN " . _DB_PREFIX_ . "category c ON c.id_category = cd.id_category
                WHERE cd.id_lang = '{$this->default_language_id}' $where
                ORDER BY c.id_category");

        return $query;
    }

    public function getCategoriesWithDescriptionDependency($category_ids, $status)
    {
        $where = '';
        if (!empty($category_ids)) {
            $where = 'AND c.id_category IN (' . implode(',', array_map('intval', $category_ids)) . ')';
        }

        if (!$status) {
            $where .= " AND c.active = 1";
        }

        $query = Db::getInstance()->executeS("SELECT cd.*, c.id_category as category_id
                FROM " . _DB_PREFIX_ . "category_lang cd
                JOIN " . _DB_PREFIX_ . "category c ON c.id_category = cd.id_category
                JOIN " . _DB_PREFIX_ . "ovesio_activity ova ON ova.resource_type = 'category' AND ova.resource_id = c.id_category
                WHERE cd.id_lang = '{$this->default_language_id}' AND ova.activity_id > 0 AND ova.status = 'completed' $where
                ORDER BY c.id_category");

        return $query;
    }

    public function getProducts($product_id, $status, $out_of_stock)
    {
        $where = '';
        if (!empty($product_id)) {
            $where = ' AND p.id_product IN (' . implode(',', $product_id) . ')';
        }

        if (!$out_of_stock) {
            $where .= " AND (SELECT SUM(quantity) FROM " . _DB_PREFIX_ . "stock_available sa WHERE sa.id_product = p.id_product) > 0";
        }

        if (!$status) {
            $where .= " AND p.active = 1";
        }

        $query = Db::getInstance()->executeS("SELECT pd.*, p.id_product as product_id FROM " . _DB_PREFIX_ . "product_lang pd
                JOIN " . _DB_PREFIX_ . "product p ON p.id_product = pd.id_product
                WHERE pd.id_lang = '{$this->default_language_id}' $where
                ORDER BY p.id_product");

        return $query;
    }

    public function getProductsWithDescriptionDependency($product_id, $status, $out_of_stock)
    {
        $where = '';
        if (!empty($product_id)) {
            $where = ' AND p.id_product IN (' . implode(',', $product_id) . ')';
        }

        if (!$out_of_stock) {
            $where .= " AND (SELECT SUM(quantity) FROM " . _DB_PREFIX_ . "stock_available sa WHERE sa.id_product = p.id_product) > 0";
        }

        if (!$status) {
            $where .= " AND p.active = 1";
        }

        $query = Db::getInstance()->executeS("SELECT pd.*, p.id_product as product_id FROM " . _DB_PREFIX_ . "product_lang pd
                JOIN " . _DB_PREFIX_ . "product p ON p.id_product = pd.id_product
                JOIN " . _DB_PREFIX_ . "ovesio_activity ova ON ova.resource_type = 'product' AND ova.resource_id = p.id_product
                WHERE pd.id_lang = '{$this->default_language_id}' AND ova.activity_id > 0 AND ova.status = 'completed' $where
                ORDER BY p.id_product");

        return $query;
    }

    public function getProductsFeatures($product_ids = [])
    {
        $where = '';
        if (!empty($product_ids)) {
            $where = ' AND pf.id_product IN (' . implode(',', $product_ids) . ')';
        }

        $query = Db::getInstance()->executeS("SELECT pf.id_product as product_id, pf.id_feature as feature_id, fl.name, fvl.id_feature_value as feature_value_id, fvl.value as value
                FROM " . _DB_PREFIX_ . "feature_product pf
                JOIN " . _DB_PREFIX_ . "feature_lang fl ON (fl.id_feature = pf.id_feature AND fl.id_lang = '{$this->default_language_id}')
                JOIN " . _DB_PREFIX_ . "feature_value_lang fvl ON (fvl.id_feature_value = pf.id_feature_value AND fvl.id_lang = '{$this->default_language_id}')
                WHERE 1 $where
                ORDER BY pf.id_product");

        $results = $query;

        $product_features = [];
        foreach ($results as $pf) {
            if (empty($pf['name']))
                continue;

            $product_features[$pf['product_id']][$pf['feature_id']]['name'] = trim($pf['name']);
            $product_features[$pf['product_id']][$pf['feature_id']]['values'][$pf['feature_value_id']] = trim($pf['name']);
        }

        return $product_features;
    }

    public function getAttributes($attribute_ids = [])
    {
        $where = '';
        if (!empty($attribute_ids)) {
            $where = 'AND a.id_attribute IN (' . implode(',', $attribute_ids) . ')';
        }

        $query = Db::getInstance()->executeS("SELECT a.id_attribute as attribute_id, a.id_attribute_group as attribute_group_id, ad.name FROM " . _DB_PREFIX_ . "attribute_lang ad
        JOIN " . _DB_PREFIX_ . "attribute a ON a.id_attribute = ad.id_attribute
        WHERE ad.id_lang = '{$this->default_language_id}' $where
        ORDER BY a.id_attribute");

        return $query;
    }

    public function getAttributeGroups($attribute_group_ids = [])
    {
        $where = '';
        if (!empty($attribute_group_ids)) {
            $where = 'AND agd.id_attribute_group IN (' . implode(',', $attribute_group_ids) . ')';
        }

        $query = Db::getInstance()->executeS("SELECT agd.id_attribute_group as attribute_group_id, agd.public_name as name
                FROM " . _DB_PREFIX_ . "attribute_group_lang agd
                JOIN " . _DB_PREFIX_ . "attribute_group ag ON ag.id_attribute_group = agd.id_attribute_group
                WHERE agd.id_lang = '{$this->default_language_id}' {$where}
                ORDER BY ag.id_attribute_group");

        return $query;
    }

    public function getGroupsAttributes($attribute_group_ids = [])
    {
        $where = '';
        if (!empty($attribute_group_ids)) {
            $where = 'AND a.id_attribute_group IN (' . implode(',', $attribute_group_ids) . ')';
        }

        $query = Db::getInstance()->executeS("SELECT a.id_attribute as attribute_id, a.id_attribute_group as attribute_group_id, ad.name FROM " . _DB_PREFIX_ . "attribute_lang ad
                JOIN " . _DB_PREFIX_ . "attribute a ON a.id_attribute = ad.id_attribute
                WHERE ad.id_lang = '{$this->default_language_id}' $where
                ORDER BY a.id_attribute");

        return $query;
    }

    public function getFeatureValues($feature_ids = [])
    {
        $where = '';
        if (!empty($feature_ids)) {
            $where = 'AND fv.id_feature IN (' . implode(',', $feature_ids) . ')';
        }

        $query = Db::getInstance()->executeS("SELECT fv.id_feature as feature_id, fvl.id_feature_value as feature_value_id, fvl.value as name
                FROM " . _DB_PREFIX_ . "feature_value_lang fvl
                JOIN " . _DB_PREFIX_ . "feature_value fv ON fv.id_feature_value = fvl.id_feature_value
                WHERE fvl.id_lang = '{$this->default_language_id}' $where");

        return $query;
    }

    public function getFeatures($feature_ids = [])
    {
        $where = '';
        if (!empty($feature_ids)) {
            $where = 'AND f.id_feature IN (' . implode(',', $feature_ids) . ')';
        }

        $query = Db::getInstance()->executeS("SELECT f.id_feature as feature_id, fl.name FROM " . _DB_PREFIX_ . "feature_lang fl
                JOIN " . _DB_PREFIX_ . "feature f ON f.id_feature = fl.id_feature
                WHERE fl.id_lang = '{$this->default_language_id}' $where
                ORDER BY f.id_feature");

        return $query;
    }

    public function updateCategoryDescription($category_id, $language_id, $description)
    {
        if (empty($description)) {
            return;
        }

        $fields_sql = [];
        foreach ($description as $key => $value) {
            $fields_sql[] = "`" . pSQL($key) . "` = '" . pSQL($value) . "'";
        }

        // check if exists first
        $exists = Db::getInstance()->getValue("SELECT id_category as category_id FROM " . _DB_PREFIX_ . "category_lang WHERE id_category = " . (int)$category_id . " AND id_lang = " . (int)$language_id);

        if ($exists) {
            Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "category_lang SET " . implode(', ', $fields_sql) . " WHERE id_category = " . (int)$category_id . " AND id_lang = " . (int)$language_id);
        } else {
            $id_shop = (int)Context::getContext()->shop->id;
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "category_lang SET id_category = " . (int)$category_id . ", id_lang = " . (int)$language_id . ", id_shop = " . $id_shop . ", " . implode(', ', $fields_sql));
        }
    }

    public function updateAttributeGroupDescription($attribute_group_id, $language_id, $name)
    {
        // check if exists first
        $exists = Db::getInstance()->getValue("SELECT id_attribute_group as attribute_group_id FROM " . _DB_PREFIX_ . "attribute_group_lang WHERE id_attribute_group = " . (int)$attribute_group_id . " AND id_lang = " . (int)$language_id);

        if ($exists) {
            Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "attribute_group_lang SET name = '" . pSQL($name) . "', public_name = '" . pSQL($name) . "' WHERE id_attribute_group = " . (int)$attribute_group_id . " AND id_lang = " . (int)$language_id);
        } else {
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "attribute_group_lang (id_attribute_group, id_lang, name, public_name) VALUES (" . (int)$attribute_group_id . ", " . (int)$language_id . ", '" . pSQL($name) . "', '" . pSQL($name) . "')");
        }
    }

    public function updateAttributeDescription($attribute_id, $language_id, $name)
    {
        // check if exists first
        $exists = Db::getInstance()->getValue("SELECT id_attribute as attribute_id FROM " . _DB_PREFIX_ . "attribute_lang WHERE id_attribute = " . (int)$attribute_id . " AND id_lang = " . (int)$language_id);

        if ($exists) {
            Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "attribute_lang SET name = '" . pSQL($name) . "' WHERE id_attribute = " . (int)$attribute_id . " AND id_lang = " . (int)$language_id);
        } else {
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "attribute_lang (id_attribute, id_lang, name) VALUES (" . (int)$attribute_id . ", " . (int)$language_id . ", '" . pSQL($name) . "')");
        }
    }

    public function updateFeatureDescription($feature_id, $language_id, $name)
    {
        // check if exists first
        $exists = Db::getInstance()->getValue("SELECT id_feature as feature_id FROM " . _DB_PREFIX_ . "feature_lang WHERE id_feature = " . (int)$feature_id . " AND id_lang = " . (int)$language_id);

        if ($exists) {
            Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "feature_lang SET name = '" . pSQL($name) . "' WHERE id_feature = " . (int)$feature_id . " AND id_lang = " . (int)$language_id);
        } else {
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "feature_lang (id_feature, id_lang, name) VALUES (" . (int)$feature_id . ", " . (int)$language_id . ", '" . pSQL($name) . "')");
        }
    }

    public function updateProductDescription($product_id, $language_id, $description)
    {
        if (empty($description)) {
            return;
        }

        $fields_sql = [];
        foreach ($description as $key => $value) {
            $fields_sql[] = "`" . pSQL($key) . "` = '" . pSQL($value) . "'";
        }

        // check if exists first
        $exists = Db::getInstance()->getValue("SELECT id_product as product_id FROM " . _DB_PREFIX_ . "product_lang WHERE id_product = " . (int)$product_id . " AND id_lang = " . (int)$language_id);

        if ($exists) {
            Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "product_lang SET " . implode(', ', $fields_sql) . " WHERE id_product = " . (int)$product_id . " AND id_lang = " . (int)$language_id);
        } else {
            $id_shop = (int)Context::getContext()->shop->id;
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "product_lang SET id_product = " . (int)$product_id . ", id_lang = " . (int)$language_id . ", id_shop = " . $id_shop . ", " . implode(', ', $fields_sql));
        }
    }

    // PrestaShop product attributes don't have translatable text fields like OpenCart
    // Attribute values are already translated in attribute_lang table
    public function updateAttributeValueDescription($product_id, $attribute_id, $language_id, $text)
    {
        // This method is not applicable to PrestaShop's attribute system
        // PrestaShop uses combinations (product_attribute) which reference attributes (attribute_lang)
        // Translation is done at the attribute level, not per product
        return;
    }

    public function updateFeatureValueDescription($feature_value_id, $language_id, $value)
    {
        // check if exists first
        $exists = Db::getInstance()->getValue("SELECT id_feature_value as feature_value_id FROM " . _DB_PREFIX_ . "feature_value_lang WHERE id_feature_value = " . (int)$feature_value_id . " AND id_lang = " . (int)$language_id);

        if ($exists) {
            Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "feature_value_lang SET value = '" . pSQL($value) . "' WHERE id_feature_value = " . (int)$feature_value_id . " AND id_lang = " . (int)$language_id);
        } else {
            Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "feature_value_lang (id_feature_value, id_lang, value) VALUES (" . (int)$feature_value_id . ", " . (int)$language_id . ", '" . pSQL($value) . "')");
        }
    }

    public function getProductForSeo($product_id, $language_id)
    {
        $data = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "product` WHERE id_product = " . (int)$product_id);

        $lang_data = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "product_lang` WHERE id_product = " . (int)$product_id . " AND id_lang = " . (int)$language_id);
        $data['product_lang'][$language_id] = $lang_data ?? [];

        return $data;
    }

    public function getCategoryForSeo($category_id, $language_id)
    {
        $data = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "category` WHERE id_category = " . (int)$category_id);

        $lang_data = Db::getInstance()->getRow("SELECT * FROM `" . _DB_PREFIX_ . "category_lang` WHERE id_category = " . (int)$category_id . " AND id_lang = " . (int)$language_id);
        $data['category_lang'][$language_id] = $lang_data ?? [];

        return $data;
    }

    public function addList(array $data = [])
    {
        $fields = [];
        foreach ($data as $key => $value) {
            $fields[] = "`" . pSQL($key) . "` = '" . pSQL($value) . "'";
        }

        $fields_sql = implode(', ', $fields);

        Db::getInstance()->execute("INSERT INTO " . _DB_PREFIX_ . "ovesio_activity SET " . $fields_sql . " ON DUPLICATE KEY UPDATE " . $fields_sql);
    }

    public function getProductCategories($product_ids)
    {
        $product_category_data = [];

        // Get product categories
        $results = Db::getInstance()->executeS("SELECT id_product as product_id, id_category as category_id FROM " . _DB_PREFIX_ . "category_product WHERE id_product IN (" . implode(',', $product_ids) . ")");

        if (empty($results)) {
            return $product_category_data;
        }

        // Get all unique category IDs
        $category_ids = array_unique(array_column($results, 'category_id'));

        // Build category paths using nested set model
        $category_paths = [];
        foreach ($category_ids as $category_id) {
            // Get the full path for this category using nested set model
            $path = Db::getInstance()->executeS("
                SELECT c2.id_category, cl.name
                FROM " . _DB_PREFIX_ . "category c1
                INNER JOIN " . _DB_PREFIX_ . "category c2 ON c2.nleft <= c1.nleft AND c2.nright >= c1.nright
                LEFT JOIN " . _DB_PREFIX_ . "category_lang cl ON cl.id_category = c2.id_category AND cl.id_lang = " . (int)$this->default_language_id . "
                WHERE c1.id_category = " . (int)$category_id . "
                AND c2.id_category != 1
                ORDER BY c2.nleft
            ");

            if (!empty($path)) {
                $names = array_column($path, 'name');
                $category_paths[$category_id] = implode(' > ', array_filter($names));
            }
        }

        // Map categories to products with full paths
        foreach ($results as $result) {
            if (isset($category_paths[$result['category_id']])) {
                $product_category_data[$result['product_id']][] = $category_paths[$result['category_id']];
            }
        }

        return $product_category_data;
    }

    public function getCategory($category_id) {
        $query = Db::getInstance()->getRow("SELECT c.id_category as category_id, cd.name, cd.link_rewrite
        FROM " . _DB_PREFIX_ . "category c
        LEFT JOIN " . _DB_PREFIX_ . "category_lang cd ON (c.id_category = cd.id_category)
        WHERE c.id_category = " . (int)$category_id . " AND cd.id_lang = " . (int)$this->default_language_id);

        return $query;
    }

    public function getCronList($params = [])
    {
        $resource_type = isset($params['resource_type']) ? pSQL($params['resource_type']) : null;
        $resource_id   = isset($params['resource_id']) ? (int) $params['resource_id'] : null;
        $limit         = isset($params['limit']) ? (int) $params['limit'] : 20;

        $limit = max(10, $limit);

        $generate_content_status = (bool) $this->config->get($this->module_key . '_generate_content_status');
        $generate_seo_status     = (bool) $this->config->get($this->module_key . '_generate_seo_status');
        $translate_status        = (bool) $this->config->get($this->module_key . '_translate_status');

        $generate_content_include_disabled = array_filter((array) $this->config->get($this->module_key . '_generate_content_include_disabled'));
        $generate_seo_include_disabled     = array_filter((array) $this->config->get($this->module_key . '_generate_seo_include_disabled'));
        $translate_include_disabled        = array_filter((array) $this->config->get($this->module_key . '_translate_include_disabled'));

        $generate_content_for = array_filter((array) $this->config->get($this->module_key . '_generate_content_for'));
        $generate_seo_for     = array_filter((array) $this->config->get($this->module_key . '_generate_seo_for'));
        $translate_fields     = (array) $this->config->get($this->module_key . '_translate_fields');
        $translate_for        = [];

        foreach ((array) $this->config->get($this->module_key . '_translate_for') as $resource => $status) {
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

        $language_settings = (array) $this->config->get($this->module_key . '_language_settings');

        $translate_languages = [];
        if(!empty($language_settings)) {
            foreach ($language_settings as $lang_id => $ls) {
                if (!empty($ls['translate']) && !empty($ls['code'])) {
                    $translate_languages[] = $ls['code'];
                }
            }
        }

        sort($translate_languages);

        if (empty($translate_languages) || empty($translate_for)) { // no translation languages selected => translation = disabled
            $translate_status = false;
        }

        $generate_content_stock_0 = (bool) $this->config->get($this->module_key . '_generate_content_include_stock_0');
        $generate_seo_stock_0     = (bool) $this->config->get($this->module_key . '_generate_seo_include_stock_0');
        $translate_stock_0        = (bool) $this->config->get($this->module_key . '_translate_include_stock_0');

        $resources  = [];
        if ($generate_content_status) {
            $resources = array_merge($resources, $generate_content_for);
        }

        if ($generate_seo_status) {
            $resources = array_merge($resources, $generate_seo_for);
        }

        if ($translate_status) {
            $resources = array_merge($resources, $translate_for);
        }

        $send_disabled_categories = 0;
        $send_disabled_categories += !empty($generate_content_include_disabled['categories']);
        $send_disabled_categories += !empty($generate_seo_include_disabled['categories']);
        $send_disabled_categories += !empty($translate_include_disabled['categories']);

        $send_disabled_products = 0;
        $send_disabled_products += !empty($generate_content_include_disabled['products']);
        $send_disabled_products += !empty($generate_seo_include_disabled['products']);
        $send_disabled_products += !empty($translate_include_disabled['products']);

        $send_stock_0_products = 0;
        $send_stock_0_products += !empty($generate_content_stock_0);
        $send_stock_0_products += !empty($generate_seo_stock_0);
        $send_stock_0_products += !empty($translate_stock_0);


        $union = [];

        if ($translate_status) {
            // Attributes
            if (!empty($resources['features']) && (!$resource_type || $resource_type == 'feature')) {
                $features_sql = "SELECT 'feature' as resource, f.id_feature AS resource_id
                    FROM " . _DB_PREFIX_ . "feature_lang as fd
                    JOIN " . _DB_PREFIX_ . "feature as f ON f.id_feature = fd.id_feature
                    WHERE fd.id_lang = '{$this->default_language_id}'";

                    if ($resource_id) {
                        $features_sql .= " AND f.id_feature = '" . (int)$resource_id . "'";
                    }

                $union[] = $features_sql;
            }

            // Options
            if (!empty($resources['attributes']) && (!$resource_type || $resource_type == 'attribute')) {
                $attributes_sql = "SELECT 'attribute_group' as resource, a.id_attribute_group AS resource_id
                    FROM " . _DB_PREFIX_ . "attribute_lang as ad
                    JOIN " . _DB_PREFIX_ . "attribute as a ON a.id_attribute = ad.id_attribute
                    WHERE ad.id_lang = '{$this->default_language_id}'";

                    if ($resource_id) {
                        $attributes_sql .= " AND a.id_attribute_group = '" . (int)$resource_id . "'";
                    }

                $union[] = $attributes_sql;
            }
        }

        if (!empty($resources['categories'])) {
            if (!$resource_type || $resource_type == 'category') {
                $categories_sql = "SELECT 'category' as resource, cd.id_category as resource_id
                    FROM " . _DB_PREFIX_ . "category_lang as cd
                    JOIN " . _DB_PREFIX_ . "category as c ON c.id_category = cd.id_category
                    WHERE cd.id_lang = '{$this->default_language_id}'";

                    if ($send_disabled_categories == 0) {
                        $categories_sql .= " AND c.active = 1";
                    }

                    if ($resource_id) {
                        $categories_sql .= " AND cd.id_category = '" . (int)$resource_id . "'";
                    }

                $union[] = $categories_sql;
            }
        }

        if (!empty($resources['products'])) {
            if (!$resource_type || $resource_type == 'product') {
                $products_sql = "SELECT 'product' as resource, p.id_product as resource_id
                    FROM " . _DB_PREFIX_ . "product as p
                    JOIN " . _DB_PREFIX_ . "product_lang as pd ON p.id_product = pd.id_product
                    where pd.id_lang = '{$this->default_language_id}'";

                    if ($send_disabled_products == 0) {
                        $products_sql .= " AND p.status = 1";
                    }

                    if ($send_stock_0_products == 0) {
                        $products_sql .= " AND p.quantity > '0'";
                    }

                    if ($resource_id) {
                        $products_sql .= " AND pd.id_product = '" . (int)$resource_id . "'";
                    }

                $union[] = $products_sql;
            }
        }

        if (!$union) {
            return [];
        }

        $translate_languages_hash = implode(',', $translate_languages);

        $union_sql = "\n(" . implode(") \nUNION (", $union) . ')';

        /**
         * Select unstarted activities, or started and not finished activities
         */
        $sql = "SELECT
            r.`resource`,
            r.resource_id,";

            if ($translate_status) {
                $sql .= "\n COUNT(if (ova.activity_type = 'translate', 1, null)) as count_translate,";
                $sql .= "\n GROUP_CONCAT(IF (ova.activity_type = 'translate', ova.lang, null) ORDER BY ova.lang SEPARATOR ',') as lang_hash,";
            }

            if ($generate_content_status) {
                $sql .= "\n COUNT(if (ova.activity_type = 'generate_content', 1, null)) as count_generate_content,";
            }

            if ($generate_seo_status) {
                $sql .= "\n COUNT(if (ova.activity_type = 'generate_seo', 1, null)) as count_generate_seo,";
            }

            $sql .= "\n ova.stale AS stale
            FROM ($union_sql) as r
            LEFT JOIN " . _DB_PREFIX_ . "ovesio_activity as ova ON ova.resource_type = r.resource AND ova.resource_id = r.resource_id
            GROUP BY r.`resource`, r.resource_id";

            $having = [];

            $having[] = "max(stale) = 1";

            if ($params['resource_type'] && $params['resource_id']) {
                $having[] = "stale = 0"; // any existing activity for this resource
            }

            if ($translate_status) {
                $resource_in = $this->resourcesToActivities(array_keys($translate_for));
                $resource_in[] = '-1'; // avoid error

                $resource_in = "'" . implode("', '", $resource_in) . "'";

                $having[] = "(resource in ($resource_in) AND (count_translate = 0 OR lang_hash != '$translate_languages_hash'))";
            }

            if ($generate_content_status) {
                $resource_in = $this->resourcesToActivities(array_keys($generate_content_for));
                $resource_in[] = '-1'; // avoid error

                $resource_in = "'" . implode("', '", $resource_in) . "'";

                $having[] = "(resource in ($resource_in) AND count_generate_content = 0)";
            }

            if ($generate_seo_status) {
                $resource_in = $this->resourcesToActivities(array_keys($generate_seo_for));
                $resource_in[] = '-1'; // avoid error

                $resource_in = "'" . implode("', '", $resource_in) . "'";

                $having[] = "(resource in ($resource_in) AND count_generate_seo = 0)";
            }

            if ($having) {
                $sql .= " HAVING " . implode(' OR ', $having);
            }

            $sql .= " ORDER BY if (ova.id AND ova.status != 'error', 0, 1), RAND() LIMIT $limit";

        $query = Db::getInstance()->executeS($sql);

        $activities = [];
        foreach ($query as $row) {
            $key = $row['resource'] . '/' . $row['resource_id'];

            $activities[$key] = [
                'generate_content' => null,
                'generate_seo'     => null,
                'translate'        => [],
            ];
        }

        if (!$activities) {
            return [];
        }

        /**
         * Update current progress
         */
        $conditions_sql = [];
        foreach ($query as $row) {
            $conditions_sql[] = "(resource_type = '{$row['resource']}' AND resource_id = '{$row['resource_id']}')";
        }

        $conditions_sql = implode(' OR ', $conditions_sql);

        // am obtinut lista elementelor, acum trebuie sa le obtinem la fiecare si activitatile parcurse
        $sql = "SELECT id, resource_type, resource_id, activity_type, lang, activity_id, hash, status, stale, updated_at FROM " . _DB_PREFIX_ . "ovesio_activity WHERE $conditions_sql";

        $query = Db::getInstance()->executeS($sql);

        foreach ($query as $row) {
            $key = $row['resource_type'] . '/' . $row['resource_id'];

            if (is_array($activities[$key][$row['activity_type']])) { // translate
                $activities[$key][$row['activity_type']][] = $row;
            } else {
                $activities[$key][$row['activity_type']] = $row;
            }
        }

        return $activities;
    }

    public function getActivityById($id)
    {
        $query = Db::getInstance()->getRow("SELECT * FROM " . _DB_PREFIX_ . "ovesio_activity WHERE id = " . (int) $id);

        return $query;
    }

    private function resourcesToActivities($resources)
    {
        $resource_to_activity_type = [
            'products'   => 'product',
            'categories' => 'category',
            'attributes' => 'attribute_group',
            'features'   => 'feature',
        ];

        return array_map(function($res) use ($resource_to_activity_type) {
            return isset($resource_to_activity_type[$res]) ? $resource_to_activity_type[$res] : $res;
        }, $resources);
    }

    public function skipRunningTranslations()
    {
        Db::getInstance()->execute("UPDATE " . _DB_PREFIX_ . "ovesio_activity SET stale = 0, status = 'skipped' WHERE activity_type = 'translate' AND status = 'started'");
    }

    public function getDefaultLanguageId()
    {
        return $this->default_language_id;
    }
}