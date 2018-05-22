<?php

namespace Rain\Wechat;

use Symfony\Component\EventDispatcher\Event;

/**
 * 公众号事件
 */
class RequestEvent extends Event
{
    protected $msg;
    protected $api;

    public function __construct($msg, $api)
    {
        $this->msg = $msg;
        $this->api = $api;
    }

    /**
     * @return object
     */
    public function getMsg()
    {
        return $this->msg;
    }

    /**
     * @return Api
     */
    public function getApi()
    {
        return $this->api;
    }

}
