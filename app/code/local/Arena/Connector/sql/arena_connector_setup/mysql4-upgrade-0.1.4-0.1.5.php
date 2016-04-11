<?php

$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$attributeSetIds = array();

$entityTypeId = Mage::getResourceModel('catalog/product')->getTypeId();
$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')
    ->setEntityTypeFilter($entityTypeId);

foreach ($attributeSetCollection as $_attributeSet) {
    $attributeSetIds[] = $_attributeSet->getId();
}

$attributeSetIds = array_unique($attributeSetIds);

$attr = array(
    'attribute_model' => null,
    'backend' => '',
    'type' => 'int',
    'table' => '',
    'frontend' => '',
    'input' => 'select',
    'label' => 'Arena.pl Availability',
    'frontend_class' => '',
    'source' => '',
    'required' => '0',
    'user_defined' => '0',
    'default' => '',
    'unique' => '0',
    'note' => '',
    'input_renderer' => null,
    'global' => '1',
    'visible' => '1',
    'searchable' => '0',
    'filterable' => '0',
    'comparable' => '0',
    'visible_on_front' => '0',
    'is_html_allowed_on_front' => '0',
    'is_used_for_price_rules' => '0',
    'filterable_in_search' => '0',
    'used_in_product_listing' => '0',
    'used_for_sort_by' => '0',
    'is_configurable' => '0',
    'apply_to' => 'simple',
    'visible_in_advanced_search' => '0',
    'position' => '100',
    'wysiwyg_enabled' => '0',
    'used_for_promo_rules' => '0',
    'option' => array(
        'values' => array(
            1 => '24h',
            3 => 3,
            7 => 7,
            14 => 14,
            28 => 28,
            99 => 'Unavailable',
        ),
    ),
);

$installer->addAttribute('catalog_product', 'arena_availability', $attr);
foreach ($attributeSetIds as $attributeSetId) {
    $installer->addAttributeToGroup('catalog_product', $attributeSetId, 'General', 'arena_availability');
}

$installer->endSetup();
