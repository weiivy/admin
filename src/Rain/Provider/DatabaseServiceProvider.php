<?php
namespace Rain\Provider;

use Pimple\Container;
use Pimple\ServiceProviderInterface;
use Rain\Application;
use Rain\Database\DBConnection;
use Rain\Database\DBHelp;


/**
 * 数据库
 * @author  Zou Yiliang
 */
class DatabaseServiceProvider implements ServiceProviderInterface
{
    /**
     * 在容器中注册服务
     *
     * @param Container $app
     */
    public function register(Container $app)
    {
        $app['db'] = function () {

            $config = Application::$app['db.config'];
            if (isset($config['slave'])) {
                $slave = $config['slave'];
                unset($config['slave']);
            } else {
                $slave = array();
            }
            $conn = new DBConnection($config, $slave);
            return new DBHelp($conn);
        };
    }
}