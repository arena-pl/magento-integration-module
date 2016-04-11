<?php

class Arena_Connector_Model_Api_Exception_UnexpectedResult extends Exception
{
    protected $productId;
    public function __construct($body, $productId)
    {
        parent::__construct($body);
        $this->setProductId($productId);
    }

    /**
     * @return mixed
     */
    public function getProductId()
    {
        return $this->productId;
    }

    /**
     * @param mixed $productId
     * @return Arena_Connector_Model_Api_Exception_UnexpectedException
     */
    public function setProductId($productId)
    {
        $this->productId = $productId;
        return $this;
    }
}
