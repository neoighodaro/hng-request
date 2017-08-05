# HNG Request

Make requests to the Hotels.ng CRS API easily.


### How to use

Pull in the package from composer `"shalvah/crs-request":"^1.1"`. That's all. A sample implementation is below.

```php
// Configuration details
$config  = [
  'base_url' => 'http://api.hng.tech',
  'client_id' => '12345',
  'client_secret' => 'SUPER_SECRET_CLIENT_SECRET',
  'scopes' => ['locations.read', 'hotels.read'],
  'storage_path' => '/tmp' // optional
  ];
$request = new HNG\Http\Request($config);
$hotels = $request->get('/hotels');
```

### Debugging

Everytime an error occurs, the error details are logged in the `log` directory of your `storage path` (if set, otherwise the top-level directory). Here is an example of the logged information:

```
[2016-12-05 03:16:52]
Endpoint: http://api.somesite.com/posts
Error:    cURL error 56: Problem (2) in the Chunked-Encoded data (see http://curl.haxx.se/libcurl/c/libcurl-errors.html)
```