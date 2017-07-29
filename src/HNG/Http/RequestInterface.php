<?php

namespace HNG\Http;


interface RequestInterface {

    /**
     * Send a GET request to the URL.
     *
     * @param  $url
     * @param  $params
     * @return mixed
     */
    public function get($url, array $params);

    /**
     * Send a POST request to the URL.
     *
     * @param  $url
     * @param  $params
     * @param  $options
     * @return mixed
     */
    public function post($url, array $params, array $options);

    /**
     * Send a DELETE request to the URL.
     *
     * @param  $url
     * @param  $params
     * @param  $options
     * @return mixed
     */
    public function delete($url, array $params, array $options);

    /**
     * Send a PUT request to the URL.
     *
     * @param       $url
     * @param array $params
     * @param array $options
     * @return mixed
     */
    public function put($url, array $params, array $options);

}