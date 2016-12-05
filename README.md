# HNG Request

Make requests to the HNG CRS api easily.


### How to use basically

Pull in the package from composer `"neo/crs-request":"1.*"`. Thats all. A sample implementation is below.

    <?php

    // Helper functions.... you can implement this however you want...
    function saveCrsApiSession(array $session) {
        $path = 'path/to/storage/accesstoken.txt';
        file_put_contents($path, json_encode($session));
    }

    function getCrsApiSession() {
        $path = 'path/to/storage/accesstoken.txt';
        if (file_exists($path)) {
            return json_decode(file_get_contents($path), true);
        }

        return [];
    }


    // Using Lumen Router...
    try {

        // Configuration details...
        $config  = [
            'base_url'      => 'http://crsapi.dev',
            'client_id'     => '12345',
            'client_secret' => 'SUPER_SECRET_CLIENT_SECRET',
            'scopes'        => ['scope1', 'scope2'],
        ];

        // Use Guzzle...
        $guzzle  = new HNG\Http\GuzzleRequest($config);

        // Get the stored session details and set it to the request class,
        // This is to avoid multiple calls to the service for authentication.
        $previouslyStoredSession = getCrsApiSession();

        // Create a request instance...
        $request = new HNG\Http\Request($guzzle, $config, $previouslyStoredSession);

        $bookings = $request->get('/bookings');

        saveCrsApiSession((array)$request->getSession());
    } catch (\Exception $e) {
        // This should not really happen except your credentials are wrong...
        throw $e;
    }

### Debugging

Everytime an error occurs, the error details are logged in the `log` directory of your storage path set above. Here is an example of the logged information:

```
[2016-12-05 03:16:52]
Endpoint: http://api.somesite.com/posts
Error:    cURL error 56: Problem (2) in the Chunked-Encoded data (see http://curl.haxx.se/libcurl/c/libcurl-errors.html)
```