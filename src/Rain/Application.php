<?php

namespace Rain;

require_once dirname(__FILE__) . DIRECTORY_SEPARATOR . 'functions.php';

use Exception;
use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Symfony\Component\HttpKernel\Event\GetResponseForControllerResultEvent;
use Symfony\Component\HttpKernel\EventListener\ExceptionListener;
use Symfony\Component\HttpKernel\EventListener\RouterListener;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\HttpKernel;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RequestContext;
use Symfony\Component\Routing\Matcher\UrlMatcher;
use Symfony\Component\Routing\Generator\UrlGenerator;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Application
 * @author  Zou Yiliang
 */
class Application extends Container
{
    /**
     * @var static
     */
    public static $app;

    public function __construct(array $config = array())
    {
        $config = array_replace_recursive([
            'id' => null,
            'path' => null,
            'timezone' => 'Asia/Chongqing',
            'debug' => false,
            'params' => [],
            'aliases' => [
                'Rain\DB' => 'Rain\Facade\DBFacade',
                'Rain\Auth' => 'Rain\Auth\AuthManager',
                'Rain\Request' => 'Symfony\Component\HttpFoundation\Request',
                'Rain\Mail' => 'Rain\Facade\MailFacade',
                'Rain\Cache' => 'Rain\Facade\CacheFacade',
                'Rain\Url' => 'Rain\Facade\UrlFacade',
            ],
        ], $config);

        parent::__construct($config);

        $this['dispatcher'] = new EventDispatcher();
        $this['callback_resolver'] = new CallbackResolver($this);
        static::$app = $this;

        //注册类自动加载器
        $this->autoloadRegister();
    }

    public function init()
    {
        $this['path'] = realpath($this['path']);
        if (empty($this['path'])) {
            throw new Exception('app path is empty.');
        }

        if (is_null($this['id'])) {
            $this['id'] = md5($this['path']);
        }

        date_default_timezone_set($this['timezone']);

        //开启错误报告
        ini_set('display_errors', 'On');
        error_reporting(E_ALL);

        //错误处理
        $errorHandler = new ErrorHandler();
        set_error_handler(array($errorHandler, 'handleError'));
        set_exception_handler(array($errorHandler, 'handleException'));

    }

    /**
     * 所有路由
     * @var \Rain\Route[]
     */
    protected $routeHelpers = [];

    public function addRoute(\Rain\Route $route)
    {
        $controller = $route->defaults[\Rain\Route::CALLBACK];
        if (is_string($controller)) {
            $route->defaults[\Rain\Route::CALLBACK] = str_replace('@', '::', $controller);
        }

        $this->routeHelpers[$route->name] = $route;
    }

    /**
     * 全部过滤器列表
     * @var array
     */
    protected $filters = [];

    public function addFilter($name, $filter)
    {
        $this->filters[$name] = $filter;
    }

    //基于模式的过滤器，对符合规则的所有路由生效
    //e.g.  Route::whenRegex('/^admin/', 'admin');
    protected $regexFilters = [];

    public function addRegexFilter($pattern, $filter)
    {
        $this->regexFilters[$pattern] = $filter;
    }

    /**
     * @var string 当前路由名
     */
    public $currentRouteName;

    /**
     * @var Request
     */
    protected $request;

    public function getRequest()
    {
        return $this->request;
    }

    /**
     * @var \Symfony\Component\Routing\Generator\UrlGenerator
     */
    public $urlGenerator;


    public function run(Request $request = null)
    {
        $this->init();

        //创建Request对象
        if ($request === null) {
            $request = Request::createFromGlobals();
        }
        $this->request = $request;

        $session = new \Symfony\Component\HttpFoundation\Session\Session();
        $request->setSession($session);

        //初始化RequestContext
        $context = new RequestContext();
        $context->fromRequest($this->request);

        //加载过滤器和路由
        require $this['path'] . DIRECTORY_SEPARATOR . 'filters.php';
        require $this['path'] . DIRECTORY_SEPARATOR . 'routes.php';

        $routes = new RouteCollection();
        foreach ($this->routeHelpers as $route) {
            $routes->add($route->name, new Route($route->path, $route->defaults, $route->where));
        }

        //Url生成器
        $this->urlGenerator = new UrlGenerator($routes, $context);

        //匹配URL中的请求路径, match返回相关的路由属性数组(路由的名字自动赋值给_route)
        $matcher = new UrlMatcher($routes, $context);

        //事件分配器
        $dispatcher = $this['dispatcher'];

        $dispatcher->addSubscriber(new RouterListener($matcher));

        //Controller不能返回一个Response对象时才被调用
        $dispatcher->addListener('kernel.view', function (GetResponseForControllerResultEvent $event) {
            $result = $event->getControllerResult();
            $response = new Response();
            $response->setContent((string)$result);
            $event->setResponse($response);
        });

        $dispatcher->addListener('kernel.request', function (GetResponseEvent $event) {
            $this->currentRouteName = $this->request->attributes->get('_route');

            //正则过虑器
            $path = ltrim($this->request->getPathInfo(), '/');
            $path = empty($path) ? '/' : $path;

            foreach ($this->regexFilters as $pattern => $filterName) {
                if (preg_match($pattern, $path)) {
                    //执行过滤器
                    if (array_key_exists($filterName, $this->filters)) {

                        //过滤器返回非null值，则不执行路由
                        $return = call_user_func($this->filters[$filterName]);
                        if ($return !== null) {
                            if ($return instanceof Response) {
                                $response = $return;
                            } else {
                                $response = new Response();
                                $response->setContent((string)$return);
                            }
                            $event->setResponse($response);
                            return;
                        }

                    } else {

                        //找不到过滤器
                        $response = new Response();
                        $response->setContent('Filter error: "' . htmlspecialchars($filterName)) . '".';
                        $event->setResponse($response);
                        return;
                    }
                }
            }

            //检查路由的过滤器
            $routeHelper = $this->routeHelpers[$this->currentRouteName];

            //循环路由的before过滤器
            foreach ($routeHelper->before as $filterName) {

                //执行过滤器
                if (array_key_exists($filterName, $this->filters)) {

                    //过滤器返回非null值，则不执行路由
                    $return = call_user_func($this->filters[$filterName]);
                    if ($return !== null) {
                        if ($return instanceof Response) {
                            $response = $return;
                        } else {
                            $response = new Response();
                            $response->setContent((string)$return);
                        }
                        $event->setResponse($response);
                        return;
                    }

                } else {

                    //找不到过滤器
                    $response = new Response();
                    $response->setContent('Filter error: "' . htmlspecialchars($filterName)) . '".';
                    $event->setResponse($response);
                    return;

                }
            }

        });


        $resolver = new ControllerResolver();
        $kernel = new HttpKernel($dispatcher, $resolver);

        $this['kernel'] = $kernel;

        $response = $kernel->handle($request);
        $response->send();
        $kernel->terminate($request, $response);

    }

    /**
     * 中止当前请求发送一个适当的HTTP错误
     *
     * @param int $statusCode The HTTP status code
     * @param string $message The status message
     * @param array $headers An array of HTTP headers
     */
    public static function abort($statusCode, $message = '', array $headers = array())
    {
        if ($statusCode == 404) {
            throw new NotFoundHttpException($message);
        }
        throw new HttpException($statusCode, $message, null, $headers);
    }

    /**
     * 注册 error handler.
     *
     * Error handlers are simple callables which take a single Exception
     * as an argument. If a controller throws an exception, an error handler
     * can return a specific response.
     *
     * When an exception occurs, all handlers will be called, until one returns
     * something (a string or a Response object), at which point that will be
     * returned to the client.
     *
     * For this reason you should add logging handlers before output handlers.
     *
     * @param mixed $callback Error handler callback, takes an Exception argument
     * @param int $priority The higher this value, the earlier an event
     *                        listener will be triggered in the chain (defaults to -8)
     */
    public function error($callback, $priority = -8)
    {
        $this->on(KernelEvents::EXCEPTION, new ExceptionListenerWrapper($this, $callback), $priority);
    }


    /**
     * 添加事件监听
     *
     * @param string $eventName 事件名
     * @param callable $callback The listener
     * @param int $priority 值越大，优先给越高
     */
    public function on($eventName, $callback, $priority = 0)
    {
        $this['dispatcher']->addListener($eventName, $this['callback_resolver']->resolveCallback($callback), $priority);
    }

    protected $providers = array();


    public static function getVersion()
    {
        return '0.1';
    }

    protected $bundles = array();

    public function registerBundle($bundle)
    {
        $name = $bundle->getName();
        if (isset($this->bundles[$name])) {
            throw new \LogicException(sprintf('Trying to register two bundles with the same name "%s".', $name));
        }
        $this->bundles[$name] = $bundle;
    }

    /**
     * @param $name
     * @return Bundle
     */
    public function getBundle($name)
    {
        if (!isset($this->bundles[$name])) {
            throw new \InvalidArgumentException(sprintf('Bundle "%s" does not exist or it is not enabled.', $name));
        }

        return $this->bundles[$name];
    }

    /**
     * 发布资源到webRoot目录
     * @param string $src
     * @param bool $force 为true时，表示强制更新，否则版本号不同，才更新
     * @param string $version 版本号
     * @return string 返回访问的url
     * @throws \Exception
     */
    public function publishAsset($src, $force = false, $version = '')
    {
        //源文件或目录
        $path = realpath($src);

        //源目录不存在时，调试模式下抛出异常，否则返回空字符串
        if ($path === false) {
            if ($this['debug']) {
                throw new \Exception('directory does not exist : ' . $src);
            }
            return '';
        }
        $hash = sprintf('%x', crc32($path));

        //目标目录
        $dst = './assets/' . $hash;

        //发布目录
        if (is_dir($path)) {

            //每次都强制发布
            if ($force) {
                Util::copyDirectory($path, $dst);
                return Url::asset('assets/' . $hash);
            }

            //判断版本号
            $versionFile = $dst . DIRECTORY_SEPARATOR . '.version';

            if (file_exists($versionFile) && @file_get_contents($versionFile) == $version) {
                return Url::asset('assets/' . $hash);
            }

            Util::copyDirectory($path, $dst);
            file_put_contents($versionFile, $version, LOCK_EX);
            return Url::asset('assets/' . $hash);

        } else { //发布单文件

            $hash = sprintf('%x', crc32($path));
            $dst = './assets/' . $hash . '/' . basename($path);

            if (!file_exists($dst) || filemtime($path) > filemtime($dst)) {
                if (!file_exists(dirname($dst))) {
                    Util::createDirectory(dirname($dst));
                }
                copy($path, $dst);
            }

            return Url::asset('assets/' . $hash . '/' . basename($path));
        }
    }

    /**
     * 注册类加载器
     */
    public function autoloadRegister()
    {
        spl_autoload_register(function ($className) {
            //加载别名
            if (array_key_exists($className, $this['aliases'])) {
                class_alias($this['aliases'][$className], $className);
            }

        });
    }
}