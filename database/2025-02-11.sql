ALTER TABLE `orders`
    ADD COLUMN `send_email_time` int NULL DEFAULT 0 COMMENT '成功发送邮件时间' AFTER `is_delete`;

ALTER TABLE `contact_us`
    ADD COLUMN `send_email_time` int NULL DEFAULT 0 COMMENT '成功发送邮件时间' AFTER `sort`;


