<?php

//计划任务 本脚本每分钟执行一次
//  */1 * * * * /usr/local/php/bin/php /data/webroot/kangjun/app/command/task.php

$loader = require __DIR__ . '/../../vendor/autoload.php';

// create app
$app = new \Rain\Application();

// config
require __DIR__ . '/../../app/config/app.php';

$app->init();

//每分钟执行一次 (用于订单服务前1小时和半小时分别进行提醒）
AppBundle\Service\OrderService::oneHourOrHalfHourNoticeEmployee();

//每天9点执行一次
if (date('H:i') === '09:00') {
    //订单超时关闭
    AppBundle\Service\OrderService::orderOverTimeChangeStatus();

    //会员到期提醒
    AppBundle\Service\MemberService::memberDueToRemind();
}
