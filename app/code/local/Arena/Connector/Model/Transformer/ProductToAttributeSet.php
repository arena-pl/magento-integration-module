<?php

class Arena_Connector_Model_Transformer_ProductToAttributeSet extends Arena_Connector_Model_Transformer_Abstract
{
    protected $swatches = null;

    /**
     * @param Mage_Catalog_Model_Product $model
     *
     * @return array
     */
    public function transform($model)
    {
        if ($this->swatches === null) {
            $this->swatches = explode(',', Mage::getStoreConfig('configswatches/general/swatch_attributes', $model->getStoreId()));
        }

        $attribs = array();
        $attributeSet = Mage::getModel('eav/entity_attribute_set')->load($model->getAttributeSetId());

        $configurableKeyPart = array();

        if ('configurable' === $model->getTypeId()) {
            $parent = $model;
        } else {
            $parent = $this->getParent($model);
        }

        if ($parent) {
            $productAttributeOptions = $parent->getTypeInstance(false)
                ->getConfigurableAttributes($parent);
            foreach ($productAttributeOptions as $configurableAttribute) {
                $name = $configurableAttribute->getProductAttribute()->getAttributeCode();
                $attribs[$name] = array(
                    'usedInVariants' => true,
                    'label' => $configurableAttribute->getProductAttribute()->getStoreLabel(),
                );
                if (in_array($configurableAttribute->getProductAttribute()->getId(), $this->swatches)) {
                    $attribs[$name]['isSwatch'] = true;
                }
                if ($name === 'manufacturer') {
                    $attribs[$name]['isManufacturer'] = true;
                }
                $configurableKeyPart[] = $name;
            }
        }

        $nonConfigurablePart = array();
        foreach ($model->getAttributes() as $attribute) {
            if ($attribute->getIsVisibleOnFront()) {
                if (!isset($attribs[$attribute->getAttributeCode()])) {
                    $nonConfigurablePart[] = $attribute->getAttributeCode();
                    $attribs[$attribute->getAttributeCode()] = array(
                        'label' => $attribute->getStoreLabel(),
                    );
                    if ($attribute->getAttributeCode() === 'manufacturer') {
                        $attribs[$attribute->getAttributeCode()]['isManufacturer'] = true;
                    }
                }
            }
        }

        foreach ($attribs as $code => $attribute) {
            $attribs[$code]['name'] = $code;
        }
        $attribs = array_values($attribs);
        sort($nonConfigurablePart);
        $toReturn = array('attribute_group' => array(
            'name' => $attributeSet->getData('attribute_set_name').($configurableKeyPart ? ' ('.implode('/', $configurableKeyPart).')' : ''),
            'key' => $attributeSet->getId().':'.implode('/', $nonConfigurablePart).':'.implode('/', $configurableKeyPart),
            'attributes' => $attribs,
            ),
        );

        $model->setData('calculated_arena_attribute_group', $toReturn);

        return $toReturn;
    }
}
