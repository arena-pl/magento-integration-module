<?php

abstract class Arena_Connector_Model_Transformer_Abstract
{
    /**
     * @param Mage_Core_Model_Abstract $model
     *
     * @return array
     */
    abstract public function transform($model);

    /**
     * @param Mage_Catalog_Model_Product $product
     *
     * @return Mage_Catalog_Model_Product|null
     */
    public function getParent(Mage_Catalog_Model_Product $product)
    {
        $parents = Mage::getModel('catalog/product_type_configurable')->getParentIdsByChild($product->getId());
        foreach ($parents as $p) {
            $parent = Mage::getModel('catalog/product')->setStoreId($product->getStoreId())->load($p);
            if ($parent->getTypeId() == 'configurable') {
                break;
            }
        }

        return isset($parent) ? $parent : null;
    }

    public function getChildren(Mage_Catalog_Model_Product $product)
    {
        return Mage::getModel('catalog/product_type_configurable')->getUsedProducts(null, $product);
    }
}
