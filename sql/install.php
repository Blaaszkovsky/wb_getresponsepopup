<?php
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

$sql = [];

$sql[] = 'CREATE TABLE IF NOT EXISTS `' . _DB_PREFIX_ . 'wb_getresponse_subscriber` (
    `id_subscriber` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
    `email` VARCHAR(255) NOT NULL,
    `first_name` VARCHAR(255) DEFAULT NULL,
    `last_name` VARCHAR(255) DEFAULT NULL,
    `date_of_birth` DATE DEFAULT NULL,
    `id_cart_rule` INT(11) UNSIGNED DEFAULT NULL,
    `discount_code` VARCHAR(64) DEFAULT NULL,
    `id_shop` INT(11) UNSIGNED NOT NULL DEFAULT 1,
    `date_add` DATETIME NOT NULL,
    PRIMARY KEY (`id_subscriber`),
    UNIQUE KEY `email_shop` (`email`, `id_shop`)
) ENGINE=' . _MYSQL_ENGINE_ . ' DEFAULT CHARSET=utf8mb4;';

foreach ($sql as $query) {
    if (!Db::getInstance()->execute($query)) {
        return false;
    }
}
