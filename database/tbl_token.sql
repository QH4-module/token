DROP TABLE IF EXISTS `tbl_token`;

CREATE TABLE IF NOT EXISTS `tbl_token`
(
    `token`           VARCHAR(64)  NOT NULL,
    `create_time`     BIGINT       NOT NULL COMMENT '生成时间',
    `expiration_time` BIGINT       NOT NULL COMMENT '过期时间',
    `is_login`        TINYINT      NOT NULL,
    `is_logout`       TINYINT      NOT NULL,
    `user_id`         VARCHAR(64)  NOT NULL COMMENT '未登录为空,登录后为用户的id',
    `device_type`     VARCHAR(20)  NULL COMMENT '设备类型,一般为 ios,android,web,wechat等',
    `device_id`       VARCHAR(200) NULL,
    `old_token`       VARCHAR(64)  NULL COMMENT '旧的Token',
    `del_time`        BIGINT       NOT NULL COMMENT '标记为删除的token,gc会定期清理无效的token',
    PRIMARY KEY (`token`)
)
    ENGINE = InnoDB
    COMMENT = 'Token表';

CREATE UNIQUE INDEX `token_UNIQUE` ON `tbl_token` (`token` ASC);

CREATE INDEX `index_user_id` ON `tbl_token` (`user_id` ASC);
