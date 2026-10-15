<?php
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

function upgrade_module_1_1_0(Wb_getresponsepopup $module): bool
{
    if (Configuration::hasKey('WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUDED_PRODUCTS')) {
        return true;
    }

    return Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUDED_PRODUCTS', '[]');
}
