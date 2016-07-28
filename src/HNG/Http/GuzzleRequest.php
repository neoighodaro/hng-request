<?php

namespace HNG\Http;

use GuzzleHttp\Client;

class GuzzleRequest implements RequestInterface {

    /**
     * @var Client
     */
    protected $client;

    /**
     * GuzzleRequest constructor.
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        $this->client = new Client($config);
    }

    /**
     * Send a GET request to the URL.
     *
     * @param  $url
     * @param  $options
     * @return mixed
     */
    public function get($url, array $options = [])
    {
        return $this->request('get', $url, $options);
    }

    /**
     * Send a POST request to the URL.
     *
     * @param  $url
     * @param  $params
     * @param  $options
     * @return mixed
     */
    public function post($url, array $params = [], array $options = [])
    {
        return $this->request('post', $url, $params, $options);
    }

    /**
     * Send a DELETE request to the URL.
     *
     * @param  $url
     * @param  $params
     * @param  $options
     * @return mixed
     */
    public function delete($url, array $params = [], array $options = [])
    {
        return $this->request('delete', $url, $params, $options);
    }

    /**
     * Send a PUT request to the URL.
     *
     * @param       $url
     * @param array $params
     * @param array $options
     * @return mixed
     */
    public function put($url, array $params = [], array $options = [])
    {
        return $this->request('put', $url, $params, $options);
    }

    /**
     * Send request using Guzzle.
     *
     * @param       $method
     * @param       $url
     * @param array $params
     * @param array $options
     * @return mixed
     */
    protected function request($method, $url, array $params = [], array $options = [])
    {
        if ($method === 'get') {
            $response = $this->client->get($url, $options);
        } else {
            $response = $this->client->{$method}($url, $params, $options);
        }

        return json_decode((string) $response->getBody());
    }
}