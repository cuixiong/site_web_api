ALTER TABLE `contact_us`
    ADD COLUMN `address` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NOT NULL DEFAULT '' COMMENT '地址' AFTER `city_id`;
