alter table pre_bank_config add `bank_id` int(10) NOT NULL COMMENT '银行id' after `id`;
alter table pre_bank_config add index index_bank_id (bank_id);
alter table pre_order add `bank_id` int(10) NOT NULL COMMENT '银行id' after `member_id`;
alter table `pre_bank` add note varchar(255) not null comment '注解' after `bank_name`;