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
        $multipart = array_get($options, 'multipart', false);

        if($multipart === true){
            $params_multi = [];
            foreach ($params as $key => $value) {
                if ($key == 'image'){
                    array_push($params_multi, ['name'=>$value['name'],'contents' => $value['contents'],'filename' => $value['filename']]);
                }else{
                    array_push($params_multi, ['name' => $key, 'contents'=>$value]);
                }
            }
            $payload = ['multipart' => $params_multi];
        }else{
            $payload = array_merge($options, ['form_params' => $params]);
        }

        return $this->request('post', $url, $payload);
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
        $payload = array_merge($options, ['form_params' => $params]);

        return $this->request('delete', $url, $payload);
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
        $payload = array_merge($options, ['form_params' => $params]);

        return $this->request('put', $url, $payload);
    }

    /**
     * Send request using Guzzle.
     *
     * @param       $method
     * @param       $url
     * @param array $params
     * @return mixed
     */
    protected function request($method, $url, array $params = [])
    {
        if ($method === 'get') {
            $response = $this->client->get($url, $params);
        } else {
            $response = $this->client->{$method}($url, $params);
        }

        return json_decode((string) $response->getBody());
    }
}
