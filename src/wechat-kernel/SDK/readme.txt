注意事项：
1.WXBizMsgCrypt.php文件提供了WXBizMsgCrypt类的实现，是用户接入企业微信的接口类。Sample.php提供了示例以供开发者参考。errorCode.php, pkcs7Encoder.php, sha1.php, xmlparse.php文件是实现这个类的辅助类，开发者无须关心其具体实现。
2.WXBizMsgCrypt类封装了 DecryptMsg, EncryptMsg两个接口，分别用于开发者解密以及开发者回复消息的加密。使用方法可以参考Sample.php文件。
3.加解密协议请参考微信公众平台官方文档。



<?php

include_once "WXBizMsgCrypt.php";

// 第三方发送消息给公众平台
$encodingAesKey = "abcdefghijklmnopqrstuvwxyz0123456789ABCDEFG";
$token = "pamtest";
$timeStamp = "1409304348";
$nonce = "xxxxxx";
$appId = "wxb11529c136998cb6";
$text = "<xml><ToUserName><![CDATA[oia2Tj我是中文jewbmiOUlr6X-1crbLOvLw]]></ToUserName><FromUserName><![CDATA[gh_7f083739789a]]></FromUserName><CreateTime>1407743423</CreateTime><MsgType><![CDATA[video]]></MsgType><Video><MediaId><![CDATA[eYJ1MbwPRJtOvIEabaxHs7TX2D-HV71s79GUxqdUkjm6Gs2Ed1KF3ulAOA9H1xG0]]></MediaId><Title><![CDATA[testCallBackReplyVideo]]></Title><Description><![CDATA[testCallBackReplyVideo]]></Description></Video></xml>";


$pc = new WXBizMsgCrypt($token, $encodingAesKey, $appId);
$encryptMsg = '';
$errCode = $pc->encryptMsg($text, $timeStamp, $nonce, $encryptMsg);
if ($errCode == 0) {
	print("加密后: " . $encryptMsg . "\n");
} else {
	print($errCode . "\n");
}

$xml_tree = new DOMDocument();
$xml_tree->loadXML($encryptMsg);
$array_e = $xml_tree->getElementsByTagName('Encrypt');
$array_s = $xml_tree->getElementsByTagName('MsgSignature');
$encrypt = $array_e->item(0)->nodeValue;
$msg_sign = $array_s->item(0)->nodeValue;

$format = "<xml><ToUserName><![CDATA[toUser]]></ToUserName><Encrypt><![CDATA[%s]]></Encrypt></xml>";
$from_xml = sprintf($format, $encrypt);

// 第三方收到公众号平台发送的消息
$msg = '';
$errCode = $pc->decryptMsg($msg_sign, $timeStamp, $nonce, $from_xml, $msg);
if ($errCode == 0) {
	print("解密后: " . $msg . "\n");
} else {
	print($errCode . "\n");
}
