<?php

namespace App\Services;

use GuzzleHttp\Client;
use mysql_xdevapi\Exception;

class ClientService extends Singleton
{
    # 请求方式
    const REQUEST_METHOD_GET  = 'GET';
    const REQUEST_METHOD_POST = 'POST';

    protected $client;
    protected $baseUri;
    protected $api    = '';
    protected $data   = [];
    protected $method = self::REQUEST_METHOD_GET;

    protected function __construct()
    {
        parent::__construct();
    }

    /**
     * Notes: client
     * @return $this
     */
    public function connection($baseUri): ClientService
    {
        $this->setBaseUri($baseUri);

        $this->client = new Client([
            'base_uri' => $this->baseUri,
            'verify'   => false,
            'timeout'  => 30
        ]);

        return $this;
    }

    public function set($api, $params)
    {
        $this->setApi($api);
        $this->setParams($params);
        return $this;
    }

    /**
     * Notes: request
     * @return mixed
     */
    public function request()
    {
        # 若没有初始化 client 使用默认
        if (is_null($this->client)) throw new Exception('The client is not init, Connection first...');

        if ($this->method === self::REQUEST_METHOD_GET) {
            $options = ['query' => $this->data];
        } else {
            $options = ['json' => $this->data];
        }
        $response = $this->client->request($this->method, $this->api, $options);
        return json_decode($response->getBody()->getContents(), true);
    }

    public function get()
    {
        $this->method = self::REQUEST_METHOD_GET;
        return $this->request();
    }

    public function post()
    {
        $this->method = self::REQUEST_METHOD_POST;
        return $this->request();
    }

    public function setParams($params = []): ClientService
    {
        $this->data = $params;
        return $this;
    }

    public function setApi($api = ''): ClientService
    {
        $this->api = $api;
        return $this;
    }

    public function setBaseUri($baseUri): ClientService
    {
        $this->baseUri = $baseUri;
        return $this;
    }


}
