<?php
namespace Rain\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Rain\Log;

/**
 * 日志
 * @author  Zou Yiliang
 */
class LogServiceProvider implements ServiceProviderInterface
{

    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['log'] = function () {
            return new Log();
        };
    }
}