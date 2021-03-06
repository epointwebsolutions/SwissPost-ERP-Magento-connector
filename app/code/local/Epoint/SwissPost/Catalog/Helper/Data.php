<?php

/**
 * Data Helper
 *
 */
class Epoint_SwissPost_Catalog_Helper_Data extends Mage_Core_Helper_Abstract
{
    /**
     * Setting path for mapping fields, default values
     */
    const XML_CONFIG_PATH_DEFAULT_VALUES = 'swisspost_api/product/default_values';
    const XML_CONFIG_PATH_API_FIELDS = 'swisspost_api/product/fields';
    const XML_CONFIG_PATH_DINAMYC_ATTRIBUTE_MAPPING = 'swisspost_api/product/dynamic_attribute_mapping';
    const XML_CONFIG_PATH_TAX_CLASS_MAPPING = 'swisspost_api/product/tax_class_mapping';
    const XML_CONFIG_PATH_ODOO_TAX_CLASS_ATTRIBUTE_CODE = 'swisspost_api/product/tax_class_id_attribue_code';
    const XML_CONFIG_PATH_STORE_ATTRIBUTE_MAPPING = 'swisspost_api/product_stores/mapping_code';
    const XML_CONFIG_DISABLED_PRODUCTS_BY_TYPE = 'swisspost_api/product/disabled_products_by_type';
    const XML_CONFIG_IMPORT_CATEGORY_FROM = 'swisspost_api/category/import_from';
    const XML_CONFIG_ENABLE_IMPORT_IMAGES = 'swisspost_api/product/import_images';
    const XML_CONFIG_IMPORT_LIMIT = 'swisspost_api/product/import_limit';
    const XML_CONFIG_ENABLE_IMPORT_FILTER_CHANGED = 'swisspost_api/product/import_filter_changed';
    const XML_CONFIG_IMPORT_LAST_DATE = 'swisspost_api/product/import_last_date';
    const XML_CONFIG_IMPORT_CUSTOM_ORDER = 'swisspost_api/product/import_custom_order';

    const XML_CONFIG_VISIBILITY_ATTRIBUTE_CODE = 'swisspost_api/product/visibility_attribute_code';
    const XML_CONFIG_VISIBILITY_MAPPING_VALUES = 'swisspost_api/product/visibility_mappging';

    /**
     * Get field for attribute mapping
     *
     * @return array
     */
    public static function getApiFields()
    {
        if (!isset($fields)) {
            $fields = array();
        } else {
            return $fields;
        }
        $configured = Mage::getStoreConfig(self::XML_CONFIG_PATH_API_FIELDS);
        if ($configured) {
            $items = explode(',', str_replace(array("\n", " ", "\t"), "", $configured));
            foreach ($items as $item) {
                $fields[] = $item;
            }
        }

        return $fields;
    }

    /**
     * Get magento coresponding class id, based on config
     * Format:odoo_tax_class_id|magento_tax_class_id
     *
     * @param $odoo_tax_class_id
     *
     * @return int
     */
    public static function __toTaxClassId($odoo_tax_class_id)
    {
        static $mapping;
        if (!isset($mapping)) {
            $fields = array();
        } else {
            return isset($mapping[$odoo_tax_class_id]) ? $mapping[$odoo_tax_class_id] : 0;;
        }
        $configured = Mage::helper('swisspost_api')->textToArray(
            Mage::getStoreConfig(self::XML_CONFIG_PATH_TAX_CLASS_MAPPING)
        );
        $mapping = array();
        if ($configured) {
            foreach ($configured as $odoo_tax_class_id => $mage_tax_class_id) {
                $mapping[($odoo_tax_class_id)] = ($mage_tax_class_id);
            }
        }

        return isset($mapping[$odoo_tax_class_id]) ? $mapping[$odoo_tax_class_id] : 0;
    }

    /**
     * Get Swisspost class id, from a product
     *
     * @param $item
     *
     * @return int
     */
    public static function getTaxClassId($item)
    {
        $attribute_code = Mage::getStoreConfig(self::XML_CONFIG_PATH_ODOO_TAX_CLASS_ATTRIBUTE_CODE);
        if (isset($item[$attribute_code])) {
            $odoo_tax_classes = $item[$attribute_code];
            $odoo_tax_class_id = 0;
            if (is_array($odoo_tax_classes)) {
                foreach ($odoo_tax_classes as $id) {
                    $odoo_tax_class_id = $id;
                    break;
                }
            }
            if ($odoo_tax_class_id) {
                return (int)self::__toTaxClassId($odoo_tax_class_id);
            }
        }

        return 0;
    }

    /**
     * Get SwissPost values
     *
     * @param $item
     *
     * @return mixed
     */
    public static function __fromDynamicAttributes($item)
    {
        static $mapping;
        static $loaded;
        
        $values = array();
        if (isset($loaded[$item['product_code']])) {
            return $loaded[$item['product_code']];
        }
        // Odoo attribute name| magento code
        if (!isset($mapping)) {
            $mapping = Mage::helper('swisspost_api')->textToArray(
                Mage::getStoreConfig(self::XML_CONFIG_PATH_DINAMYC_ATTRIBUTE_MAPPING)
            );
        }
        /**
         * $odoo_values
         */
        $odoo_values = array();
        if (isset($item['dynamic_attributes'])) {
            foreach ($item['dynamic_attributes'] as $attribute) {
                $type = $attribute['attribute_type'];
                $key = 'attribute_value_' . $type;
                $odoo_values[$attribute['attribute_name']]['value'] = $attribute[$key];
                if (isset($attribute['languages']) && $attribute['languages']) {
                    $odoo_values[$attribute['attribute_name']]['languages'] = $attribute['languages'];
                }
            }
        }
        foreach ($mapping as $odoo_attribute_code => $mage_attribute_code) {
        	if(isset($odoo_values[$odoo_attribute_code])){
            	$values[$mage_attribute_code] = $odoo_values[$odoo_attribute_code];
        	}
        }
        return $values;
    }
    /**
     * Get SwissPost value rom visibility
     *
     * @param $item
     *
     * @return string
     */
    public static function __getVisibilityValue($item)
    {
        $odoo_visibility_value = null;
        static $attribute_code;
        if(!isset($attribute_code)) {
            $attribute_code = Mage::getStoreConfig(Epoint_SwissPost_Catalog_Helper_Data::XML_CONFIG_VISIBILITY_ATTRIBUTE_CODE);
        }
        if($attribute_code){
            if($item[$attribute_code]){
                $odoo_visibility_value = $item[$attribute_code];
            }else{
                foreach ($item['dynamic_attributes'] as $attribute) {
                    if($attribute['attribute_name'] == $attribute_code){
                        $type = $attribute['attribute_type'];
                        $key = 'attribute_value_' . $type;
                        $odoo_visibility_value = $attribute[$key];
                        break;
                    }
                }
            }
        }
        return $odoo_visibility_value;
    }
}
