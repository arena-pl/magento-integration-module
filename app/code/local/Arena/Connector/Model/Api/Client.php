<?php

class Arena_Connector_Model_Api_Client
{
    protected $clients = array();
    protected $auths = array();

    protected function getRestClient($store)
    {
        $store = $this->getStore($store);

        if (!array_key_exists($store->getCode(), $this->clients)) {
            $key = Mage::getStoreConfig('arena_api/arena_api_credentials/api_key', $store);
            $login = Mage::getStoreConfig('arena_api/arena_api_credentials/api_login', $store);
            $this->auths[$store->getCode()] = array('login' => $login, 'apiKey' => $key);
            $endpoint = Mage::getStoreConfig('arena_api/arena_api_credentials/api_endpoint', $store);

            $this->clients[$store->getCode()] = new Zend_Rest_Client($endpoint);

            $httpClient = $this->clients[$store->getCode()]->getHttpClient();
            $httpClient->setConfig(array('timeout' => 30));
            $this->clients[$store->getCode()]->setHttpClient($httpClient);

        }

        return $this->clients[$store->getCode()];
    }

    /**
     * @param $store
     * @param int $page
     * /api/sellers/orders/{page}/gets.json
     */
    public function getOrders($store, $page = 1, $orderUpdatedAt = false)
    {
        $client = $this->getRestClient($store);
        $data = array('auth' => $this->getAuthForStore($store));
        if ($orderUpdatedAt) {
            $data['sort'] = 'updated-at';
        }
        $result = $client->restPost('/api/sellers/orders/'.$page.'/gets.json', $data);
        if ($result->getStatus() == 200) {
            return json_decode($result->getBody(), true);
        }
        throw new Arena_Connector_Model_Api_Exception_UnexpectedResult($result->getBody(), null);
    }

    /**
     * /api/products/attributes/groups/updates.json.
     */
    public function updateAttributeGroup($data, $store)
    {
        $client = $this->getRestClient($store);
        $data['attribute_group']['auth'] = $this->getAuthForStore($store);

        return $client->restPost('/api/products/attributes/groups/updates.json', $data);
    }

    /**
     * /api/products/products/updates/v2s.json.
     */
    public function updateProduct($data, $store)
    {
        $client = $this->getRestClient($store);
        $data['product']['auth'] = $this->getAuthForStore($store);

        return $client->restPost('/api/products/products/updates/v2s.json', $data);
    }

    /**
     * /api/products/products/statuses/updates.json.
     */
    public function updateStatus($data, $store)
    {
        $client = $this->getRestClient($store);
        $data['product_status']['auth'] = $this->getAuthForStore($store);

        return $client->restPost('/api/products/products/statuses/updates.json', $data);
    }

    /**
     * /api/products/products/multiples/pictures/adds.json.
     */
    public function updatePictures($data, $store)
    {
        $client = $this->getRestClient($store);
        $data['product_pictures']['auth'] = $this->getAuthForStore($store);

        return $client->restPost('/api/products/products/multiples/pictures/adds.json', $data);
    }

    public function getShippingMethods($store)
    {
        /*
         * /sellers/shippings/methods.json
         *
         */

        $client = $this->getRestClient($store);
        $data = array('auth' => $this->getAuthForStore($store));

        return $client->restPost('/api/sellers/shippings/methods.json', $data);
    }

    public function getStore($store)
    {
        if (!is_object($store)) {
            $store = Mage::app()->getStore($store);
        }

        return $store;
    }

    public function getAuthForStore($store)
    {
        $store = $this->getStore($store);

        return $this->auths[$store->getCode()];
    }
}
