ALTER TABLE `menus`
    ADD COLUMN `banner_content` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT '' COMMENT '页面背景图额外文字' AFTER `banner_short_title`;
