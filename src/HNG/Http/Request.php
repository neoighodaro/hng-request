<?php

namespace HNG\Http;

use Carbon\Carbon;
use HNG\Http\Exception;
use Exception as PhpException;

class Request
{

    /**
     * @var RequestInterface
     */
    protected $client;

    /**
     * @var  \stdClass
     */
    private static $session;

    /**
     * @var Carbon
     */
    private static $authenticatedAt;

    /**
     * @var string
     */
    protected $authenticatedUrl;

    /**
     * Scope separator.
     */
    const SCOPE_SEPARATOR = ',';

    /**
     * Request constructor.
     *
     * @param array $config
     */
    public function __construct($clientOrConfig = [], $config = null, $session = null)
    {
        // for backward compatibility
        if ($clientOrConfig instanceof RequestInterface) {
            $this->client = $clientOrConfig;
        } else if (is_array($clientOrConfig)) {
            $config = $clientOrConfig;
            $this->client = new GuzzleRequest($config);
        }
        $this->config = array_merge([
            'client_id' => '12345',
            'client_secret' => '12345',
            'base_url' => 'http://localhost',
            'scopes' => [],
            'storage_path' => '',
        ], $config);

        // Standardise scopes...
        $this->config['scopes'] = implode(static::SCOPE_SEPARATOR, $this->config['scopes']);

        // Remove trailing slash...
        $this->config['base_url'] = rtrim($this->config['base_url'], '/');
    }

    /**
     * Send a GET request to the URL.
     *
     * @param        $url
     * @param  array $options
     * @return mixed
     * @throws Exception\InvalidRequest
     */
    public function get($url, array $options = [])
    {
        return $this->request('GET', $url, $options);
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
        $params = array_merge($params, [
            'access_token' => $this->getToken()
        ]);

        return $this->request('POST', $url, $params, $options);
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
        $params = array_merge($params, [
            '_method' => 'DELETE',
            'access_token' => $this->getToken()
        ]);

        return $this->request('DELETE', $url, $params, $options);
    }

    /**
     * Send a PUT request to the URL.
     *
     * @param  $url
     * @param  $params
     * @param  $options
     * @return mixed
     */
    public function put($url, array $params = [], array $options = [])
    {
        $params = array_merge($params, [
            '_method' => 'PUT',
            'access_token' => $this->getToken()
        ]);

        return $this->request('POST', $url, $params, $options);
    }

    /**
     * Get the session detail(s).
     *
     * @param  string $key
     * @return string|object
     */
    protected function getSession()
    {
        if (!static::$session || $this->sessionHasExpired()) {
            $this->renewSession();
        }
        return static::$session;
    }

    /**
     * Send a request to the server.
     *
     * @param        $method
     * @param  string $url Raltive url
     * @param  array $params
     * @param  array $options
     * @return mixed
     * @throws PhpException
     * @throws Exception\InvalidRequest
     * @throws Exception\RequiresAuthentication
     */
    protected function request($method, $url, array $params = [], array $options = [])
    {
        // Prepend slash to the url...
        $url = '/' . ltrim($url, '/');
        $authenticatedUrl = $this->getCompleteUrl(
            $this->addAccessTokenToUrl($url));

        try {
            $response = $this->sendRequest($method, $authenticatedUrl, $params, $options);
            $this->responseCheck($response);
        } catch (Exception\RequiresAuthentication $e) {
            $this->renewSession();
            $authenticatedUrl = $this->getCompleteUrl(
                $this->addAccessTokenToUrl($url));

            if (strtolower($method) !== 'get') {
                $params = array_merge($params, [
                    '_method' => strtoupper($method),
                    'access_token' => $this->getToken(),
                ]);
            }

            $response = $this->sendRequest($method, $authenticatedUrl, $params, $options);

            $this->responseCheck($response, ['url' => $authenticatedUrl, 'params' => $params, 'options' => $options]);
        } catch (PhpException $e) {
            throw $e;
        }

        return $response;
    }

    /**
     * @param $response
     * @throws PhpException
     * @throws Exception\InvalidRequest
     * @throws Exception\RequiresAuthentication
     */
    protected function responseCheck($response, $debugData = [])
    {
        if (!is_object($response) AND !is_array($response)) {
            if (!empty($debugData)) {
                $this->logError(json_encode($debugData), $response);
            }

            throw new Exception\RequiresAuthentication("Request requires a JSON object as a response.");
        }

        if (isset($response->error)) {
            $errorMessage = (isset($response->error_description)) ? $response->error_description : '';

            if ($errorMessage === '' && is_string($response->error)) {
                $errorMessage = $response->error;
            }

            switch ($response->error) {
                case 'access_denied':
                    throw new Exception\RequiresAuthentication($errorMessage);
                    break;
                default:
                    throw new Exception\InvalidRequest($response, $errorMessage);
                    break;
            }
        }
    }

    /**
     * @param       $method
     * @param       $url
     * @param array $params
     * @param array $options
     * @return mixed
     */
    protected function sendRequest($method, $url, array $params, array $options)
    {
        $this->authenticatedUrl = $url;

        try {
            switch (strtolower($method)) {
                case 'post':
                    $response = $this->client->post($url, $params, $options);
                    break;
                case 'put':
                    $response = $this->client->put($url, $params, $options);
                    break;
                case 'delete':
                    $response = $this->client->delete($url, $params, $options);
                    break;
                default:
                    $response = $this->client->get($url, $params);
                    break;
            }
        } catch (PhpException $e) {
            $this->logError($e->getMessage());

            $response = null;
        }

        return $response;
    }

    /**
     * Log errors.
     */
    protected function logError($msg, $response = null)
    {
        $logFolder = rtrim(realpath($this->config['storage_path']) . '/logs', '/');

        // Create logs folder if it does not exist
        if (!is_dir($logFolder)) {
            mkdir($logFolder);
        }

        $logFile = $logFolder . '/error.log';

        $url = $this->authenticatedUrl ? $this->authenticatedUrl : null;

        // Format error message
        $msg = '[' . date('Y-m-d h:i:s') . ']' . PHP_EOL .
            "Endpoint: {$url}" . PHP_EOL .
            "Error:    {$msg}" . PHP_EOL .
            "Response: ".print_r($response, true). PHP_EOL.
            '---------' . PHP_EOL;

        file_put_contents($logFile, $msg, LOCK_EX | FILE_APPEND);
    }

    /**
     * Get the access token from the server.
     *
     * @throws PhpException
     */
    protected function getNewSessionFromServer()
    {
        // Generate the authentication URL...
        $authUrl = sprintf(
            '%s/oauth/authenticate?grant_type=client_credentials&client_id=%s&client_secret=%s&scope=%s',
            $this->config['base_url'],
            $this->config['client_id'],
            $this->config['client_secret'],
            rawurlencode($this->config['scopes'])
        );

        try {
            // -------------------------
            // Expected Response
            // -------------------------
            // {
            //     "access_token": "SomeRandomValue",
            //     "token_type": "Bearer",
            //     "expires_in": 3600
            // }
            $session = $this->client->get($authUrl);
        } catch (PhpException $e) {
            $session = null;
        }

        if (!isset($session->access_token) OR !isset($session->expires_in) OR !isset($session->token_type)) {
            throw new Exception\RequiresAuthentication("Invalid client ID and secret.");
        }
        return $session;
    }

    /**
     * @param $url string
     * @return string Full url
     */
    protected function getCompleteUrl($url)
    {
        // if the url is already complete, just return it
        $scheme = parse_url($url, PHP_URL_SCHEME);
        return empty($scheme ) ? $this->config['base_url'] . $url : $url;
    }

    /**
     * @param $url string
     * @return string
     */
    protected function addAccessTokenToUrl($url)
    {
        if ($this->urlHasNoAccessToken($url)) {
            $accessToken = $this->getToken();

            $query = parse_url($url, PHP_URL_QUERY);
            $url .= ($query ? '&' : '?') . 'access_token=' . $accessToken;
        }

        return $url;
    }

    /**
     * @param $url
     * @return bool
     */
    protected function urlHasNoAccessToken($url)
    {
        $queryString = parse_url($url, PHP_URL_QUERY);
        $query = [];
        parse_str($queryString, $query);
        return !isset($query['access_token']) || empty($query['access_token']);
    }

    protected function getToken()
    {
        return $this->getSession()->access_token;
    }

    protected function sessionHasExpired()
    {
        return static::$authenticatedAt->addSeconds(static::$session->expires_in)->greaterThan(Carbon::now());
    }

    protected function renewSession()
    {
        static::$session = $this->getNewSessionFromServer();
        static::$authenticatedAt = Carbon::now();
    }
}