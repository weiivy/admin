
/* 微信支付配置 */
DROP TABLE IF EXISTS pay_wxpay;
CREATE TABLE IF NOT EXISTS pay_wxpay(
  id INT AUTO_INCREMENT PRIMARY KEY,
  mch_id VARCHAR(50) NOT NULL DEFAULT '' COMMENT '微信支付商户号', /*申请支付成功的邮件中*/
  appid VARCHAR(100) NOT NULL DEFAULT '' COMMENT '公众号APPID',     /*申请支付成功的邮件中*/
  `key` VARCHAR(100) NOT NULL DEFAULT '' COMMENT 'api key',/*帐户设置-安全设置-API安全-API密钥-设置API密钥*/
	status INT NOT NULL DEFAULT 2 COMMENT '状态', /*1.有效Valid  2.无效Invalid*/
  created_at INT NOT NULL DEFAULT 0 COMMENT '录入时间',
  updated_at INT NOT NULL DEFAULT 0 COMMENT '更新时间'
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/* 微信支付结果 */
DROP TABLE IF EXISTS pay_wxpay_result;
CREATE TABLE IF NOT EXISTS pay_wxpay_result(
  id INT AUTO_INCREMENT PRIMARY KEY,
  wxpay_id INT DEFAULT 0 NOT NULL COMMENT 'wxpay id',
  mch_id VARCHAR(100) DEFAULT '' COMMENT '微信支付商户号',
  appid VARCHAR(100) DEFAULT '' COMMENT '公众账号ID',
  out_trade_no VARCHAR(100) DEFAULT '' COMMENT '订单号',
  openid VARCHAR(255) NOT NULL DEFAULT '' COMMENT '用户唯一标识',
  transaction_id VARCHAR(255) NOT NULL DEFAULT '' COMMENT '微信支付交易号', /* 支付成功后返回 */
  total_fee DEC(10,2) NOT NULL DEFAULT '0' COMMENT '付款金额',
  time_end VARCHAR(50) NOT NULL DEFAULT 0 COMMENT '支付时间', /* 平台返回的支付时间 */
  created_at INT NOT NULL DEFAULT 0 COMMENT '录入时间',
  updated_at INT NOT NULL DEFAULT 0 COMMENT '更新时间'
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;
/*
alter table pay_wxpay_result add   mch_id VARCHAR(100) DEFAULT '' COMMENT '微信支付商户号' after wxpay_id;
alter table pay_wxpay_result add     appid VARCHAR(100) DEFAULT '' COMMENT '公众账号ID'  after mch_id;
*/