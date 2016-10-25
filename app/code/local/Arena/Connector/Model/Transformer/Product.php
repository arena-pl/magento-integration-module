<?php

class Arena_Connector_Model_Transformer_Product extends Arena_Connector_Model_Transformer_Abstract
{
    /**
     * @param Mage_Catalog_Model_Product $model
     *
     * @return array
     */
    public function transform($model)
    {
        $parent = $this->getParent($model);

        if ($model->getData('calculated_arena_attribute_group')) {
            $attributeSet = $model->getData('calculated_arena_attribute_group');
            $attributeGroup = $attributeSet['attribute_group']['key'];
        } else {
            $attributeSet = Mage::getModel('arena_connector/transformer_productToAttributeSet')->transform($model);
            $attributeGroup = $attributeSet['attribute_group']['key'];
        }

        $attributes = array();
        foreach ($attributeSet['attribute_group']['attributes'] as $data) {
            $value = $model->getAttributeText($data['name']);
            if ($value === false) {
                $value = $model->getData($data['name']);
            }
            $attributes[] = array('name' => $data['name'], 'value' => $value);
        }

        $statusTransport = Mage::getSingleton('arena_connector/transformer_status')->transform($model);
        $status = $statusTransport['product_status'];

        return array(
            'product' => array(
                'id' => $model->getId(),
                'name' => $model->getName(),
                'parent' => $parent ? $parent->getData('arena_id') : '',
                'attributeGroup' => $attributeGroup,
                'type' => $model->getTypeId(),
                'category' => $this->getCategory($model, $parent),
                'description' => $model->getDescription(),
                'weight' => $model->getWeight(),
                'price' => (int) bcmul($model->getPrice(), 100),
                'shippingMethodBoundary' => '',
                'attributes' => $attributes,
                'forceMove' => '1',
                'quantity' => $status['quantity'],
                'availability' => $status['availability']
            ),
        );
    }

    public function getCategory(Mage_Catalog_Model_Product $product, $parent = null)
    {
        $categoryCollection = $product->getCategoryCollection();
        if (count($categoryCollection) == 0 && $parent) {
            $categoryCollection = $parent->getCategoryCollection();
        }

        $max = null;
        $_cat = null;
        foreach ($categoryCollection as $cat) {
            if ($cat->getLevel() > $max || $max === null) {
                $max = $cat->getLevel();
                $_cat = $cat;
            }
        }

        if ($_cat) {
            $_cat = Mage::getModel('catalog/category')->load($_cat->getId());
            $names = array($_cat->getName());
            while ($_cat->getParentId()) {
                $_cat = Mage::getModel('catalog/category')->load($_cat->getParentId());
                $names[] = $_cat->getName();
            }
        }
        $skip = Mage::getStoreConfig('arena_api/arena_api_config/category_nodes_to_skip', $product->getStoreId());
        if (!is_numeric($skip)) {
            $skip = 0;
        }
        for ($i = 0; $i < $skip; ++$i) {
            array_pop($names);
        }
        $names = array_reverse($names);
        $names = implode('/', $names);

        return $names;
    }
}
