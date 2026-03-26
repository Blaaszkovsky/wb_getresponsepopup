<?php
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\Module\WbGetresponsePopup\Service\DiscountService;
use PrestaShop\Module\WbGetresponsePopup\Service\GetResponseService;

class Wb_getresponsepopupAjaxModuleFrontController extends ModuleFrontController
{
    public function init(): void
    {
        $this->ajax = true;
        parent::init();
    }

    public function initContent(): void
    {
        // Do not call parent - it would render the page layout
    }

    public function displayAjaxSubscribe(): void
    {
        try {
            $this->processSubscription();
        } catch (\Throwable $e) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'An unexpected error occurred. Please try again.',
            ]);
        }
    }

    private function processSubscription(): void
    {
        $email = (string) Tools::getValue('email');
        $firstName = (string) Tools::getValue('first_name');
        $lastName = (string) Tools::getValue('last_name');
        $birthday = (string) Tools::getValue('date_of_birth');
        $consent = (int) Tools::getValue('consent');
        $token = (string) Tools::getValue('token');

        if ($token !== Tools::getToken(false)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Invalid security token. Please refresh the page and try again.',
            ]);
        }

        if (!$consent) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'You must agree to receive marketing communications.',
            ]);
        }

        if (!Validate::isEmail($email)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Please enter a valid email address.',
            ]);
        }

        if ($firstName !== '' && !Validate::isGenericName($firstName)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Please enter a valid first name.',
            ]);
        }

        if ($lastName !== '' && !Validate::isGenericName($lastName)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Please enter a valid last name.',
            ]);
        }

        if ($birthday !== '' && !Validate::isBirthDate($birthday)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Please enter a valid date of birth.',
            ]);
        }

        $idShop = (int) $this->context->shop->id;

        if ($this->isAlreadySubscribed($email, $idShop)) {
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'This email is already subscribed.',
            ]);
        }

        $campaignToken = (string) Configuration::get('WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN');
        $name = trim($firstName . ' ' . $lastName) ?: null;

        // Generate discount code BEFORE GetResponse call so it can be included
        $idCustomer = 0;
        $customer = $this->context->customer;
        if ($customer && $customer->isLogged()) {
            $idCustomer = (int) $customer->id;
        }

        $discountService = new DiscountService();
        $discountResult = $discountService->generateDiscount($email, $idShop, $idCustomer);

        // Build custom fields for GetResponse
        $customFields = [];
        if ($birthday !== '') {
            $customFields[] = [
                'customFieldId' => 'birthday',
                'value' => [$birthday],
            ];
        }

        // Include discount code in initial contact creation for autoresponder
        $grDiscountFieldName = (string) Configuration::get('WB_GETRESPONSEPOPUP_GR_DISCOUNT_FIELD_ID');
        if ($discountResult !== null && $grDiscountFieldName !== '') {
            $customFields[] = [
                'customFieldId' => $grDiscountFieldName,
                'value' => [$discountResult['code']],
            ];
        }

        // Add contact to GetResponse with all data at once
        $grService = new GetResponseService();
        $result = $grService->addContact($email, $campaignToken, $name, $customFields);

        if (!$result['success']) {
            // GR failed — remove generated CartRule
            $this->rollbackDiscount($discountResult);
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'Subscription failed: ' . ($result['error'] ?? 'Unknown error'),
            ]);
        }

        // Contact already exists in GetResponse — remove generated CartRule, show message
        if (!empty($result['alreadyExists'])) {
            $this->rollbackDiscount($discountResult);
            $this->sendJsonResponse([
                'success' => false,
                'message' => 'This email address is already in our database.',
            ]);
        }

        $this->saveSubscriber($email, $firstName, $lastName, $birthday, $discountResult, $idShop);

        $response = [
            'success' => true,
            'message' => 'Thank you for subscribing!',
        ];

        if ($discountResult !== null && (bool) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_SHOW_CODE')) {
            $response['discount_code'] = $discountResult['code'];
        }

        $this->sendJsonResponse($response);
    }

    private function rollbackDiscount(?array $discountResult): void
    {
        if ($discountResult === null) {
            return;
        }

        $cartRule = new CartRule((int) $discountResult['id_cart_rule']);
        if (Validate::isLoadedObject($cartRule)) {
            $cartRule->delete();
        }
    }

    private function isAlreadySubscribed(string $email, int $idShop): bool
    {
        $result = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'wb_getresponse_subscriber`
            WHERE `email` = \'' . pSQL($email) . '\' AND `id_shop` = ' . $idShop
        );

        return (int) $result > 0;
    }

    private function saveSubscriber(
        string $email,
        string $firstName,
        string $lastName,
        string $birthday,
        ?array $discountResult,
        int $idShop
    ): void {
        $data = [
            'email' => pSQL($email),
            'id_shop' => $idShop,
            'date_add' => date('Y-m-d H:i:s'),
        ];

        if ($firstName !== '') {
            $data['first_name'] = pSQL($firstName);
        }

        if ($lastName !== '') {
            $data['last_name'] = pSQL($lastName);
        }

        if ($birthday !== '') {
            $data['date_of_birth'] = pSQL($birthday);
        }

        if ($discountResult !== null) {
            $data['id_cart_rule'] = (int) $discountResult['id_cart_rule'];
            $data['discount_code'] = pSQL($discountResult['code']);
        }

        Db::getInstance()->insert('wb_getresponse_subscriber', $data);
    }

    private function sendJsonResponse(array $response): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($response);
        exit;
    }
}
