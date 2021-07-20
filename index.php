<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Slim\Factory\AppFactory;
use Slim\Routing\RouteCollectorProxy;

require __DIR__ . '/vendor/autoload.php';

$app = AppFactory::create();
$app->setBasePath('/slimapi2');


class CURL
{
    static function call_api_get_internal($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        if (curl_error($ch)) die("Connection Error: " . curl_errno($ch) . ' - ' . curl_error($ch));
        curl_close($ch);
        return json_decode($response);
    }
}
class CURLNS
{
    private $ch;

    function __construct()
    {
        $this->ch = curl_init();
    }

    public function getAPI($url)
    {
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($this->ch);
        if (curl_error($this->ch)) die("Connection Error: " . curl_errno($this->ch) . ' - ' . curl_error($this->ch));
        curl_close($this->ch);
        return json_decode($response);
    }
}


//v1 calls 
$app->group('/v1', function (RouteCollectorProxy $group1) {
    // what ever is sent in /v1/'api1' api1 gets responded out
    $group1->get('/{api1}', function (Request $request, Response $response, $args) {
        $response->getBody()->write(json_encode($args));
        return $response;
    });
});

//v2 calls
$app->group('/v2', function (RouteCollectorProxy $group2) {
    //CURL is a static class method
    //what ever is sent in /v2/static/'api2' <- api2, the api calls /v1/'api2' service from it
    $group2->get('/static/{api2}', function (Request $request, Response $response, $args) {
        $start = microtime(true);
        $apires = CURL::call_api_get_internal('http://localhost/slimapi2/v1/' . $args['api2']);
        $data = [$args, $apires];
        $end = microtime(true);
        array_push($data, array("time" => $end - $start));
        $response->getBody()->write(json_encode($data));
        return $response;
    });
    //same as above but CURL is a non-stati class method
    //what ever is sent in /v2/nsstatic/'api2' <- api2, the api calls /v1/'api2' service from it
    $group2->get('/nstatic/{api2}', function (Request $request, Response $response, $args) {
        $start = microtime(true);
        $curl1 = new CURLNS();
        $apires = $curl1->getAPI('http://localhost/slimapi2/v1/' . $args['api2']);
        $data = [$args, $apires];
        $end = microtime(true);
        array_push($data, array("time" => $end - $start));
        $response->getBody()->write(json_encode($data));
        return $response;
    });
});

$app->run();
