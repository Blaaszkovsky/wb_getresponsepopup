<?php
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\Module\WbGetresponsePopup\Service\DiscountService;
use PrestaShop\Module\WbGetresponsePopup\Service\GetResponseService;

/**
 * Public REST endpoint for newsletter signup + discount code generation.
 *
 * POST index.php?fc=module&module=wb_getresponsepopup&controller=api
 * Header: X-Api-Key: <WB_GETRESPONSEPOPUP_API_ENDPOINT_KEY>
 * Body (application/json): { "email": "...", "name": "...", "campaignId": "..." }
 */
class Wb_getresponsepopupApiModuleFrontController extends ModuleFrontController
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

    public function postProcess(): void
    {
        try {
            $this->handleRequest();
        } catch (\Throwable $e) {
            $this->sendJson(500, [
                'success' => false,
                'error' => 'server_error',
                'message' => 'An unexpected error occurred. Please try again.',
            ]);
        }
    }

    private function handleRequest(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
            $this->sendJson(405, [
                'success' => false,
                'error' => 'method_not_allowed',
                'message' => 'Only POST requests are accepted.',
            ]);
        }

        $configuredKey = (string) Configuration::get('WB_GETRESPONSEPOPUP_API_ENDPOINT_KEY');
        if ($configuredKey === '') {
            $this->sendJson(503, [
                'success' => false,
                'error' => 'endpoint_disabled',
                'message' => 'The API endpoint is not configured.',
            ]);
        }

        $providedKey = (string) ($_SERVER['HTTP_X_API_KEY'] ?? '');
        if ($providedKey === '' || !hash_equals($configuredKey, $providedKey)) {
            $this->sendJson(401, [
                'success' => false,
                'error' => 'unauthorized',
                'message' => 'Invalid or missing API key.',
            ]);
        }

        // Record the moment of an authenticated call (a legitimate "run").
        Configuration::updateValue('WB_GETRESPONSEPOPUP_API_LAST_RUN', date('Y-m-d H:i:s'));

        $payload = $this->parseJsonBody();

        $email = isset($payload['email']) ? trim((string) $payload['email']) : '';
        $name = isset($payload['name']) ? trim((string) $payload['name']) : '';
        $campaignId = isset($payload['campaignId']) ? trim((string) $payload['campaignId']) : '';

        if (!Validate::isEmail($email)) {
            $this->sendJson(400, [
                'success' => false,
                'error' => 'invalid_request',
                'message' => 'A valid "email" field is required.',
            ]);
        }

        if ($name !== '' && !Validate::isGenericName($name)) {
            $this->sendJson(400, [
                'success' => false,
                'error' => 'invalid_request',
                'message' => 'The "name" field contains invalid characters.',
            ]);
        }

        if ($campaignId === '') {
            $campaignId = (string) Configuration::get('WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN');
        }

        if ($campaignId === '') {
            $this->sendJson(500, [
                'success' => false,
                'error' => 'server_error',
                'message' => 'No campaign is configured for this endpoint.',
            ]);
        }

        $idShop = (int) $this->context->shop->id;

        if ($this->isAlreadySubscribed($email, $idShop)) {
            $this->sendJson(409, [
                'success' => false,
                'error' => 'already_exists',
                'message' => 'This email address is already subscribed.',
            ]);
        }

        // Generate discount BEFORE the GetResponse call so it can travel in the contact payload.
        $discountService = new DiscountService();
        $discountResult = $discountService->generateDiscount($email, $idShop, 0);

        $customFields = [];
        $grDiscountFieldName = (string) Configuration::get('WB_GETRESPONSEPOPUP_GR_DISCOUNT_FIELD_ID');
        if ($discountResult !== null && $grDiscountFieldName !== '') {
            $customFields[] = [
                'customFieldId' => $grDiscountFieldName,
                'value' => [$discountResult['code']],
            ];
        }

        $grService = new GetResponseService();
        $result = $grService->addContact($email, $campaignId, $name !== '' ? $name : null, $customFields);

        if (empty($result['success'])) {
            $this->rollbackDiscount($discountResult);
            $this->sendJson(502, [
                'success' => false,
                'error' => 'upstream_error',
                'message' => 'Subscription failed: ' . ($result['error'] ?? 'Unknown error'),
            ]);
        }

        if (!empty($result['alreadyExists'])) {
            $this->rollbackDiscount($discountResult);
            $this->sendJson(409, [
                'success' => false,
                'error' => 'already_exists',
                'message' => 'This email address is already in our database.',
            ]);
        }

        $this->saveSubscriber($email, $name, $discountResult, $idShop);

        $this->sendJson(201, [
            'success' => true,
            'message' => 'Subscribed successfully.',
            'discount' => $this->buildDiscountDetails($discountResult),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function parseJsonBody(): array
    {
        $raw = (string) file_get_contents('php://input');
        if ($raw === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            $this->sendJson(400, [
                'success' => false,
                'error' => 'invalid_request',
                'message' => 'Request body must be valid JSON.',
            ]);
        }

        return $decoded;
    }

    private function buildDiscountDetails(?array $discountResult): ?array
    {
        if ($discountResult === null) {
            return null;
        }

        $currency = new Currency((int) Configuration::get('PS_CURRENCY_DEFAULT'));

        $expiresAt = null;
        $cartRule = new CartRule((int) $discountResult['id_cart_rule']);
        if (Validate::isLoadedObject($cartRule)) {
            $expiresAt = $cartRule->date_to;
        }

        return [
            'code' => $discountResult['code'],
            'type' => (string) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_TYPE'),
            'value' => (float) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_VALUE'),
            'currency' => $currency->iso_code,
            'minimum_cart' => (float) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_MIN_CART'),
            'expires_at' => $expiresAt,
        ];
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

    private function saveSubscriber(string $email, string $name, ?array $discountResult, int $idShop): void
    {
        $firstName = '';
        $lastName = '';
        if ($name !== '') {
            $parts = explode(' ', $name, 2);
            $firstName = $parts[0];
            $lastName = $parts[1] ?? '';
        }

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

        if ($discountResult !== null) {
            $data['id_cart_rule'] = (int) $discountResult['id_cart_rule'];
            $data['discount_code'] = pSQL($discountResult['code']);
        }

        Db::getInstance()->insert('wb_getresponse_subscriber', $data);
    }

    private function sendJson(int $status, array $response): void
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        echo json_encode($response);
        exit;
    }
}
