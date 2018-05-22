<?php

namespace Rain\Facade;

use Rain\Application;

class UrlFacade
{
    /**
     * 生成到路由的url
     * @param $name
     * @param array $params
     * @param bool $absoluteUrl 是否生成绝对url(http开头)
     * @return string
     */
    public static function to($name, $params = [], $absoluteUrl = false)
    {
        return Application::$app->urlGenerator->generate($name, $params, $absoluteUrl);
    }

    /**
     * $url = Url::action('app\controllers\FooController@method');
     *
     * @param string $action
     * @param array $params
     * @param bool $absoluteUrl
     * @return string
     */
    /*public static function action($action, $params = [], $absoluteUrl = false)
    {
    }*/

    /**
     * 生成入口文件所在目录为基础目录的url，默认最后没有斜线"/"
     * 例如 Url::asset('images/logo.png')
     * @param string $asset
     * @param bool $absoluteUrl 是否生成绝对url(http开头)
     * @return string
     */
    public static function asset($asset = '', $absoluteUrl = false)
    {
        if ($asset !== '') {
            $asset = '/' . $asset;
        }

        $host = '';
        if ($absoluteUrl) {
            $host = Application::$app->getRequest()->getSchemeAndHttpHost();
        }

        return $host . Application::$app->getRequest()->getBasePath() . $asset;
    }

}