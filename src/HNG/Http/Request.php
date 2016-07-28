<?php

namespace HNG\Http;

use HNG\Http\Exception;
use Exception as PhpException;

class Request {

    /**
     * @var RequestInterface
     */
    protected $client;

    /**
     * @var string
     */
    protected $session;

    /**
     * Request constructor.
     *
     * @param RequestInterface $client
     * @param array            $config
     * @param array            $session
     */
    public function __construct(RequestInterface $client, array $config = [], $session = null)
    {
        $this->client = $client;

        $this->config = array_merge([
            'client_id'     => '12345',
            'client_secret' => '12345',
            'base_url'      => 'http://crsapi.dev',
        ], $config);

        // Remove trailing slash...
        $this->config['base_url'] = rtrim($this->config['base_url'], '/');

        if (is_array($session) && ! empty($session)) {
            $this->setSession($session);
        }
    }

    /**
     * Send a GET request to the URL.
     *
     * @param        $url
     * @param  array $options
     * @return mixed
     * @throws Exception\InvalidCredentials
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
            'access_token' => $this->getSession('access_token')
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
            '_method'      => 'DELETE',
            'access_token' => $this->getSession('access_token')
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
            '_method'      => 'PUT',
            'access_token' => $this->getSession('access_token')
        ]);

        return $this->request('POST', $url, $params, $options);
    }

    /**
     * Get the access token.
     *
     * @param  array $session
     */
    public function setSession(array $session)
    {
        $this->session = (object) $session;
    }

    /**
     * Get the session detail(s).
     *
     * @param  string $key
     * @return string|object
     */
    public function getSession($key = null)
    {
        if ($key) {
            return is_string($key) && isset($this->session->{$key})
                ? $this->session->{$key}
                : 'no_access_token_set';
        }

        return $this->session;
    }


    /**
     * Send a request to the server.
     *
     * @param        $method
     * @param        $url
     * @param  array $params
     * @param  array $options
     * @return mixed
     * @throws PhpException
     * @throws Exception\InvalidCredentials
     * @throws Exception\RequiresAuthentication
     */
    protected function request($method, $url, array $params = [], array $options = [])
    {
        // Prepend slash to the url...
        $url = '/'.ltrim($url, '/');

        $authenticatedUrl = $this->addAccessTokenToUrl($url);

        try {
            $response = $this->sendRequest($method, $authenticatedUrl, $params, $options);
            $this->responseCheck($response);
        } catch (Exception\RequiresAuthentication $e) {
            $this->getAccessTokenFromServer();

            $authenticatedUrl = $this->addAccessTokenToUrl($url);

            if (strtolower($method) !== 'get') {
                $params = array_merge($params, [
                    '_method'      => strtoupper($method),
                    'access_token' => $this->getSession('access_token'),
                ]);
            }

            $response = $this->sendRequest($method, $authenticatedUrl, $params, $options);
            $this->responseCheck($response);
        } catch (PhpException $e) {
            throw $e;
        }

        return $response;
    }

    /**
     * @param $response
     * @throws PhpException
     * @throws Exception\InvalidCredentials
     * @throws Exception\RequiresAuthentication
     */
    protected function responseCheck($response)
    {
        if ( ! is_object($response)) {
            throw new Exception\RequiresAuthentication("Request requires a JSON object as a response.");
        }

        if (isset($response->error)) {
            $errorMessage = $response->error_description;
            switch ($response->error) {
                case 'access_denied':
                    throw new Exception\RequiresAuthentication($errorMessage);
                    break;
                default:
                    throw new Exception\InvalidCredentials($errorMessage);
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
            $response = null;
        }

        return $response;
    }

    /**
     * Get the access token from the server.
     *
     * @throws Exception
     */
    protected function getAccessTokenFromServer()
    {
        // Generate the authentication URL...
        $authUrl = sprintf(
            '%s/oauth/authenticate?grant_type=client_credentials&client_id=%s&client_secret=%s',
            $this->config['base_url'],
            $this->config['client_id'],
            $this->config['client_secret']
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

        if ( ! isset($session->access_token) OR ! isset($session->expires_in) OR ! isset($session->token_type)) {
            throw new Exception\RequiresAuthentication("Invalid client ID and secret.");
        }

        $this->setSession( (array) $session);
    }

    /**
     * @param $url
     * @return string
     */
    protected function addAccessTokenToUrl($url)
    {
        if ($this->urlHasNoAccessToken($url)) {
            $accessToken = $this->getSession('access_token');

            $url = sprintf('%s' . $url, $this->config['base_url']);

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
        return strpos($url, 'access_token=') === false;
    }
}