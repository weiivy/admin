
CREATE DATABASE IF NOT EXISTS kangjun_dev;
USE kangjun_dev;

/* 用户表 */
DROP TABLE IF EXISTS pre_user;
CREATE TABLE IF NOT EXISTS `pre_user`(
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL DEFAULT '',
  `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
  `password_reset_token` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 0, /*10允许登录 20禁止登录 30删除*/
  `login_at` INT NOT NULL DEFAULT 0 COMMENT '登录时间',
  `created_at` INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  `updated_at` INT NOT NULL DEFAULT 0 COMMENT '修改时间',
  KEY ind_username(username)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

INSERT INTO pre_user (username,password_hash,status) VALUES ('root', md5('root'), 10);

/*服务项目*/
DROP TABLE IF EXISTS pre_product;
CREATE TABLE IF NOT EXISTS pre_product(
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '名称',
  `image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图片',
  `time` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '服务时长',
  `content` text COMMENT '内容',
  `price` DEC(10,2) NOT NULL DEFAULT '0' COMMENT '单价',
  `min` INT NOT NULL DEFAULT '0' COMMENT '起订人数',
  `sort` INT NOT NULL DEFAULT '0' COMMENT '排序',
  `discount` INT NOT NULL DEFAULT '0' COMMENT '特价商品标记: 0 正常 1 特价',
  `count` INT NOT NULL DEFAULT '0' COMMENT '交易数',
  `status` INT NOT NULL DEFAULT '0' COMMENT '10.上架 20.下架 30.删除',
  `position` INT  NOT NULL DEFAULT '0' COMMENT '体位(10.坐、20.卧)',
  `created_at` INT NOT NULL  DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*
alter table pre_product add `discount` INT NOT NULL DEFAULT '0' COMMENT '特价商品标记: 0 正常 1 特价' after sort;
*/

/*服务星级价格表*/
DROP TABLE IF EXISTS pre_product_grade_price;
CREATE TABLE IF NOT EXISTS pre_product_grade_price(
  `product_id` INT NOT NULL DEFAULT  '0' COMMENT '服务项目ID',
  `price` DEC(10,2) NOT NULL DEFAULT '0' COMMENT '单价',
  `grade` INT NOT NULL DEFAULT '0' COMMENT '技师等级', /* 例如 中级、高级*/
  `created_at` INT NOT NULL  DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*服务标签表*/
DROP TABLE IF EXISTS pre_product_tag;
CREATE TABLE IF NOT EXISTS pre_product_tag(
  `product_id` INT NOT NULL DEFAULT  '0' COMMENT '服务项目ID',
  `tag` INT NOT NULL DEFAULT '0' COMMENT '标签'
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*城市*/
DROP TABLE IF EXISTS pre_city;
CREATE TABLE IF NOT EXISTS pre_city(
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '名称',
  `created_at` INT NOT NULL  DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

INSERT INTO pre_city(id,name) VALUES (1,'上海');
/*INSERT INTO pre_city(id,name) VALUES (2,'北京');*/


/*分组与服务项目关联表*/
DROP TABLE IF EXISTS pre_group_product;
CREATE TABLE IF NOT EXISTS pre_group_product(
  `group_id` INT NOT NULL DEFAULT  '0' COMMENT '分组ID',
  `product_id` INT NOT NULL DEFAULT  '0' COMMENT '服务项目ID',
  KEY ind_group_product (group_id, product_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/*分组表*/
DROP TABLE IF EXISTS pre_group;
CREATE TABLE IF NOT EXISTS pre_group(
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '组名',
  `sort` INT NOT NULL DEFAULT '10' COMMENT '排序',
  `created_at` INT NOT NULL  DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*技师*/
DROP TABLE IF EXISTS pre_employee;
CREATE TABLE IF NOT EXISTS pre_employee(
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
 /* `group_id` INT NOT NULL DEFAULT  '0' COMMENT '分组id',*/
  `username` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '登陆名',
  `password` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '密码',
  `name` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '名称',
  `openid` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '用户唯一标识',
  `avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
  `sex` INT NOT NULL DEFAULT '0' COMMENT '1.男 2.女',
  `grade` INT NOT NULL DEFAULT '0' COMMENT '等级', /* 例如 中级、高级*/
  `intro` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '介绍',
  `area` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '服务区域',
  `count` INT NOT NULL DEFAULT '0' COMMENT '接单数',
  `city_id` INT NOT NULL DEFAULT '0' COMMENT '城市',
  `location` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '位置', /* 例如 中山公园 */
  `location_x` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '地理位置经度',
  `location_y` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '地理位置纬度',
  `sort` INT NOT NULL DEFAULT '0' COMMENT '排序',
  `status` INT NOT NULL DEFAULT '0' COMMENT '10.上岗 20.下岗 30.删除',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/*分组与技师关联表*/
DROP TABLE IF EXISTS pre_group_employee;
CREATE TABLE IF NOT EXISTS pre_group_employee(
  `group_id` INT NOT NULL DEFAULT  '0' COMMENT '分组ID',
  `employee_id` INT NOT NULL DEFAULT  '0' COMMENT '技师ID',
  KEY ind_group_Employee (group_id, employee_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*技师与服务项目关联表*/
/*DROP TABLE IF EXISTS pre_employee_product;
CREATE TABLE IF NOT EXISTS pre_employee_product(
  `employee_id` INT NOT NULL DEFAULT  '0' COMMENT '员工ID',
  `product_id` INT NOT NULL DEFAULT  '0' COMMENT '服务项目ID',
  KEY ind_employee_product (employee_id, product_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;*/

/*认证资料*/
DROP TABLE IF EXISTS pre_certificate;
CREATE TABLE IF NOT EXISTS pre_certificate(
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT NOT NULL DEFAULT  '0' COMMENT '员工ID',
  `image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '路径',
  `status` INT NOT NULL DEFAULT '0' COMMENT '(1待审核    2已审核   3审核未通过)',
  `created_at` INT NOT NULL DEFAULT 0,
  `updated_at` INT NOT NULL DEFAULT 0
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/*会员表*/
DROP TABLE IF EXISTS pre_member;
CREATE TABLE IF NOT EXISTS pre_member(
	`id` INT AUTO_INCREMENT PRIMARY KEY ,
	`username` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '登录名称',
	`password_hash` VARCHAR(255) NOT NULL DEFAULT '',
	`openid` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '用户唯一标识',
	`nickname` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '昵称',
	`avatar` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '头像',
	`email` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '邮箱',
	`mobile` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '手机',
	`auth_key` VARCHAR(13) NOT NULL DEFAULT '' COMMENT 'Auth Key',
	`password_reset_token` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '重置密码Token', /* md5(unique()) . time() */
	`mobile_check_token` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '手机验证Token',   /* md5(随机数) . time() */
	`status` TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '状态',		/* 1.启用  2.冻结 */
	`grade` INT NOT NULL DEFAULT '0' COMMENT '等级', /* 例如 0. 普通会员 1. VIP */

	`point` INT NOT NULL DEFAULT 0 COMMENT '积分',
	`money` DEC(10,2) NOT NULL DEFAULT 0 COMMENT '金额',
	`total_fee` DEC(10,2) NOT NULL DEFAULT 0 COMMENT '累计消费金额',
	`count` INT NOT NULL DEFAULT '0' COMMENT '累计消费单数',
	`undone` INT NOT NULL DEFAULT '0' COMMENT '未完成定单',

	`end_timestamp` INT DEFAULT 0 NOT NULL COMMENT '到期时间',/*会员VIP等级到期时间 对应grade字段非0的情况*/
	`created_at` INT NOT NULL DEFAULT 0 COMMENT '新增时间',
	`updated_at` INT NOT NULL DEFAULT 0 COMMENT '修改时间',
	KEY ind_username (username),
	KEY ind_mobile (mobile)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/* 订单 */
DROP TABLE IF EXISTS pre_order;
CREATE TABLE IF NOT EXISTS pre_order(
  id INT AUTO_INCREMENT PRIMARY KEY,

  out_trade_no VARCHAR(32) DEFAULT '' COMMENT '订单号',/*对外显示使用的订单号 唯一 */
  name VARCHAR(100) NOT NULL DEFAULT '' COMMENT '订单名称',
  member_id INT NOT NULL DEFAULT 0 COMMENT '下单人',
  employee_id INT NOT NULL DEFAULT 0 COMMENT '技师',

  pay_type TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式',/* 10.在线支付 20.货到付款 30.自提*/
  service_type TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务方式',/* 10.到家服务 20.到店服务 */
  employee_source TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '技师来源',/* 10.点钟 20.分配 */

  total_fee DEC(10,2) NOT NULL DEFAULT 0 COMMENT '订单总额',
  extra_fee DEC(10,2) NOT NULL DEFAULT 0 COMMENT '额外费用',

  status TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '订单状态',		/*订单状态: 新订单 进行中 交易成功 无效订单 申请退货 退货中 退货完成 */
  payment_status TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态',/*支付状态 未付款 待确认(有的付款方式需要人工确认) 已付款 已退款*/
  employee_status TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '技师状态',/*技师状态 出发 到达 完成*/

  /*收货人信息*/
  receiver_city SMALLINT NOT NULL DEFAULT 0 COMMENT '市',
  receiver_district SMALLINT NOT NULL DEFAULT 0 COMMENT '区',
  receiver_all_stage VARCHAR(255) NOT NULL DEFAULT '' COMMENT '完整省市区',
  receiver_detail VARCHAR(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  receiver_zip VARCHAR(10) NOT NULL DEFAULT '' COMMENT '邮编',
  receiver_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '姓名',
  receiver_phone VARCHAR(50) NOT NULL DEFAULT '' COMMENT '电话',
  receiver_mobile VARCHAR(50) NOT NULL DEFAULT '' COMMENT '手机',
  receiver_email VARCHAR(100) NOT NULL DEFAULT '' COMMENT '邮箱',

  /*技师位置、完成时间*/
  `longitude` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '经度',
	`latitude` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '纬度',
	`completed_time` INT NOT NULL DEFAULT 0 COMMENT '完成时间',

  message TEXT COMMENT '客户留言', /*买家下单时订单备注 */
  booking_time INT NOT NULL DEFAULT 0 COMMENT '预约时间',
  created_at INT NOT NULL DEFAULT 0 COMMENT '创建时间',
  updated_at INT NOT NULL DEFAULT 0 COMMENT '更新时间',
  KEY ind_out_trade_no(out_trade_no),
  KEY ind_member_id(created_at, member_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 AUTO_INCREMENT=1001;

/*订单商品明细*/
DROP TABLE IF EXISTS pre_order_item;
CREATE TABLE IF NOT EXISTS pre_order_item(
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_id INT NOT NULL DEFAULT 0 COMMENT '订单ID',
  product_id INT NOT NULL DEFAULT 0 COMMENT '商品ID',
  product_full_name VARCHAR(100) NOT NULL COMMENT '商品完整名称',
  quantity INT NOT NULL DEFAULT 0 COMMENT '数量',
  price DEC(10,2) NOT NULL DEFAULT 0 COMMENT '单价',
  created_at INT NOT NULL DEFAULT 0 COMMENT '创建时间',
  updated_at INT NOT NULL DEFAULT 0 COMMENT '更新时间',
  KEY ind_order_id(order_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*评价*/
DROP TABLE IF EXISTS pre_comment;
CREATE TABLE IF NOT EXISTS pre_comment(
	id INT AUTO_INCREMENT PRIMARY KEY ,
	
	product_id INT NOT NULL DEFAULT 0 COMMENT '项目',
	employee_id INT NOT NULL DEFAULT 0 COMMENT '员工',
	order_id INT NOT NULL DEFAULT 0 COMMENT '订单',
	rate INT NOT NULL DEFAULT 0 COMMENT '评级(10.好 20.中 30.差)',

	content VARCHAR(255) NOT NULL DEFAULT '' COMMENT '内容',
	review INT NOT NULL DEFAULT 0 COMMENT '审核', /* 10.未审核  20.审核通过 30.审核不通过*/

  created_at INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  updated_at INT NOT NULL DEFAULT 0 COMMENT '修改时间'
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/*卡券*/
DROP TABLE IF EXISTS pre_ticket;
CREATE TABLE IF NOT EXISTS pre_ticket(
	id INT AUTO_INCREMENT PRIMARY KEY ,
  type INT NOT NULL DEFAULT 0 COMMENT '类型',/*10代金券CASH  (扩展用: 例如20.礼品券GIFT 30.红包LUCKY_MONEY)*/
	title VARCHAR(255) NOT NULL DEFAULT '' COMMENT '名称',
  logo_url VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图片',
  reduce_cost DEC(10,2) NOT NULL DEFAULT '0' COMMENT '减免金额',/*代金券专用*/
  least_cost DEC(10,2) NOT NULL DEFAULT '0' COMMENT '抵扣条件',/* 消费最小金额 代金券专用*/
  quantity INT NOT NULL DEFAULT 0 COMMENT '剩余数量',
  distribute INT NOT NULL DEFAULT 0 COMMENT '派发数量',/*派发数量不为0时，只允许修改剩余数量*/
  notice VARCHAR(255) NOT NULL DEFAULT '' COMMENT '使用提醒',
  description TEXT COMMENT '使用说明',

  valid_type INT DEFAULT 0 NOT NULL COMMENT '有效期类型', /* 1:固定日期区间  2:固定时长 (自领取后按天算) */
  begin_timestamp INT DEFAULT 0 NOT NULL COMMENT '起用时间(固定日期区间专用)',
  end_timestamp INT DEFAULT 0 NOT NULL COMMENT '结束时间(固定日期区间专用)',
  fixed_begin_term INT DEFAULT 0 NOT NULL COMMENT '领取后多少天开始生效(固定时长专用)',
  fixed_term INT DEFAULT 0 NOT NULL COMMENT '自领取后多少天内有效(固定时长专用)',

  created_at INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  updated_at INT NOT NULL DEFAULT 0 COMMENT '修改时间'
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*卡券兑换详情*/
DROP TABLE IF EXISTS pre_ticket_detail;
CREATE TABLE IF NOT EXISTS pre_ticket_detail(
  id INT AUTO_INCREMENT PRIMARY KEY ,
  ticket_id INT NOT NULL DEFAULT 0 COMMENT '卡券id',
  member_id INT NOT NULL DEFAULT 0 COMMENT '会员id',
  code VARCHAR(255) NOT NULL DEFAULT '' COMMENT '校验码',/*校验码*/
  status TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否已使用',/* 是否已使用: 1:未使用 2:已使用 3.作废*/
  begin_timestamp INT DEFAULT 0 NOT NULL COMMENT '起用时间',
  end_timestamp INT DEFAULT 0 NOT NULL COMMENT '结束时间',
  created_at INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  exchange_timestamp INT NOT NULL DEFAULT 0 COMMENT '兑换时间',
  KEY ind_code(code),
  KEY ind_member_id(member_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/* 实际支付记录 */
DROP TABLE IF EXISTS pre_actual_pay;
CREATE TABLE IF NOT EXISTS pre_actual_pay(
  id INT AUTO_INCREMENT PRIMARY KEY ,
  order_id INT NOT NULL DEFAULT 0 COMMENT '订单id',
  type TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付方式',/* 支付方式: 1:现金 2:微信 3.卡券*/
  status TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '支付状态', /* 1.未支付  2.已支付 3.已退款*/
  fee DEC(10,2) NOT NULL DEFAULT 0 COMMENT '支付总额',
  remark text COMMENT '备注',
  created_at INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  KEY ind_order_id(order_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/* 卡券使用记录表 */
DROP TABLE IF EXISTS pre_ticket_use;
CREATE TABLE IF NOT EXISTS pre_ticket_use(
  ticket_id INT NOT NULL DEFAULT 0 COMMENT '卡券id',
  actual_pay_id INT NOT NULL DEFAULT 0 COMMENT '实际支付id',
  created_at INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  KEY ind_actual_pay_id(actual_pay_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/* 积分表 */
DROP TABLE IF EXISTS pre_score;
CREATE TABLE IF NOT EXISTS pre_score(
  `id` INT AUTO_INCREMENT PRIMARY KEY ,
  `out_trade_no` VARCHAR(32) DEFAULT '' COMMENT '订单号',/*对外显示使用的订单号 唯一 */
  `point` INT NOT NULL DEFAULT 0 COMMENT '积分'
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/*常用地址地址*/
DROP TABLE IF EXISTS pre_address;
CREATE TABLE IF NOT EXISTS pre_address(
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT DEFAULT 0 COMMENT '会员ID',
  detail VARCHAR(255) NOT NULL DEFAULT '' COMMENT '详细地址',
  mobile VARCHAR(50) NOT NULL DEFAULT '' COMMENT '手机',
  is_default TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '是否默认地址',
  `created_at` INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  `updated_at` INT NOT NULL DEFAULT 0 COMMENT '修改时间',
  KEY ind_member_id(member_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 AUTO_INCREMENT=1001;

/*会员收藏表*/
DROP TABLE IF EXISTS pre_collect;
CREATE TABLE IF NOT EXISTS pre_collect(
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT DEFAULT 0 COMMENT '会员ID',
  product_id INT DEFAULT 0 COMMENT '服务ID',
  employee_id INT DEFAULT 0 COMMENT '技师ID',
  type TINYINT UNSIGNED NOT NULL DEFAULT 0, /*10服务 20技师*/
  `created_at` INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  `updated_at` INT NOT NULL DEFAULT 0 COMMENT '修改时间',
  KEY ind_member_id(member_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 AUTO_INCREMENT=1001;

/* 技师服务时段 */
DROP TABLE IF EXISTS pre_service_time;
CREATE TABLE IF NOT EXISTS pre_service_time(
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT DEFAULT 0 COMMENT '技师ID',
  `date` date NOT NULL DEFAULT '0000-00-00' COMMENT '日期',
  `timeslot` CHAR(48) NOT NULL DEFAULT '' COMMENT '服务时间',/*以半小时为维度的bitmap，24小时共48位。0表示没有预约 1表示已预约*/
  `created_at` INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  `updated_at` INT NOT NULL DEFAULT 0 COMMENT '修改时间',
  KEY ind_employee_id(employee_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/* 技师休息时间 */
DROP TABLE IF EXISTS pre_rest_time;
CREATE TABLE IF NOT EXISTS pre_rest_time(
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `employee_id` INT DEFAULT 0 COMMENT '技师ID',
  `time` INT NOT NULL DEFAULT 0 COMMENT '休息时间',
  KEY ind_employee_id(employee_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/* 广告表 */
DROP TABLE IF EXISTS pre_ad;
CREATE TABLE IF NOT EXISTS pre_ad(
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(255) NOT NULL DEFAULT '' COMMENT '名称',
  `image` VARCHAR(255) NOT NULL DEFAULT '' COMMENT '图片',
  `url` VARCHAR(255) NOT NULL DEFAULT 'http://' COMMENT '链接URL',
  `created_at` INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  `updated_at` INT NOT NULL DEFAULT 0 COMMENT '修改时间'
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/* 微信支付结果 */
DROP TABLE IF EXISTS pre_wxpay_result;
CREATE TABLE IF NOT EXISTS pre_wxpay_result(
  id INT AUTO_INCREMENT PRIMARY KEY,
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

/*消费记录*/
DROP TABLE IF EXISTS pre_consumption;
CREATE TABLE IF NOT EXISTS pre_consumption(
	id INT AUTO_INCREMENT PRIMARY KEY,
	member_id INT NOT NULL COMMENT '会员ID',
	amount DEC(8,2) NOT NULL DEFAULT 0 COMMENT '金额',  /*充钱：正数   消费：负数*/
	type INT NOT NULL DEFAULT 1 COMMENT '类型', /* 1:订单 2:充值 3:赠送 */
	relation_id INT NOT NULL DEFAULT 0 COMMENT '关联ID',
	content text COMMENT '描述',
	created_at INT DEFAULT 0 NOT NULL,
	updated_at INT DEFAULT 0 NOT NULL,
	KEY ind_member (member_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

/* 后台充值记录 */
DROP TABLE IF EXISTS pre_recharge ;
CREATE TABLE IF NOT EXISTS pre_recharge(
	id INT AUTO_INCREMENT PRIMARY KEY,
	user_id INT NOT NULL COMMENT '操作员ID',
	member_id INT NOT NULL COMMENT '会员ID',
	amount DEC(8,2) NOT NULL DEFAULT 0 COMMENT '金额',
	remark text COMMENT '备注',
	created_at INT DEFAULT 0 NOT NULL,
	updated_at INT DEFAULT 0 NOT NULL,
	KEY ind_member (member_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;


/* 退货单 */
/*DROP TABLE IF EXISTS pre_return_order;
CREATE TABLE IF NOT EXISTS pre_return_order(
  id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL DEFAULT 0 COMMENT '下单人',
  order_id INT DEFAULT 0 COMMENT '订单ID',
  order_item_id INT DEFAULT 0 COMMENT '商品ID',
  contact VARCHAR(50) DEFAULT '' COMMENT '联系人',
  mobile VARCHAR(50) DEFAULT '' COMMENT '联系电话',
  message TEXT COMMENT '退货原因',
  seller_remark TEXT COMMENT '商户备注',
  status INT NOT NULL DEFAULT 1 COMMENT '状态', *//*1申请退货 2.用户取消取货 3.同意退货 4拒绝退货 5退货完成*//*
  created_at INT NOT NULL DEFAULT 0 COMMENT '创建时间',
  updated_at INT NOT NULL DEFAULT 0 COMMENT '更新时间',
  KEY ind_member_id(member_id)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8MB4 AUTO_INCREMENT=1001;*/

/*
20150909 V1.0上线
新增字段 alter table pre_order add service_type TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '服务方式' after pay_type;

20151008
新增字段 alter table pre_order add receiver_name VARCHAR(50) NOT NULL DEFAULT '' COMMENT '客户姓名' after receiver_zip;
新增字段 alter table pre_order add employee_source TINYINT UNSIGNED NOT NULL DEFAULT 0 COMMENT '技师来源' after service_type;
新增字段 alter table pre_member add `auth_key` VARCHAR(13) NOT NULL DEFAULT '' COMMENT 'Auth Key' after `mobile`;

20151103
新增字段
alter table pre_order add `longitude` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '经度' after receiver_email;
alter table pre_order add `latitude` VARCHAR(100) NOT NULL DEFAULT '' COMMENT '纬度' after longitude;
alter table pre_order add `completed_time` INT NOT NULL DEFAULT 0 COMMENT '完成时间' after latitude;
*/


