<?php

namespace Rain\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Rain\Application;
use Rain\Auth;

class TwigServiceProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['twig.options'] = function ($app) {
            return array(
                'charset' => isset($app['charset']) ? $app['charset'] : 'UTF-8',
                'debug' => isset($app['debug']) ? $app['debug'] : false,
                'strict_variables' => isset($app['debug']) ? $app['debug'] : false,
                'cache' => $app['path'] . '/runtime/cache/twig',
                'auto_reload' => true,
            );
        };

        $app['twig.templates'] = function ($app) {
            return array();
        };

        $app['twig.path'] = function ($app) {
            return $app['path'] . '/views';
        };

        $app['twig.loader.filesystem'] = function ($app) {
            return new \Twig_Loader_Filesystem($app['twig.path']);
        };

        $app['twig.loader.array'] = function ($app) {
            return new \Twig_Loader_Array($app['twig.templates']);
        };

        $app['twig.loader'] = function ($app) {
            return new \Twig_Loader_Chain(array(
                $app['twig.loader.array'],
                $app['twig.loader.filesystem'],
            ));
        };

        $app['twig.environment_factory'] = $app->protect(function ($app) {
            return new \Twig_Environment($app['twig.loader'], $app['twig.options']);
        });

        $app['twig.app'] = function (Application $app) {
            $obj = new Container();
            $obj['user'] = function () {
                return Auth::getIdentity();
            };
            $obj['request'] = function () use ($app) {
                return $app->getRequest();
            };
            $obj['session'] = function () {
                return new \Rain\Session();
            };
            $obj['html'] = function () {
                return new \Rain\Html();
            };
            $obj['debug'] = $app['twig.options']['debug'];
            return $obj;
        };

        $app['twig'] = function ($app) {

            $twig = $app['twig.environment_factory']($app);

            $twig->addFunction(new \Twig_SimpleFunction('url', function ($name, $params = [], $absoluteUrl = false) use ($app) {
                return \Rain\Url::to($name, $params, $absoluteUrl);
            }));

            $twig->addFunction(new \Twig_SimpleFunction('asset', function ($asset = '', $absoluteUrl = false) use ($app) {
                return \Rain\Url::asset($asset, $absoluteUrl);
            }));

            $twig->addFunction(new \Twig_SimpleFunction('csrf_token', function () use ($app) {
                return \Rain\Session::regenerateToken();
            }));

            //<script src="{{ publish('@AdminBundle/js/test.js') }}"></script>  发布单文件
            //{% set public = publish('@AdminBundle', app.debug, '20150619.1') %} 发布public目录
            //<script src="{{ public ~ '/js/test.js' }}"></script>
            $twig->addFunction(new \Twig_SimpleFunction('publish', function ($fileName, $force = false, $version = '') use ($app) {

                if (stripos($fileName, '@') === 0) {
                    if (stripos($fileName, '/') === false) {
                        $bundleName = substr($fileName, 1);
                    } else {
                        $bundleName = substr($fileName, 1, stripos($fileName, '/') - 1);
                    }
                    $bundle = Application::$app->getBundle($bundleName);
                    $file = $bundle->getPath() . '/resources/public/' . substr($fileName, strlen($bundleName) + 2);

                    return Application::$app->publishAsset($file, $force, $version);
                }
                return $fileName;
            }));


            if (isset($app['debug']) && $app['debug']) {
                $twig->addExtension(new \Twig_Extension_Debug());
            }

            $twig->addGlobal('app', $app['twig.app']);

            return $twig;
        };

    }
}

/*class Javascripts extends \Twig_TokenParser
{
    public function parse(\Twig_Token $token)
    {
        $parser = $this->parser;
        $stream = $parser->getStream();

        $name = $stream->expect(Twig_Token::NAME_TYPE)->getValue();
        $stream->expect(Twig_Token::OPERATOR_TYPE, '=');
        $value = $parser->getExpressionParser()->parseExpression();
        $stream->expect(Twig_Token::BLOCK_END_TYPE);

        return 'sss';
    }

    public function getTag()
    {
        return 'javasciprts';
    }
}
*/