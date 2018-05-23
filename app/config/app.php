<?php

$loader->addPsr4('app\\', __DIR__ . '/../../app');
$loader->addPsr4('Rain\\Bundle\\AdminBundle\\', __DIR__ . '/../../src/admin-bundle');
$loader->addPsr4('Rain\Wechat\\', __DIR__ . '/../../src/wechat-kernel');

$app['path'] = dirname(__DIR__);

$app['db.config'] = [
    'dsn' => 'mysql:host=localhost;dbname=jifen',
    'username' => 'root',
    'password' => 'root',
    'charset' => 'utf8',
    'tablePrefix' => 'pre_',
    'slave' => [],
];

//$app['cache.path'] = __DIR__ . '/../app/runtime/cache/';
//$app['runtime.path'] = __DIR__ . '/../app/runtime/';


$app->error(function (\Exception $e, \Symfony\Component\HttpFoundation\Request $request, $code) use ($app) {
    if ($app['debug']) {
        throw $e;
    }
    // 404.twig, or 40x.twig, or 4xx.twig, or default.twig
    $templates = array(
        'errors/' . $code . '.twig',
        'errors/' . substr($code, 0, 2) . 'x.twig',
        'errors/' . substr($code, 0, 1) . 'xx.twig',
        'errors/default.twig',
    );

    return new \Symfony\Component\HttpFoundation\Response($app['twig']->resolveTemplate($templates)->render(array(
        'code' => $code,
        'message' => $e->getMessage()
    )), $code);
});

if (file_exists(__DIR__ . '/app-local.php')) {
    require __DIR__ . '/app-local.php';
}


// service provider
$app->register(new \Rain\Provider\DatabaseServiceProvider());
$app->register(new \Rain\Provider\LogServiceProvider());
$app->register(new \Rain\Provider\TwigServiceProvider());

// register bundle
$loader->addPsr4('Rain\\Bundle\\AdminBundle\\', __DIR__ . '/../../src/admin-bundle');
$app->registerBundle(new Rain\Bundle\AdminBundle\AdminBundle());

//$loader->addPsr4('AppBundle\\', __DIR__ . '/../../AppBundle');
$app->registerBundle(new AppBundle\AppBundle());
