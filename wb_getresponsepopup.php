<?php
declare(strict_types=1);

if (!defined('_PS_VERSION_')) {
    exit;
}

$autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

class Wb_getresponsepopup extends Module
{
    private const CONFIG_KEYS = [
        'WB_GETRESPONSEPOPUP_ACTIVE',
        'WB_GETRESPONSEPOPUP_DEV_MODE',
        'WB_GETRESPONSEPOPUP_DEV_PARAM',
        'WB_GETRESPONSEPOPUP_API_KEY',
        'WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN',
        'WB_GETRESPONSEPOPUP_GR_DISCOUNT_FIELD_ID',
        'WB_GETRESPONSEPOPUP_FIELD_FIRSTNAME',
        'WB_GETRESPONSEPOPUP_FIELD_LASTNAME',
        'WB_GETRESPONSEPOPUP_FIELD_BIRTHDAY',
        'WB_GETRESPONSEPOPUP_DATE_FROM',
        'WB_GETRESPONSEPOPUP_DATE_TO',
        'WB_GETRESPONSEPOPUP_TRIGGER_TYPE',
        'WB_GETRESPONSEPOPUP_TRIGGER_VALUE',
        'WB_GETRESPONSEPOPUP_DISCOUNT_ENABLED',
        'WB_GETRESPONSEPOPUP_DISCOUNT_TYPE',
        'WB_GETRESPONSEPOPUP_DISCOUNT_VALUE',
        'WB_GETRESPONSEPOPUP_DISCOUNT_DAYS',
        'WB_GETRESPONSEPOPUP_DISCOUNT_MIN_CART',
        'WB_GETRESPONSEPOPUP_DISCOUNT_CATEGORIES',
        'WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS',
        'WB_GETRESPONSEPOPUP_DISCOUNT_CARRIERS',
        'WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUSIVE',
        'WB_GETRESPONSEPOPUP_DISCOUNT_SHOW_CODE',
        'WB_GETRESPONSEPOPUP_COOKIE_CLOSED_MIN',
        'WB_GETRESPONSEPOPUP_COOKIE_SUBSCRIBED_MIN',
        'WB_GETRESPONSEPOPUP_POPUP_IMAGE_DESKTOP',
        'WB_GETRESPONSEPOPUP_POPUP_IMAGE_MOBILE',
        'WB_GETRESPONSEPOPUP_TEXT_TITLE',
        'WB_GETRESPONSEPOPUP_TEXT_SUBTITLE',
        'WB_GETRESPONSEPOPUP_TEXT_SUBMIT',
        'WB_GETRESPONSEPOPUP_TEXT_CONSENT',
        'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_TITLE',
        'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_MSG',
        'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_TEXT',
        'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_HINT',
    ];

    private const MULTILANG_CONFIG_KEYS = [
        'WB_GETRESPONSEPOPUP_TEXT_TITLE',
        'WB_GETRESPONSEPOPUP_TEXT_SUBTITLE',
        'WB_GETRESPONSEPOPUP_TEXT_SUBMIT',
        'WB_GETRESPONSEPOPUP_TEXT_CONSENT',
        'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_TITLE',
        'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_MSG',
        'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_TEXT',
        'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_HINT',
    ];

    private const MULTILANG_DEFAULTS = [
        'WB_GETRESPONSEPOPUP_TEXT_TITLE' => 'Subscribe to our newsletter',
        'WB_GETRESPONSEPOPUP_TEXT_SUBTITLE' => 'Join us and get',
        'WB_GETRESPONSEPOPUP_TEXT_SUBMIT' => 'Subscribe',
        'WB_GETRESPONSEPOPUP_TEXT_CONSENT' => 'I agree to receive marketing communications and accept the privacy policy.',
        'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_TITLE' => 'Thank you!',
        'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_MSG' => 'You have been successfully subscribed to our newsletter.',
        'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_TEXT' => 'Here is your exclusive discount code:',
        'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_HINT' => 'Use it at checkout to claim your discount!',
    ];

    private const ALLOWED_IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    private const ALLOWED_IMAGE_MIMES = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    private const MAX_IMAGE_SIZE = 2 * 1024 * 1024;

    public function __construct()
    {
        $this->name = 'wb_getresponsepopup';
        $this->tab = 'advertising_marketing';
        $this->version = '1.0.0';
        $this->author = 'WBShop.pl';
        $this->need_instance = 0;
        $this->bootstrap = true;
        $this->ps_versions_compliancy = ['min' => '8.0.0', 'max' => _PS_VERSION_];

        parent::__construct();

        $this->displayName = $this->trans(
            '(WB) GetResponse Newsletter Popup',
            [],
            'Modules.Wbgetresponsepopup.Admin'
        );
        $this->description = $this->trans(
            'Displays a newsletter subscription popup with GetResponse integration and optional discount code generation.',
            [],
            'Modules.Wbgetresponsepopup.Admin'
        );
    }

    public function isUsingNewTranslationSystem(): bool
    {
        return true;
    }

    public function install(): bool
    {
        include dirname(__FILE__) . '/sql/install.php';

        return parent::install()
            && $this->registerHook('actionFrontControllerSetMedia')
            && $this->registerHook('displayFooter')
            && $this->installDefaultConfig();
    }

    public function uninstall(): bool
    {
        include dirname(__FILE__) . '/sql/uninstall.php';

        $this->deleteUploadedImage('WB_GETRESPONSEPOPUP_POPUP_IMAGE_DESKTOP');
        $this->deleteUploadedImage('WB_GETRESPONSEPOPUP_POPUP_IMAGE_MOBILE');

        foreach (self::CONFIG_KEYS as $key) {
            Configuration::deleteByName($key);
        }

        return parent::uninstall();
    }

    private function installDefaultConfig(): bool
    {
        $defaults = [
            'WB_GETRESPONSEPOPUP_ACTIVE' => '0',
            'WB_GETRESPONSEPOPUP_DEV_MODE' => '0',
            'WB_GETRESPONSEPOPUP_DEV_PARAM' => 'wbpopup',
            'WB_GETRESPONSEPOPUP_API_KEY' => '',
            'WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN' => '',
            'WB_GETRESPONSEPOPUP_GR_DISCOUNT_FIELD_ID' => '',
            'WB_GETRESPONSEPOPUP_FIELD_FIRSTNAME' => '0',
            'WB_GETRESPONSEPOPUP_FIELD_LASTNAME' => '0',
            'WB_GETRESPONSEPOPUP_FIELD_BIRTHDAY' => '0',
            'WB_GETRESPONSEPOPUP_DATE_FROM' => '',
            'WB_GETRESPONSEPOPUP_DATE_TO' => '',
            'WB_GETRESPONSEPOPUP_TRIGGER_TYPE' => 'time',
            'WB_GETRESPONSEPOPUP_TRIGGER_VALUE' => '5',
            'WB_GETRESPONSEPOPUP_DISCOUNT_ENABLED' => '0',
            'WB_GETRESPONSEPOPUP_DISCOUNT_TYPE' => 'percentage',
            'WB_GETRESPONSEPOPUP_DISCOUNT_VALUE' => '10',
            'WB_GETRESPONSEPOPUP_DISCOUNT_DAYS' => '14',
            'WB_GETRESPONSEPOPUP_DISCOUNT_MIN_CART' => '0',
            'WB_GETRESPONSEPOPUP_DISCOUNT_CATEGORIES' => '[]',
            'WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS' => '[]',
            'WB_GETRESPONSEPOPUP_DISCOUNT_CARRIERS' => '[]',
            'WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUSIVE' => '0',
            'WB_GETRESPONSEPOPUP_DISCOUNT_SHOW_CODE' => '1',
            'WB_GETRESPONSEPOPUP_COOKIE_CLOSED_MIN' => '10080',
            'WB_GETRESPONSEPOPUP_COOKIE_SUBSCRIBED_MIN' => '43200',
            'WB_GETRESPONSEPOPUP_POPUP_IMAGE_DESKTOP' => '',
            'WB_GETRESPONSEPOPUP_POPUP_IMAGE_MOBILE' => '',
        ];

        foreach ($defaults as $key => $value) {
            if (!Configuration::updateValue($key, $value)) {
                return false;
            }
        }

        $languages = Language::getLanguages(false);

        foreach (self::MULTILANG_DEFAULTS as $key => $defaultValue) {
            $values = [];
            foreach ($languages as $lang) {
                $values[(int) $lang['id_lang']] = $defaultValue;
            }
            if (!Configuration::updateValue($key, $values, true)) {
                return false;
            }
        }

        return true;
    }

    public function getContent(): string
    {
        if (Tools::getValue('ajax')) {
            $this->handleAdminAjax();

            return '';
        }

        return $this->postProcess() . $this->renderForm();
    }

    private function handleAdminAjax(): void
    {
        $action = (string) Tools::getValue('action');

        switch ($action) {
            case 'testApiConnection':
                $this->ajaxTestApiConnection();
                break;
            case 'searchProducts':
                $this->ajaxSearchProducts();
                break;
        }

        exit;
    }

    private function ajaxTestApiConnection(): void
    {
        $apiKey = (string) Tools::getValue('api_key');

        if (empty($apiKey)) {
            $this->ajaxAdminResponse(false, 'Please enter an API key.');

            return;
        }

        $ch = curl_init('https://api.getresponse.com/v3/accounts');
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'X-Auth-Token: api-key ' . $apiKey,
            ],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError !== '') {
            $this->ajaxAdminResponse(false, 'Connection error: ' . $curlError);

            return;
        }

        if ($httpCode === 200) {
            $data = json_decode((string) $response, true);
            $accountName = $data['firstName'] ?? '';
            $accountEmail = $data['email'] ?? '';
            $this->ajaxAdminResponse(true, 'Connection successful! Account: ' . $accountName . ' (' . $accountEmail . ')');

            return;
        }

        $this->ajaxAdminResponse(false, 'Authentication failed (HTTP ' . $httpCode . '). Please check your API key.');
    }

    private function ajaxSearchProducts(): void
    {
        $query = trim((string) Tools::getValue('q'));

        if (strlen($query) < 2) {
            $this->ajaxAdminResponse(true, '', []);

            return;
        }

        $idLang = (int) $this->context->language->id;
        $idShop = (int) $this->context->shop->id;

        $sql = new DbQuery();
        $sql->select('p.`id_product`, pl.`name`, p.`reference`, p.`ean13`');
        $sql->from('product', 'p');
        $sql->innerJoin('product_shop', 'ps', 'ps.`id_product` = p.`id_product` AND ps.`id_shop` = ' . $idShop);
        $sql->leftJoin('product_lang', 'pl', 'pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop);
        $sql->where(
            'pl.`name` LIKE \'%' . pSQL($query) . '%\' OR p.`reference` LIKE \'%' . pSQL($query) . '%\' OR p.`ean13` LIKE \'%' . pSQL($query) . '%\' OR p.`id_product` = ' . (int) $query
        );
        $sql->where('ps.`active` = 1');
        $sql->groupBy('p.`id_product`');
        $sql->orderBy('pl.`name` ASC');
        $sql->limit(20);

        $results = Db::getInstance()->executeS($sql);
        $products = [];

        if ($results) {
            foreach ($results as $row) {
                $products[] = [
                    'id' => (int) $row['id_product'],
                    'name' => $row['name'],
                    'reference' => $row['reference'],
                    'ean13' => $row['ean13'],
                ];
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($products);
        exit;
    }

    private function ajaxAdminResponse(bool $success, string $message, ?array $data = null): void
    {
        header('Content-Type: application/json; charset=utf-8');
        $response = ['success' => $success, 'message' => $message];
        if ($data !== null) {
            $response['data'] = $data;
        }
        echo json_encode($response);
        exit;
    }

    private function postProcess(): string
    {
        if (!Tools::isSubmit('submitWbGetresponsePopup')) {
            return '';
        }

        $errors = [];

        $apiKey = (string) Tools::getValue('WB_GETRESPONSEPOPUP_API_KEY');
        $campaignToken = (string) Tools::getValue('WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN');
        $dateFrom = (string) Tools::getValue('WB_GETRESPONSEPOPUP_DATE_FROM');
        $dateTo = (string) Tools::getValue('WB_GETRESPONSEPOPUP_DATE_TO');
        $triggerValue = (int) Tools::getValue('WB_GETRESPONSEPOPUP_TRIGGER_VALUE');
        $discountEnabled = (int) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_ENABLED');
        $discountValue = (float) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_VALUE');
        $discountDays = (int) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_DAYS');
        $cookieClosed = (int) Tools::getValue('WB_GETRESPONSEPOPUP_COOKIE_CLOSED_MIN');
        $cookieSubscribed = (int) Tools::getValue('WB_GETRESPONSEPOPUP_COOKIE_SUBSCRIBED_MIN');

        if ($dateFrom !== '' && $dateTo !== '' && strtotime($dateFrom) > strtotime($dateTo)) {
            $errors[] = $this->trans('Date "from" must be earlier than date "to".', [], 'Modules.Wbgetresponsepopup.Admin');
        }

        if ($triggerValue < 0) {
            $errors[] = $this->trans('Trigger value must be a positive number.', [], 'Modules.Wbgetresponsepopup.Admin');
        }

        if ($discountEnabled && $discountValue <= 0) {
            $errors[] = $this->trans('Discount value must be greater than 0 when discounts are enabled.', [], 'Modules.Wbgetresponsepopup.Admin');
        }

        if ($discountEnabled && $discountDays < 1) {
            $errors[] = $this->trans('Discount validity must be at least 1 day.', [], 'Modules.Wbgetresponsepopup.Admin');
        }

        if ($cookieClosed < 1) {
            $errors[] = $this->trans('Cookie duration for closed popup must be at least 1 minute.', [], 'Modules.Wbgetresponsepopup.Admin');
        }

        if ($cookieSubscribed < 1) {
            $errors[] = $this->trans('Cookie duration for subscribed users must be at least 1 minute.', [], 'Modules.Wbgetresponsepopup.Admin');
        }

        $languages = Language::getLanguages(false);
        foreach (self::MULTILANG_CONFIG_KEYS as $key) {
            foreach ($languages as $lang) {
                $value = (string) Tools::getValue($key . '_' . $lang['id_lang']);
                if ($value !== '' && !Validate::isCleanHtml($value)) {
                    $errors[] = $this->trans(
                        'Invalid HTML content in field "%field%" for language %lang%.',
                        ['%field%' => $key, '%lang%' => $lang['name']],
                        'Modules.Wbgetresponsepopup.Admin'
                    );
                }
            }
        }

        $imageFields = [
            'desktop' => [
                'file_key' => 'WB_GETRESPONSEPOPUP_IMAGE_UPLOAD_DESKTOP',
                'config_key' => 'WB_GETRESPONSEPOPUP_POPUP_IMAGE_DESKTOP',
                'delete_key' => 'WB_GETRESPONSEPOPUP_DELETE_IMAGE_DESKTOP',
                'label' => 'Desktop',
            ],
            'mobile' => [
                'file_key' => 'WB_GETRESPONSEPOPUP_IMAGE_UPLOAD_MOBILE',
                'config_key' => 'WB_GETRESPONSEPOPUP_POPUP_IMAGE_MOBILE',
                'delete_key' => 'WB_GETRESPONSEPOPUP_DELETE_IMAGE_MOBILE',
                'label' => 'Mobile',
            ],
        ];

        $newImages = [];
        foreach ($imageFields as $key => $field) {
            try {
                $newImages[$key] = $this->processImageUpload($field['file_key']);
            } catch (\RuntimeException $e) {
                $errors[] = $field['label'] . ': ' . $e->getMessage();
                $newImages[$key] = null;
            }
        }

        if (!empty($errors)) {
            return $this->displayError(implode('<br>', $errors));
        }

        foreach ($imageFields as $key => $field) {
            if (Tools::getValue($field['delete_key'])) {
                $this->deleteUploadedImage($field['config_key']);
                Configuration::updateValue($field['config_key'], '');
            }

            if ($newImages[$key] !== null) {
                $this->deleteUploadedImage($field['config_key']);
                Configuration::updateValue($field['config_key'], pSQL($newImages[$key]));
            }
        }

        $categories = Tools::getValue('categoryBox');
        $products = (string) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS');
        $carriers = Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_CARRIERS');

        $productIds = [];
        if ($products !== '') {
            $productIds = array_map('intval', array_filter(explode(',', $products)));
        }

        Configuration::updateValue('WB_GETRESPONSEPOPUP_ACTIVE', (int) Tools::getValue('WB_GETRESPONSEPOPUP_ACTIVE'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DEV_MODE', (int) Tools::getValue('WB_GETRESPONSEPOPUP_DEV_MODE'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DEV_PARAM', pSQL(trim((string) Tools::getValue('WB_GETRESPONSEPOPUP_DEV_PARAM'))) ?: 'wbpopup');
        Configuration::updateValue('WB_GETRESPONSEPOPUP_API_KEY', pSQL($apiKey));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN', pSQL($campaignToken));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_GR_DISCOUNT_FIELD_ID', pSQL((string) Tools::getValue('WB_GETRESPONSEPOPUP_GR_DISCOUNT_FIELD_ID')));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_FIELD_FIRSTNAME', (int) Tools::getValue('WB_GETRESPONSEPOPUP_FIELD_FIRSTNAME'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_FIELD_LASTNAME', (int) Tools::getValue('WB_GETRESPONSEPOPUP_FIELD_LASTNAME'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_FIELD_BIRTHDAY', (int) Tools::getValue('WB_GETRESPONSEPOPUP_FIELD_BIRTHDAY'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DATE_FROM', pSQL($dateFrom));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DATE_TO', pSQL($dateTo));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_TRIGGER_TYPE', pSQL((string) Tools::getValue('WB_GETRESPONSEPOPUP_TRIGGER_TYPE')));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_TRIGGER_VALUE', $triggerValue);
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_ENABLED', $discountEnabled);
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_TYPE', pSQL((string) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_TYPE')));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_VALUE', (string) $discountValue);
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_DAYS', $discountDays);
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_MIN_CART', (string) (float) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_MIN_CART'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_CATEGORIES', json_encode(is_array($categories) ? array_map('intval', $categories) : []));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS', json_encode($productIds));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_CARRIERS', json_encode(is_array($carriers) ? array_map('intval', $carriers) : []));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUSIVE', (int) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUSIVE'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_DISCOUNT_SHOW_CODE', (int) Tools::getValue('WB_GETRESPONSEPOPUP_DISCOUNT_SHOW_CODE'));
        Configuration::updateValue('WB_GETRESPONSEPOPUP_COOKIE_CLOSED_MIN', $cookieClosed);
        Configuration::updateValue('WB_GETRESPONSEPOPUP_COOKIE_SUBSCRIBED_MIN', $cookieSubscribed);

        foreach (self::MULTILANG_CONFIG_KEYS as $key) {
            $values = [];
            foreach ($languages as $lang) {
                $values[(int) $lang['id_lang']] = (string) Tools::getValue($key . '_' . $lang['id_lang']);
            }
            Configuration::updateValue($key, $values, true);
        }

        return $this->displayConfirmation(
            $this->trans('Settings saved successfully.', [], 'Modules.Wbgetresponsepopup.Admin')
        );
    }

    private function processImageUpload(string $fileKey): ?string
    {
        $file = $_FILES[$fileKey] ?? null;

        if (!$file || (int) $file['error'] === UPLOAD_ERR_NO_FILE || (int) $file['size'] === 0) {
            return null;
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            throw new \RuntimeException(
                $this->trans('Image upload error (code: %code%).', ['%code%' => $file['error']], 'Modules.Wbgetresponsepopup.Admin')
            );
        }

        if ((int) $file['size'] > self::MAX_IMAGE_SIZE) {
            throw new \RuntimeException(
                $this->trans('Image file is too large. Maximum size is 2 MB.', [], 'Modules.Wbgetresponsepopup.Admin')
            );
        }

        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_IMAGE_EXTENSIONS, true)) {
            throw new \RuntimeException(
                $this->trans('Invalid file type. Allowed formats: JPG, PNG, GIF, WEBP.', [], 'Modules.Wbgetresponsepopup.Admin')
            );
        }

        $mime = mime_content_type($file['tmp_name']);
        if (!in_array($mime, self::ALLOWED_IMAGE_MIMES, true)) {
            throw new \RuntimeException(
                $this->trans('Invalid image MIME type detected (%mime%). The file may not be a genuine image.', ['%mime%' => $mime], 'Modules.Wbgetresponsepopup.Admin')
            );
        }

        $sourceImage = @imagecreatefromstring(file_get_contents($file['tmp_name']));
        if ($sourceImage === false) {
            throw new \RuntimeException(
                $this->trans('Could not process image. The file may be corrupted or contain invalid data.', [], 'Modules.Wbgetresponsepopup.Admin')
            );
        }

        $uploadsDir = _PS_MODULE_DIR_ . $this->name . '/uploads/';
        if (!is_dir($uploadsDir)) {
            mkdir($uploadsDir, 0755, true);
        }

        $safeName = bin2hex(random_bytes(16)) . '.' . $extension;
        $destPath = $uploadsDir . $safeName;

        $success = $this->reencodeImage($sourceImage, $destPath, $extension);
        imagedestroy($sourceImage);

        if (!$success) {
            throw new \RuntimeException(
                $this->trans('Failed to save processed image.', [], 'Modules.Wbgetresponsepopup.Admin')
            );
        }

        return $safeName;
    }

    private function reencodeImage(\GdImage $image, string $destPath, string $extension): bool
    {
        switch ($extension) {
            case 'jpg':
            case 'jpeg':
                return imagejpeg($image, $destPath, 90);

            case 'png':
                imagealphablending($image, false);
                imagesavealpha($image, true);

                return imagepng($image, $destPath, 6);

            case 'gif':
                return imagegif($image, $destPath);

            case 'webp':
                if (!function_exists('imagewebp')) {
                    return false;
                }
                imagealphablending($image, false);
                imagesavealpha($image, true);

                return imagewebp($image, $destPath, 90);

            default:
                return false;
        }
    }

    private function deleteUploadedImage(string $configKey): void
    {
        $currentImage = (string) Configuration::get($configKey);
        if ($currentImage === '') {
            return;
        }

        $filePath = _PS_MODULE_DIR_ . $this->name . '/uploads/' . $currentImage;
        if (file_exists($filePath) && is_file($filePath)) {
            unlink($filePath);
        }
    }

    private function renderForm(): string
    {
        $helper = new HelperForm();
        $helper->module = $this;
        $helper->name_controller = $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->currentIndex = AdminController::$currentIndex . '&configure=' . $this->name;
        $helper->default_form_language = (int) Configuration::get('PS_LANG_DEFAULT');
        $helper->allow_employee_form_lang = (int) Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG');
        $helper->languages = Language::getLanguages(false);
        $helper->submit_action = 'submitWbGetresponsePopup';
        $helper->tpl_vars = [
            'fields_value' => $this->getConfigFieldsValues(),
        ];

        $adminJs = _PS_MODULE_DIR_ . $this->name . '/views/js/admin/config.js';
        $adminCss = _PS_MODULE_DIR_ . $this->name . '/views/css/admin/config.css';

        $output = '';
        if (file_exists($adminCss)) {
            $output .= '<link rel="stylesheet" href="' . $this->getPathUri() . 'views/css/admin/config.css">';
        }

        $output .= $helper->generateForm($this->getFormFields());

        if (file_exists($adminJs)) {
            $ajaxUrl = $this->context->link->getAdminLink('AdminModules') . '&configure=' . $this->name . '&ajax=1';
            $output .= '<script>var wbGrPopupAjaxUrl = ' . json_encode($ajaxUrl) . ';</script>';
            $output .= '<script src="' . $this->getPathUri() . 'views/js/admin/config.js"></script>';
        }

        return $output;
    }

    private function getConfigFieldsValues(): array
    {
        $values = [];

        foreach (self::CONFIG_KEYS as $key) {
            if (in_array($key, self::MULTILANG_CONFIG_KEYS, true)) {
                continue;
            }

            $value = Configuration::get($key);
            if ($key === 'WB_GETRESPONSEPOPUP_DISCOUNT_CARRIERS') {
                $values[$key . '[]'] = json_decode((string) $value, true) ?: [];
            } elseif ($key === 'WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS') {
                $decoded = json_decode((string) $value, true) ?: [];
                $values[$key] = implode(',', $decoded);
            } elseif ($key === 'WB_GETRESPONSEPOPUP_DISCOUNT_CATEGORIES') {
                continue;
            } else {
                $values[$key] = $value;
            }
        }

        $languages = Language::getLanguages(false);
        foreach (self::MULTILANG_CONFIG_KEYS as $key) {
            foreach ($languages as $lang) {
                $idLang = (int) $lang['id_lang'];
                $values[$key][$idLang] = (string) Configuration::get($key, $idLang);
            }
        }

        return $values;
    }

    private function getFormFields(): array
    {
        return [
            $this->getPopupStatusFieldset(),
            $this->getGetResponseFieldset(),
            $this->getPopupAppearanceFieldset(),
            $this->getPopupContentFieldset(),
            $this->getFormFieldsFieldset(),
            $this->getScheduleFieldset(),
            $this->getTriggerFieldset(),
            $this->getDiscountFieldset(),
            $this->getCookieFieldset(),
        ];
    }

    private function getPopupStatusFieldset(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Popup Status', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-power-off',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Active', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_ACTIVE',
                        'is_bool' => true,
                        'desc' => $this->trans('Enable or disable the popup. The popup also requires a valid API key and campaign token to be displayed.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'values' => [
                            ['id' => 'active_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'active_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Developer Mode', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DEV_MODE',
                        'is_bool' => true,
                        'desc' => $this->trans('When enabled, the popup is only displayed when the URL contains the specified parameter (e.g., ?wbpopup=1). Useful for testing without showing the popup to all visitors.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'values' => [
                            ['id' => 'dev_mode_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'dev_mode_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Developer Mode URL Parameter', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DEV_PARAM',
                        'desc' => $this->trans('The URL parameter name that triggers the popup in developer mode. Example: with "wbpopup", add ?wbpopup=1 to any page URL.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'class' => 'fixed-width-lg',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getPopupAppearanceFieldset(): array
    {
        $inputs = [];

        $imageConfigs = [
            [
                'config_key' => 'WB_GETRESPONSEPOPUP_POPUP_IMAGE_DESKTOP',
                'delete_key' => 'WB_GETRESPONSEPOPUP_DELETE_IMAGE_DESKTOP',
                'file_key' => 'WB_GETRESPONSEPOPUP_IMAGE_UPLOAD_DESKTOP',
                'preview_name' => 'image_preview_desktop_html',
                'label' => $this->trans('Desktop Image', [], 'Modules.Wbgetresponsepopup.Admin'),
                'desc' => $this->trans(
                    'Image shown on desktop (screen wider than 768px). Recommended size: 344x573px. Allowed formats: JPG, PNG, GIF, WEBP. Max 2 MB.',
                    [],
                    'Modules.Wbgetresponsepopup.Admin'
                ),
            ],
            [
                'config_key' => 'WB_GETRESPONSEPOPUP_POPUP_IMAGE_MOBILE',
                'delete_key' => 'WB_GETRESPONSEPOPUP_DELETE_IMAGE_MOBILE',
                'file_key' => 'WB_GETRESPONSEPOPUP_IMAGE_UPLOAD_MOBILE',
                'preview_name' => 'image_preview_mobile_html',
                'label' => $this->trans('Mobile Image', [], 'Modules.Wbgetresponsepopup.Admin'),
                'desc' => $this->trans(
                    'Image shown on mobile (screen up to 768px). Recommended size: 600x300px (landscape). Allowed formats: JPG, PNG, GIF, WEBP. Max 2 MB.',
                    [],
                    'Modules.Wbgetresponsepopup.Admin'
                ),
            ],
        ];

        foreach ($imageConfigs as $cfg) {
            $currentImage = (string) Configuration::get($cfg['config_key']);

            if ($currentImage !== '') {
                $imagePath = _PS_MODULE_DIR_ . $this->name . '/uploads/' . $currentImage;
                if (file_exists($imagePath)) {
                    $imageUrl = $this->getPathUri() . 'uploads/' . $currentImage;
                    $inputs[] = [
                        'type' => 'html',
                        'name' => $cfg['preview_name'],
                        'html_content' => '<div style="margin-bottom:15px;">'
                            . '<p><strong>' . $this->trans('Current image:', [], 'Modules.Wbgetresponsepopup.Admin') . '</strong></p>'
                            . '<img src="' . htmlspecialchars($imageUrl) . '" style="max-width:200px;max-height:300px;border:1px solid #ddd;border-radius:4px;" alt="">'
                            . '<div style="margin-top:10px;">'
                            . '<label><input type="checkbox" name="' . $cfg['delete_key'] . '" value="1"> '
                            . $this->trans('Delete current image', [], 'Modules.Wbgetresponsepopup.Admin')
                            . '</label>'
                            . '</div>'
                            . '</div>',
                    ];
                }
            }

            $inputs[] = [
                'type' => 'file',
                'label' => $cfg['label'],
                'name' => $cfg['file_key'],
                'desc' => $cfg['desc'],
            ];
        }

        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Popup Appearance', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-picture-o',
                ],
                'description' => $this->trans(
                    'Upload separate images for desktop and mobile. Leave both empty to show a form-only popup.',
                    [],
                    'Modules.Wbgetresponsepopup.Admin'
                ),
                'input' => $inputs,
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getPopupContentFieldset(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Popup Content', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-pencil',
                ],
                'description' => $this->trans(
                    'Customize the texts displayed in the popup. All fields support multiple languages.',
                    [],
                    'Modules.Wbgetresponsepopup.Admin'
                ),
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Title', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_TITLE',
                        'lang' => true,
                        'desc' => $this->trans('Main heading of the popup.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Subtitle', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_SUBTITLE',
                        'lang' => true,
                        'desc' => $this->trans('Text displayed below the title, above the discount badge.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Submit Button Text', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_SUBMIT',
                        'lang' => true,
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Consent Text', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_CONSENT',
                        'lang' => true,
                        'desc' => $this->trans('Text next to the consent checkbox. HTML is allowed (e.g., for privacy policy links).', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Success Title', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_TITLE',
                        'lang' => true,
                        'desc' => $this->trans('Heading shown after successful subscription.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'textarea',
                        'label' => $this->trans('Success Message', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_SUCCESS_MSG',
                        'lang' => true,
                        'desc' => $this->trans('Message shown after successful subscription.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Discount Code Label', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_TEXT',
                        'lang' => true,
                        'desc' => $this->trans('Text shown above the discount code on the success screen.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Discount Code Hint', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_HINT',
                        'lang' => true,
                        'desc' => $this->trans('Text shown below the discount code on the success screen.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getGetResponseFieldset(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('GetResponse Connection', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-plug',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('API Key', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_API_KEY',
                        'desc' => $this->trans('Your GetResponse API key. Find it in GetResponse > Menu > Integrations & API > API.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'required' => true,
                    ],
                    [
                        'type' => 'html',
                        'name' => 'test_api_connection',
                        'html_content' => '<button type="button" id="wb-gr-test-api" class="btn btn-default"><i class="icon-refresh"></i> '
                            . $this->trans('Test API Connection', [], 'Modules.Wbgetresponsepopup.Admin')
                            . '</button> <span id="wb-gr-test-api-result"></span>',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Campaign Token', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN',
                        'desc' => $this->trans('The campaign list token (e.g., "PsgxP"), NOT the numeric ID. Find it in GetResponse > Lists > your list > Settings > Token.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'required' => true,
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Discount Code Custom Field Name', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_GR_DISCOUNT_FIELD_ID',
                        'desc' => $this->trans('GetResponse custom field name for the discount code (e.g., "discount_code"). Must match the exact field name in GetResponse > Contacts > Custom fields. Leave empty to skip sending discount code to GetResponse.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getFormFieldsFieldset(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Form Fields', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-list-alt',
                ],
                'description' => $this->trans('Email is always displayed. Select additional fields to show in the popup form.', [], 'Modules.Wbgetresponsepopup.Admin'),
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('First Name', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_FIELD_FIRSTNAME',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'firstname_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'firstname_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Last Name', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_FIELD_LASTNAME',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'lastname_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'lastname_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Date of Birth', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_FIELD_BIRTHDAY',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'birthday_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'birthday_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getScheduleFieldset(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Display Schedule', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-calendar',
                ],
                'description' => $this->trans('Optionally restrict the popup to a specific date range. Leave both fields empty to display it at all times (as long as the popup is active). If set, the popup will only appear within the specified date range.', [], 'Modules.Wbgetresponsepopup.Admin'),
                'input' => [
                    [
                        'type' => 'date',
                        'label' => $this->trans('Date From', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DATE_FROM',
                    ],
                    [
                        'type' => 'date',
                        'label' => $this->trans('Date To', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DATE_TO',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getTriggerFieldset(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Popup Trigger', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-bolt',
                ],
                'input' => [
                    [
                        'type' => 'select',
                        'label' => $this->trans('Trigger Type', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TRIGGER_TYPE',
                        'options' => [
                            'query' => [
                                ['id' => 'time', 'name' => $this->trans('After time delay (seconds)', [], 'Modules.Wbgetresponsepopup.Admin')],
                                ['id' => 'exit', 'name' => $this->trans('Exit intent (cursor leaves viewport)', [], 'Modules.Wbgetresponsepopup.Admin')],
                                ['id' => 'idle', 'name' => $this->trans('User inactivity (seconds)', [], 'Modules.Wbgetresponsepopup.Admin')],
                                ['id' => 'scroll', 'name' => $this->trans('Scroll percentage (%)', [], 'Modules.Wbgetresponsepopup.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Trigger Value', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_TRIGGER_VALUE',
                        'desc' => $this->trans('Seconds for time/inactivity triggers, percentage (0-100) for scroll trigger. Not used for exit intent.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'class' => 'fixed-width-sm',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getDiscountFieldset(): array
    {
        $selectedCategories = json_decode((string) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_CATEGORIES'), true) ?: [];

        $carriers = Carrier::getCarriers((int) $this->context->language->id, true, false, false, null, Carrier::ALL_CARRIERS);
        $carrierOptions = [];
        foreach ($carriers as $carrier) {
            $carrierOptions[] = [
                'id' => $carrier['id_carrier'],
                'name' => $carrier['name'],
            ];
        }

        $selectedProductIds = json_decode((string) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS'), true) ?: [];
        $selectedProductsHtml = '';
        if (!empty($selectedProductIds)) {
            $idLang = (int) $this->context->language->id;
            $idShop = (int) $this->context->shop->id;
            $idsString = implode(',', array_map('intval', $selectedProductIds));
            $products = Db::getInstance()->executeS(
                'SELECT p.`id_product`, pl.`name`, p.`reference`
                FROM `' . _DB_PREFIX_ . 'product` p
                LEFT JOIN `' . _DB_PREFIX_ . 'product_lang` pl
                    ON pl.`id_product` = p.`id_product` AND pl.`id_lang` = ' . $idLang . ' AND pl.`id_shop` = ' . $idShop . '
                WHERE p.`id_product` IN (' . $idsString . ')'
            );
            if ($products) {
                foreach ($products as $product) {
                    $ref = $product['reference'] ? ' (' . htmlspecialchars($product['reference']) . ')' : '';
                    $selectedProductsHtml .= '<div class="wb-gr-product-item" data-id="' . (int) $product['id_product'] . '">'
                        . '<span>' . htmlspecialchars($product['name']) . $ref . ' <small class="text-muted">#' . (int) $product['id_product'] . '</small></span>'
                        . ' <button type="button" class="btn btn-xs btn-danger wb-gr-product-remove">&times;</button>'
                        . '</div>';
                }
            }
        }

        $productAutocompleteHtml = '<div class="wb-gr-product-autocomplete-wrapper">'
            . '<input type="text" class="form-control" id="wb-gr-product-search" placeholder="' . $this->trans('Search by name, reference or EAN...', [], 'Modules.Wbgetresponsepopup.Admin') . '" autocomplete="off">'
            . '<div class="wb-gr-product-results" id="wb-gr-product-results"></div>'
            . '<input type="hidden" name="WB_GETRESPONSEPOPUP_DISCOUNT_PRODUCTS" id="wb-gr-product-ids" value="' . implode(',', $selectedProductIds) . '">'
            . '<div class="wb-gr-product-selected" id="wb-gr-product-selected">' . $selectedProductsHtml . '</div>'
            . '</div>';

        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Discount Code', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-ticket',
                ],
                'input' => [
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Enable Discount Code Generation', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_ENABLED',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'discount_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'discount_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Discount Type', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_TYPE',
                        'options' => [
                            'query' => [
                                ['id' => 'percentage', 'name' => $this->trans('Percentage (%)', [], 'Modules.Wbgetresponsepopup.Admin')],
                                ['id' => 'amount', 'name' => $this->trans('Fixed amount', [], 'Modules.Wbgetresponsepopup.Admin')],
                                ['id' => 'free_shipping', 'name' => $this->trans('Free shipping', [], 'Modules.Wbgetresponsepopup.Admin')],
                            ],
                            'id' => 'id',
                            'name' => 'name',
                        ],
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Discount Value', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_VALUE',
                        'desc' => $this->trans('Percentage or fixed amount value. Not used for free shipping.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Validity (days)', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_DAYS',
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Minimum Cart Value', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_MIN_CART',
                        'desc' => $this->trans('Set to 0 for no minimum.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'class' => 'fixed-width-sm',
                    ],
                    [
                        'type' => 'categories',
                        'label' => $this->trans('Limit to Categories', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'categoryBox',
                        'tree' => [
                            'id' => 'categoryBox',
                            'selected_categories' => $selectedCategories,
                            'use_checkbox' => true,
                            'root_category' => (int) Context::getContext()->shop->getCategory(),
                        ],
                        'desc' => $this->trans('Leave unselected for no category restriction.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'html',
                        'label' => $this->trans('Limit to Products', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'product_autocomplete_html',
                        'html_content' => $productAutocompleteHtml,
                        'desc' => $this->trans('Search and select products. Leave empty for no restriction.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'select',
                        'label' => $this->trans('Limit to Carriers', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_CARRIERS[]',
                        'multiple' => true,
                        'options' => [
                            'query' => $carrierOptions,
                            'id' => 'id',
                            'name' => 'name',
                        ],
                        'desc' => $this->trans('Leave empty for no carrier restriction. Hold Ctrl/Cmd to select multiple.', [], 'Modules.Wbgetresponsepopup.Admin'),
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Exclusive (no combination with other discounts)', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_EXCLUSIVE',
                        'is_bool' => true,
                        'values' => [
                            ['id' => 'exclusive_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'exclusive_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                    [
                        'type' => 'switch',
                        'label' => $this->trans('Show discount code in popup', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_DISCOUNT_SHOW_CODE',
                        'is_bool' => true,
                        'desc' => $this->trans('When disabled, the discount code is still generated and sent to GetResponse, but it will not be displayed in the popup confirmation screen.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'values' => [
                            ['id' => 'show_code_on', 'value' => 1, 'label' => $this->trans('Yes', [], 'Admin.Global')],
                            ['id' => 'show_code_off', 'value' => 0, 'label' => $this->trans('No', [], 'Admin.Global')],
                        ],
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    private function getCookieFieldset(): array
    {
        return [
            'form' => [
                'legend' => [
                    'title' => $this->trans('Cookie Settings', [], 'Modules.Wbgetresponsepopup.Admin'),
                    'icon' => 'icon-cog',
                ],
                'input' => [
                    [
                        'type' => 'text',
                        'label' => $this->trans('Popup Closed Cookie Duration (minutes)', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_COOKIE_CLOSED_MIN',
                        'desc' => $this->trans('How many minutes to hide the popup after the user closes it. E.g., 10080 = 7 days, 1440 = 1 day, 60 = 1 hour.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'class' => 'fixed-width-md',
                    ],
                    [
                        'type' => 'text',
                        'label' => $this->trans('Subscribed Cookie Duration (minutes)', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'name' => 'WB_GETRESPONSEPOPUP_COOKIE_SUBSCRIBED_MIN',
                        'desc' => $this->trans('How many minutes to hide the popup after a successful subscription. E.g., 43200 = 30 days, 20160 = 14 days.', [], 'Modules.Wbgetresponsepopup.Admin'),
                        'class' => 'fixed-width-md',
                    ],
                ],
                'submit' => [
                    'title' => $this->trans('Save', [], 'Admin.Actions'),
                ],
            ],
        ];
    }

    public function hookActionFrontControllerSetMedia(array $params): void
    {
        if (!$this->isPopupAllowed()) {
            return;
        }

        $this->context->controller->registerStylesheet(
            'module-wb-getresponsepopup-css',
            'modules/' . $this->name . '/views/css/front/popup.css',
            ['media' => 'all', 'priority' => 150]
        );

        $this->context->controller->registerJavascript(
            'module-wb-getresponsepopup-js',
            'modules/' . $this->name . '/views/js/front/popup.js',
            ['position' => 'bottom', 'priority' => 150]
        );
    }

    public function hookDisplayFooter(array $params): string
    {
        if (!$this->isPopupAllowed()) {
            return '';
        }

        if ($this->isCustomerSubscribed()) {
            return '';
        }

        $ajaxUrl = $this->context->link->getModuleLink($this->name, 'ajax', [], true);
        $idLang = (int) $this->context->language->id;

        $discountEnabled = (bool) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_ENABLED');
        $discountBadge = '';
        if ($discountEnabled) {
            $discountBadge = $this->buildDiscountBadge();
        }

        $imageDesktopUrl = $this->getUploadedImageUrl('WB_GETRESPONSEPOPUP_POPUP_IMAGE_DESKTOP');
        $imageMobileUrl = $this->getUploadedImageUrl('WB_GETRESPONSEPOPUP_POPUP_IMAGE_MOBILE');

        $this->smarty->assign([
            'wb_gr_ajax_url' => $ajaxUrl,
            'wb_gr_token' => Tools::getToken(false),
            'wb_gr_trigger_type' => Configuration::get('WB_GETRESPONSEPOPUP_TRIGGER_TYPE'),
            'wb_gr_trigger_value' => (int) Configuration::get('WB_GETRESPONSEPOPUP_TRIGGER_VALUE'),
            'wb_gr_field_firstname' => (bool) Configuration::get('WB_GETRESPONSEPOPUP_FIELD_FIRSTNAME'),
            'wb_gr_field_lastname' => (bool) Configuration::get('WB_GETRESPONSEPOPUP_FIELD_LASTNAME'),
            'wb_gr_field_birthday' => (bool) Configuration::get('WB_GETRESPONSEPOPUP_FIELD_BIRTHDAY'),
            'wb_gr_cookie_closed_min' => (int) Configuration::get('WB_GETRESPONSEPOPUP_COOKIE_CLOSED_MIN'),
            'wb_gr_cookie_subscribed_min' => (int) Configuration::get('WB_GETRESPONSEPOPUP_COOKIE_SUBSCRIBED_MIN'),
            'wb_gr_discount_enabled' => $discountEnabled,
            'wb_gr_discount_show_code' => (bool) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_SHOW_CODE'),
            'wb_gr_discount_badge' => $discountBadge,
            'wb_gr_popup_image_desktop' => $imageDesktopUrl,
            'wb_gr_popup_image_mobile' => $imageMobileUrl,
            'wb_gr_text_title' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_TITLE', $idLang),
            'wb_gr_text_subtitle' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_SUBTITLE', $idLang),
            'wb_gr_text_submit' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_SUBMIT', $idLang),
            'wb_gr_text_consent' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_CONSENT', $idLang),
            'wb_gr_text_success_title' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_SUCCESS_TITLE', $idLang),
            'wb_gr_text_success_msg' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_SUCCESS_MSG', $idLang),
            'wb_gr_text_discount_text' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_TEXT', $idLang),
            'wb_gr_text_discount_hint' => (string) Configuration::get('WB_GETRESPONSEPOPUP_TEXT_DISCOUNT_HINT', $idLang),
        ]);

        return $this->fetch('module:wb_getresponsepopup/views/templates/hook/popup.tpl');
    }

    private function getUploadedImageUrl(string $configKey): string
    {
        $imageFile = (string) Configuration::get($configKey);
        if ($imageFile === '') {
            return '';
        }

        $imagePath = _PS_MODULE_DIR_ . $this->name . '/uploads/' . $imageFile;
        if (!file_exists($imagePath)) {
            return '';
        }

        return $this->getPathUri() . 'uploads/' . $imageFile;
    }

    private function isPopupAllowed(): bool
    {
        if (!(bool) Configuration::get('WB_GETRESPONSEPOPUP_ACTIVE')) {
            return false;
        }

        $apiKey = (string) Configuration::get('WB_GETRESPONSEPOPUP_API_KEY');
        $campaignToken = (string) Configuration::get('WB_GETRESPONSEPOPUP_CAMPAIGN_TOKEN');
        if ($apiKey === '' || $campaignToken === '') {
            return false;
        }

        if ((bool) Configuration::get('WB_GETRESPONSEPOPUP_DEV_MODE')) {
            $devParam = (string) Configuration::get('WB_GETRESPONSEPOPUP_DEV_PARAM') ?: 'wbpopup';
            if (!Tools::getValue($devParam)) {
                return false;
            }
        }

        $dateFrom = Configuration::get('WB_GETRESPONSEPOPUP_DATE_FROM');
        $dateTo = Configuration::get('WB_GETRESPONSEPOPUP_DATE_TO');

        if (!empty($dateFrom) || !empty($dateTo)) {
            $now = time();

            if (!empty($dateFrom) && $now < strtotime($dateFrom . ' 00:00:00')) {
                return false;
            }

            if (!empty($dateTo) && $now > strtotime($dateTo . ' 23:59:59')) {
                return false;
            }
        }

        return true;
    }

    private function buildDiscountBadge(): string
    {
        $type = (string) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_TYPE');
        $value = (float) Configuration::get('WB_GETRESPONSEPOPUP_DISCOUNT_VALUE');

        switch ($type) {
            case 'percentage':
                return $this->trans(
                    '%value%% discount on the entire cart!',
                    ['%value%' => (int) $value],
                    'Modules.Wbgetresponsepopup.Shop'
                );
            case 'amount':
                $currency = $this->context->currency;
                $formatted = Tools::displayPrice($value, $currency);

                return $this->trans(
                    '%value% discount on the entire cart!',
                    ['%value%' => $formatted],
                    'Modules.Wbgetresponsepopup.Shop'
                );
            case 'free_shipping':
                return $this->trans(
                    'Free shipping on the entire cart!',
                    [],
                    'Modules.Wbgetresponsepopup.Shop'
                );
            default:
                return '';
        }
    }

    private function isCustomerSubscribed(): bool
    {
        $customer = $this->context->customer;

        if (!$customer || !$customer->isLogged()) {
            return false;
        }

        $idShop = (int) $this->context->shop->id;
        $email = pSQL($customer->email);

        $result = Db::getInstance()->getValue(
            'SELECT COUNT(*) FROM `' . _DB_PREFIX_ . 'wb_getresponse_subscriber`
            WHERE `email` = \'' . $email . '\' AND `id_shop` = ' . $idShop
        );

        return (int) $result > 0;
    }
}
