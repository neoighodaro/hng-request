<?php

use HNG\Http;

if ( ! function_exists('crsRequest'))
{
    /**
     * Create a new Request instance using the guzzle driver.
     *
     * @param  array  $config
     * @return Http\Request
     */
    function crsRequest(array $config)
    {
        if (strtolower($config['driver']) === 'guzzle') {
            $driver  = new Http\GuzzleRequest([
                'base_url' => $config['base_url']
            ]);
        } else {
            throw new Exception("Unsupported request driver.");
        }

        return new Http\Request($driver, $config);
    }
}