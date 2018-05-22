<?php

namespace Rain\Facade;

use Rain\Application;
use Rain\Cache\CacheManager;

class CacheFacade extends CacheManager
{
    protected static function getConfig()
    {
        if (isset(Application::$app['cache.path'])) {
            $path = Application::$app['cache.path'];
        } else {
            $path = Application::$app['path'] . '/runtime/cache';
        }

        return array(
            'class' => 'Rain\Cache\FileCache',
            'cachePath' => $path,
            'keyPrefix' => '',
        );
    }

}