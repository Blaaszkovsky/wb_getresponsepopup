<?php
declare(strict_types=1);

namespace PrestaShop\Module\WbGetresponsePopup\Service;

use CartRule;
use Configuration;
use Context;
use Currency;
use Db;
use Language;
use Tools;

class DiscountService
{
    public function generateDiscount(string $email, int $idShop, int $idCustomer = 0): ?array
    {
        if (!Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_ENABLED')) {
            return null;
        }

        $discountType = Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_TYPE');
        $discountValue = (float) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_VALUE');
        $discountDays = (int) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_DAYS');
        $minCart = (float) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_MIN_CART');
        $exclusive = (bool) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUSIVE');

        $code = 'GR-' . strtoupper(Tools::passwdGen(8));
        $now = date('Y-m-d H:i:s');
        $dateTo = date('Y-m-d H:i:s', strtotime('+' . $discountDays . ' days'));

        $languages = Language::getLanguages(true);
        $names = [];
        foreach ($languages as $lang) {
            $names[$lang['id_lang']] = 'GetResponse Newsletter - ' . $code;
        }

        $cartRule = new CartRule();
        $cartRule->name = $names;
        $cartRule->code = $code;
        $cartRule->date_from = $now;
        $cartRule->date_to = $dateTo;
        $cartRule->quantity = 1;
        $cartRule->quantity_per_user = 1;
        $cartRule->priority = 1;
        $cartRule->partial_use = false;
        $cartRule->active = true;
        $cartRule->id_shop_list = [$idShop];

        $defaultCurrency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        switch ($discountType) {
            case 'percentage':
                $cartRule->reduction_percent = $discountValue;
                break;
            case 'amount':
                $cartRule->reduction_amount = $discountValue;
                $cartRule->reduction_currency = (int) $defaultCurrency->id;
                $cartRule->reduction_tax = true;
                break;
            case 'free_shipping':
                $cartRule->free_shipping = true;
                break;
        }

        if ($minCart > 0) {
            $cartRule->minimum_amount = $minCart;
            $cartRule->minimum_amount_currency = (int) $defaultCurrency->id;
            $cartRule->minimum_amount_tax = true;
            $cartRule->minimum_amount_shipping = false;
        }

        if ($exclusive) {
            $cartRule->cart_rule_restriction = true;
        }

        if ($idCustomer > 0) {
            $cartRule->id_customer = $idCustomer;
        }

        if (!$cartRule->add()) {
            return null;
        }

        $this->applyProductRestrictions((int) $cartRule->id);
        $this->applyCarrierRestrictions((int) $cartRule->id);

        return [
            'code' => $code,
            'id_cart_rule' => (int) $cartRule->id,
        ];
    }

    private function applyProductRestrictions(int $idCartRule): void
    {
        $categories = json_decode((string) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_CATEGORIES'), true);
        $products = json_decode((string) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS'), true);

        if (empty($categories) && empty($products)) {
            return;
        }

        $db = Db::getInstance();

        $db->insert('cart_rule_product_rule_group', [
            'id_cart_rule' => $idCartRule,
            'quantity' => 1,
        ]);

        $idGroup = (int) $db->Insert_ID();

        if (!empty($categories)) {
            $db->insert('cart_rule_product_rule', [
                'id_product_rule_group' => $idGroup,
                'type' => 'categories',
            ]);
            $idRule = (int) $db->Insert_ID();

            foreach ($categories as $idCategory) {
                $db->insert('cart_rule_product_rule_value', [
                    'id_product_rule' => $idRule,
                    'id_item' => (int) $idCategory,
                ]);
            }
        }

        if (!empty($products)) {
            $db->insert('cart_rule_product_rule', [
                'id_product_rule_group' => $idGroup,
                'type' => 'products',
            ]);
            $idRule = (int) $db->Insert_ID();

            foreach ($products as $idProduct) {
                $db->insert('cart_rule_product_rule_value', [
                    'id_product_rule' => $idRule,
                    'id_item' => (int) $idProduct,
                ]);
            }
        }
    }

    private function applyCarrierRestrictions(int $idCartRule): void
    {
        $carriers = json_decode((string) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_CARRIERS'), true);

        if (empty($carriers)) {
            return;
        }

        $db = Db::getInstance();

        foreach ($carriers as $idCarrier) {
            $db->insert('cart_rule_carrier', [
                'id_cart_rule' => $idCartRule,
                'id_carrier' => (int) $idCarrier,
            ]);
        }
    }
}
