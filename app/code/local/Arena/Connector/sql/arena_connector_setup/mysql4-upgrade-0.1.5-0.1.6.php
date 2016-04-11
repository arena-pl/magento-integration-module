<?php
$installer = Mage::getResourceModel('catalog/setup', 'catalog_setup');
$installer->startSetup();

$attributeSetIds = array();

$entityTypeId           = Mage::getResourceModel('catalog/product')->getTypeId();
$attributeSetCollection = Mage::getResourceModel('eav/entity_attribute_set_collection')
    ->setEntityTypeFilter($entityTypeId);

foreach ($attributeSetCollection as $_attributeSet) {
    $attributeSetIds[] = $_attributeSet->getId();
}

$attributeSetIds = array_unique($attributeSetIds);

// Configuration:
$data = array(
    'type'      => 'varchar',
    'input'     => 'text',
    'global'    => Mage_Catalog_Model_Resource_Eav_Attribute::SCOPE_STORE,
    'required'  => false,
    'user_defined' => false,
    'searchable' => false,
    'filterable' => false,
    'comparable' => false,
    'visible_on_front' => false,
    'unique' => false,
    'used_in_product_listing' => false,
    'default' => '',
    'label' => 'Arena.pl ID'
);

$installer->addAttribute('catalog_product', 'arena_id', $data);
foreach($attributeSetIds as $attributeSetId)
{
    $installer->addAttributeToGroup('catalog_product', $attributeSetId, 'General', 'arena_id');
}

$installer->endSetup();
