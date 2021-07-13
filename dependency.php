<?php

namespace Bookstore\Utils;

use Bookstore\Exceptions\NotFoundException;

class DependencyInjector {
    private $dependencies = [];

    public function set(string $name, $object) {
        $this -> dependencies[$name] = $object;
    }

    public function get(string $name) {
        if (isset ($this -> dependencies[$name])) {
            return $this ->dependencies[$name];
        }

        throw new NotFoundException (
            $name . ' dependency not found.'
        );
    }
}

?>

<?php
namespace Bookstore\Core;

use Bookstore\Exceptions\NotFoundException;

class Config {
    private $data;

    public function __construct() {
        $json = file_get_contents (
            __DIR__ . '/../../config/app.json'
        );

        $this -> data = json_decode($json, true);
    }

    public function get($key) {
        if (!isset($this->data[$key])) {
            throw new NotFoundException("Key $key not in config.");
        }
        return $this -> data[$key];
    }
}

$config = new Config();

$dbConfig = $config->get('db');
$db = new PDO (
    'mysql:host = 127.0.0.1;dbname=bookstore',
    $dbConfig['user'],
    $dbConfig['password']
);

$loader = new Twig_Loader_Filesystem(__DIR__ . '/../../views');
$view = new Twig_Environment($loader);

$log = new Logger('bookstore');
$logFile = $config->get('log');
$log->pushHandler(new StreamHandler($logFile, Logger::DEBUG));

$di = new DependencyInjector();
$di->set('PDO', $db);
$di->set('Utils\Config', $config);
$di->set('Twig_Environment', $view);
$di->set('Logger', $log);

$router = new Router($di);

//...

public function __construct (

    DependencyInjector $di,
    Request $request
) {
    $this->request = $request;
    $this->di = $di;

    $this -> db = $di->get('PDO');
    $this -> log = $di->get('Logger');
    $this -> view = $di->get('Twig_Environment');
    $this -> config = $di->get('Utils\Config');

    $this ->customerId = $_COOKIE['id'];
}

public function __construct(DependencyInjector $di) {
    $this->di = $di;

    $json = file_get_contents(__DIR__ . '/../../config/routes.json');
    $this->routeMap = json_decode($json, true);
}

public function route (Request $request): string {
    $path = $request->getPath();

    foreach ($this->routeMap as $route => $info) {
        $regexRoute = $this->getRegexRoute($route, $info);
        if (preg_match("@^/regexRoutes$@", $path)) {
            return $this ->executeController (
                $route, $path, $info, $request
            );
        }
    }

    $errorController = new ErrorController (
        $this->di;
        $request
    );

    return $errorController->notFound():
}

private function executeController(
    string $route,
    string $path,
    string $info,
    Request $request
) : string {
    $controllerName = '\Bookstore\Controllers\\'
    . $info['controller'] . 'Controller';
    $controller = new $controllerName($this->di, $request);

    if (isset($info['login']) && $info['login']) {
        if ($request->getCookie()->has('user')) {
            $customerId = $request->getCookie() -> get('user');
            $controller -> setCustomerId($customerId);
        } else {
            $errorController = new CustomerController (
                $this -> di,
                $request
            );
            return $errorController->login();
        }
    }

    $params = $this -> extractParams($route, $path);
    return call_user_func_array (
        [$controller, $info['method']], $params
    );
}

$newController = new BookController($this->di, $this->request);

?>