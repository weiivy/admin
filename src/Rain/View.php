<?php

namespace Rain;

/**
 * 视图操作帮助类
 * @author  Zou Yiliang
 */
class View
{
    /**
     *
     * @param string $view 模板文件
     * @param array $data
     * @return string
     */
    public static function render($view, $data = array())
    {
        //View::render('@AcmeDemoBundle/welcome/index.twig');
        if (stripos($view, '@') === 0) {
            $bundleName = substr($view, 1, stripos($view, '/') - 1);
            $bundle = Application::$app->getBundle($bundleName);

            //app/views/AcmeDemoBundle/welcome/index.twig
            if (file_exists(Application::$app['twig.path'] . '/' . $bundleName)) {
                Application::$app['twig.loader.filesystem']->addPath(Application::$app['twig.path'] . '/' . $bundleName, $bundleName);
            }
            //src/Acme/AcmeDemoBundle/views/welcome/index.twig
            Application::$app['twig.loader.filesystem']->addPath($bundle->getPath() . '/resources/views/', $bundleName);
        }

        return Application::$app['twig']->render($view, $data);
    }

    /**
     * 把数据共享给所有模板文件
     * @param $name
     * @param $value
     */
    public static function share($name, $value)
    {
        Application::$app['twig'] = Application::$app->extend('twig', function ($twig, $app) use ($name, $value) {
            $twig->addGlobal($name, $value);
            return $twig;
        });
    }

    /**
     * echo View::renderText('hello {{ user }}',['user'=>'jack']);
     *
     * @param string $template 模板字符串
     * @param array $data
     * @return string
     */
    public static function renderText($template, $data = array())
    {
        $loader = new \Twig_Loader_String();
        $twig = new \Twig_Environment($loader);
        return $twig->render($template, $data);
    }

}