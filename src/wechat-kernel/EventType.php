<?php
namespace Rain\Wechat;

class EventType
{
    //消息类型 对应MsgType字段
    const MSG_TYPE_TEXT = 'MsgType.text';                 //文本消息
    const MSG_TYPE_VOICE = 'MsgType.voice';               //语音
    const MSG_TYPE_IMAGE = 'MsgType.image';               //图片
    const MSG_TYPE_LOCATION = 'MsgType.location';         //位置消息(聊天窗口发送的位置)
    const MSG_TYPE_LINK = 'MsgType.link';                 //链接
    const MSG_TYPE_VIDEO = 'MsgType.video';               //视频 (微信"拍摄"功能所得到的视频，不是"小视频")
    const MSG_TYPE_SHORTVIDEO = 'MsgType.shortvideo';     //小视频 (测试未成功)
    const MSG_TYPE_EVENT = 'MsgType.event';               //事件

    //事件类型 当MsgType为event时,细分具体事件类型，对应Event字段
    const EVENT_SUBSCRIBE = 'Event.subscribe';                   //关注事件
    const EVENT_UNSUBSCRIBE = 'Event.unsubscribe';               //关注事件
    const EVENT_CLICK = 'Event.CLICK';                           //点击菜单
    const EVENT_VIEW = 'Event.VIEW';                             //菜单跳转
    const EVENT_SCAN = 'Event.SCAN';                             //扫描二唯码
    const EVENT_LOCATION = 'Event.LOCATION';                     //上报位置事件

    const EVENT_MASSSENDJOBFINISH = 'Event.MASSSENDJOBFINISH';   //群发消息结果

    const EVENT_CARD_PASS_CHECK = 'Event.card_pass_check';           //卡券通过审核
    const EVENT_CARD_NOT_PASS_CHECK = 'Event.card_not_pass_check';   //卡券审核不通过
    const EVENT_USER_GET_CARD = 'Event.user_get_card';               //用户在领取卡券
    const EVENT_USER_DEL_CARD = 'Event.user_del_card';               //用户在删除卡券 核销事件推送与这个相同?
    const EVENT_USER_VIEW_CARD = 'Event.user_view_card';             //用户在进入会员卡

}