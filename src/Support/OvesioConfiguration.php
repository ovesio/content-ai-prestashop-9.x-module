<?php

namespace PrestaShop\Module\Ovesio\Support;

use Configuration;
use ConfigurationCore;
use Shop;

class OvesioConfiguration extends ConfigurationCore
{
    public static function getAll($module, $idLang = null, $idShopGroup = null, $idShop = null)
    {
        // Init the cache on demand
        if (!self::$_initialized) {
            Configuration::loadConfiguration();
        }

        // $idLang = self::isLangKey($key) ? (int) $idLang : 0;

        if (self::$_new_cache_shop === null) {
            $idShop = 0;
        } elseif ($idShop === null || !Shop::isFeatureActive()) {
            $idShop = Shop::getContextShopID(true);
        }

        if (self::$_new_cache_group === null) {
            $idShopGroup = 0;
        } elseif ($idShopGroup === null || !Shop::isFeatureActive()) {
            $idShopGroup = Shop::getContextShopGroupID(true);
        }

        $all = [];

        foreach (self::$_new_cache_global as $key => $data) {
            if (stripos($key, $module . '_') !== 0) continue;

            $all[$key] = Configuration::get($key, $idLang, $idShopGroup, $idShop);
        }

        return new class ($all) {
            private $data;

            public function __construct($data)
            {
                foreach ($data as $key => $value) {
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (json_last_error() === JSON_ERROR_NONE) {
                            $data[$key] = $decoded;
                        }
                    }
                }

                $this->data = $data;
            }

            public function get($key, $default = null)
            {
                return $this->data[strtoupper($key)] ?? $this->data[strtolower($key)] ?? $default;
            }

            public function all()
            {
                return $this->data;
            }
        };
    }
}