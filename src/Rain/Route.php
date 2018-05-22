<?php

namespace Rain;

/**
 * 路由帮助类
 * @author  Zou Yiliang
 * @since   1.0
 */
class Route
{
    public $method;//GET、POST
    public $name;
    public $path;
    public $defaults = [];
    public $where = [];
    public $before = [];

    const CALLBACK = '_controller';

    public static function get($path, $callback)
    {
        $route = new self();
        $route->addRoute($path, $callback, 'GET');
        return $route;
    }

    public static function post($path, $callback)
    {
        $route = new self();
        $route->addRoute($path, $callback, 'POST');
        return $route;
    }

    public static function any($path, $callback)
    {
        $route = new self();
        $route->addRoute($path, $callback);
        return $route;
    }

    public function where(array $where)
    {
        $this->where = array_merge($this->where, $where);
    }

    protected function addRoute($path, $params, $method = null)
    {
        if (static::$group !== null && isset(static::$group['prefix'])) {
            $path = static::$group['prefix'] . '/' . $path;
        }

        $this->path = $path;
        $this->name = $path;

        if (is_array($params)) {

            if (array_key_exists('as', $params)) {
                $this->name = $params['as'];
            }

            if (array_key_exists('before', $params)) {
                if (is_array($params['before'])) {
                    $this->before = array_merge($this->before, $params['before']);
                } else {
                    $this->before[] = (string)$params['before'];
                }
            }

            $this->defaults[self::CALLBACK] = $params[0];
        } else {
            //string or Closure
            //e.g. "UserController@getAllUsers"
            $this->defaults[self::CALLBACK] = $params;
        }

        //检查并提取默认参数
        $arr = [];
        if (preg_match_all('/\\{(\w+)\\?\\}/', $this->path, $arr)) {
            //去除问号
            $this->path = str_replace('?', '', $this->path);
            $defaultValues = $this->getDefaultValues($this->defaults[self::CALLBACK]);
            foreach ($arr[1] as $name) {
                $this->defaults[$name] = array_key_exists($name, $defaultValues) ? $defaultValues[$name] : null;
            }
        }

        //GET、POST
        if ($method !== null) {
            $this->where['_method'] = $method;
        }

        if (!array_key_exists(self::CALLBACK, $this->defaults)) {
            echo 'route error: ' . htmlspecialchars($path);
            exit;
        }

        //是否在group中
        if (static::$group !== null) {
            if (isset(static::$group['before'])) {
                if (is_array(static::$group['before'])) {
                    $this->before = array_merge($this->before, static::$group['before']);
                } else {
                    $this->before[] = (string)static::$group['before'];
                }
            }
        }

        Application::$app->addRoute($this);
    }

    private function getDefaultValues($action)
    {
        $values = array();
        if ($action instanceof \Closure) {
            //匿名函数
            $method = new \ReflectionFunction($action);
        } else if (is_string($action)) {
            //控制器类+方法
            //e.g. UserController@getAllUsers
            $arr = explode('@', $action);
            if (count($arr) !== 2) {
                return $values;
            }
            $controller = new $arr[0];
            $method = new \ReflectionMethod($controller, $arr[1]);
        }

        if ($method->getNumberOfParameters() > 0) {
            foreach ($method->getParameters() as $i => $param) {
                $name = $param->getName();
                if ($param->isDefaultValueAvailable()) {
                    $values[$name] = $param->getDefaultValue();
                }
            }
        }
        return $values;
    }

    public static function filter($name, $callback)
    {
        Application::$app->addFilter($name, $callback);
    }

    /**
     * 基于正则的过滤器(对所有匹配到Url有效)
     *
     * Route::whenRegex('/^user/', 'login');
     *
     * @param $pattern
     * @param $filter
     */
    public static function whenRegex($pattern, $filter)
    {
        Application::$app->addRegexFilter($pattern, $filter);
    }

    protected static $group;

    /**
     * 路由组
     * Route::group(array('before' => 'test', 'prefix'=>'admin'), function () {
     *    Route::get('test1', function () {
     *        // Matches The "/admin/test1" URL
     *        return 'test1';
     *    });
     *
     *    Route::get('test2', function () {
     *        return 'test2';
     *    });
     * });
     * @param $arr
     * @param $callback
     */
    public static function group($arr, $callback)
    {
        static::$group = $arr;

        $callback();

        static::$group = null;
    }


    /**
     * 隐式控制器 定义单一路由来处理控制器中的每一项行为 需要在方法名称前加上它们所对应的 HTTP 请求(get、post、any)
     *
     * 例如:
     *      Route::controller('user', 'app\controllers\UserController');
     * 表示:
     *      /user 对应 'app\controllers\UserController@getIndex'
     *      /user/create 对应 'app\controllers\UserController@postCreate'
     *
     * 如果你的控制器行为包含多个字词，你可以在 URI 中使用中杠"-"语法来访问此行为。
     *
     * 例如:
     *      user/admin-profile 对应 'app\controllers\UserController@getAdminProfile'
     *
     * @param string $baseUrl 控制器欲处理的 base URI
     * @param string|array $callback 控制器的类名
     * @param array $alias 设定路由名字 例如 array('getIndex' => 'users')
     */
    public static function controller($baseUrl, $callback, $alias = array())
    {

        if (is_array($callback)) {
            $controllerClass = $callback[0];
        } else {
            $controllerClass = $callback;
        }

        $pattern = '/^(get|post|any){1}([A-Z]{1}[\w]*)$/';
        $ref = new \ReflectionClass($controllerClass);
        $methods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
        foreach ($methods as $method) {
            $fullMethodName = $method->getName();
            if (preg_match($pattern, $fullMethodName, $result)) {
                $httpMethod = $result[1]; // HTTP 请求类型  get 、 post 等
                $methodName = strtolower(trim(preg_replace('/[A-Z]/', '-\\0', $result[2]), '-')); // 方法名去掉请求类型，如果中间有大写，则转为中杠"-"语法


                $action = $controllerClass . '@' . $fullMethodName;
                if (is_array($callback)) {
                    $callback[0] = $action;
                } else {
                    $callback = array($action);
                }

                if (array_key_exists($fullMethodName, $alias)) {
                    $callback['as'] = $alias[$fullMethodName];
                } else {
                    unset($callback['as']);
                }

                $isIndexMethod = strcmp($methodName, 'index') == 0;
                $path = $isIndexMethod ? $baseUrl : ($baseUrl . '/' . $methodName);

                static::$httpMethod($path, $callback);

                //允许 Url::to('user/index','UserController@getIndex');
                if ($isIndexMethod && !array_key_exists('as', $callback)) {
                    $callback['as'] = $baseUrl . '/' . $methodName;
                    static::$httpMethod($path, $callback);
                }

            }
        }
    }
}