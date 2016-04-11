<?php

class Arena_Connector_Block_Adminhtml_Shipping_Grid extends Mage_Adminhtml_Block_Widget_Grid
{

    public function __construct()
    {
        parent::__construct();
        $this->setId("shippingGrid");
        $this->setDefaultSort("id");
        $this->setDefaultDir("DESC");
        $this->setSaveParametersInSession(true);
    }

    protected function _prepareCollection()
    {
        $collection = Mage::getModel("arena_connector/shipping")->getCollection();
        $this->setCollection($collection);
        return parent::_prepareCollection();
    }

    protected function _prepareColumns()
    {
        $this->addColumn("id", array(
            "header" => Mage::helper("arena_connector")->__("ID"),
            "align" => "right",
            "width" => "50px",
            "type" => "number",
            "index" => "id",
        ));

        $this->addColumn("arena_id", array(
            "header" => Mage::helper("arena_connector")->__("Arena ID"),
            "index" => "arena_id",
        ));
        $this->addColumn("arena_name", array(
            "header" => Mage::helper("arena_connector")->__("Arena Name"),
            "index" => "arena_name",
        ));

        $this->addColumn("store_id", array(
            "header" => Mage::helper("arena_connector")->__("Store"),
            "index" => "store_id",
            "type" => 'options',
            "options" => Mage::helper('arena_connector')->getStoreViews()
        ));
        $this->addColumn("shipping_code", array(
            "header" => Mage::helper("arena_connector")->__("Shipping Code"),
            "index" => "shipping_code",
        ));

        $this->addColumn("actions", array(
            "header" => Mage::helper("arena_connector")->__("Actions"),
            "index" => "actions",
            "renderer" => "Arena_Connector_Block_Adminhtml_Shipping_GridRenderer"
        ));

        return parent::_prepareColumns();
    }

    public function getRowUrl($row)
    {
        return $this->getUrl("*/*/edit", array("id" => $row->getId()));
    }


}