-- lpi-cn 研究逻辑表单要求填入报告名称
ALTER TABLE `contact_us`
    ADD COLUMN `product_name` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '报告名称' AFTER `product_id`;
