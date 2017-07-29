<?php
namespace HNG\Http;

use Exception;
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

if (! function_exists('array_get'))
{
  /*
   *
   * @param array  $data
   * @param string $key
   * @param string $default
   *
   * @return mixed
   */
   function array_get($data, $key, $default = false) {
     if (!is_array($data)) {
         return $default;
     }
     return isset($data[$key]) ? $data[$key]: $default;
   }
}
