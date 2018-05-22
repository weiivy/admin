
USE test;

/* 用户表 */
DROP TABLE IF EXISTS pre_user;
CREATE TABLE IF NOT EXISTS `pre_user`(
  `id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(255) NOT NULL DEFAULT '',
  `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
  `password_reset_token` VARCHAR(255) NOT NULL DEFAULT '',
  `email` VARCHAR(255) NOT NULL DEFAULT '',
  `status` TINYINT UNSIGNED NOT NULL DEFAULT 0, /* 10.允许登录 20.禁止登录 30.删除 */
  `login_at` INT NOT NULL DEFAULT 0 COMMENT '登录时间',
  `created_at` INT NOT NULL DEFAULT 0 COMMENT '新增时间',
  `updated_at` INT NOT NULL DEFAULT 0 COMMENT '修改时间',
  KEY ind_username(username)
)ENGINE=InnoDB DEFAULT CHARSET=UTF8 AUTO_INCREMENT=1001;

INSERT INTO pre_user (username,password_hash,status) VALUES ('root', md5('root'), 10);
