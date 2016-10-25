<?php

class Arena_Connector_Model_Transformer_Status extends Arena_Connector_Model_Transformer_Abstract
{
    protected static $_optionsMicrocache = array();

    /**
     * @param $model Mage_Catalog_Model_Product
     * @return array
     */
    public function transform($model)
    {
        $options = $this->getAttributeOptions($model, 'arena_availability');
        /**
         * @var Mage_CatalogInventory_Model_Stock_Item::isInStock
         */
        $stock = Mage::getModel('cataloginventory/stock_item')->loadByProduct($model);
        $option = $options[(string) $model->getData('arena_availability')];
        if (is_object($option)) {
            $avail = $option->getData('sort_order');
        } else {
            $avail = $option;
        }

        if(!$model->getArenaSyncFlag()){
            $avail = 99;
        }

        $qty = $stock->getQty();
        if (!$stock->getIsInStock()) {
            $avail = 99;
        } elseif ($stock->getQty() <= 0) {
            $qty = (int) Mage::getStoreConfig('arena_api/arena_api_config/qty_when_in_stock_but_0', $model->getStoreId());
        }

        $price = (int) bcmul($model->getPrice(), 100);

        return array(
            'product_status' => array(
                'id' => $model->getId(),
                'quantity' => $qty,
                'availability' => $avail,
                'price' => $price
            ),
        );
    }

    /**
     * @param $model
     * @param $code
     * @return mixed
     */
    public function getAttributeOptions($model, $code)
    {
        if (isset(self::$_optionsMicrocache[$code])) {
            return self::$_optionsMicrocache[$code];
        }

        $options = Mage::getResourceModel('eav/entity_attribute_option_collection')
            ->setAttributeFilter($model->getResource()->getAttribute($code)->getId())
            ->setStoreFilter()
            ->setPositionOrder('ASC');
        $items = array('' => '7') + $options->getItems();
        self::$_optionsMicrocache[$code] = $items;

        return self::$_optionsMicrocache[$code];
    }
}
