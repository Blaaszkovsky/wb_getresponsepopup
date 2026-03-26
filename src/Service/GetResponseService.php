<?php
declare(strict_types=1);

namespace PrestaShop\Module\WbGetresponsePopup\Service;

use Configuration;

class GetResponseService
{
    private const API_BASE_URL = 'https://api.getresponse.com/v3';

    private array $fieldNameToIdCache = [];

    public function addContact(
        string $email,
        string $campaignToken,
        ?string $name = null,
        array $customFields = []
    ): array {
        $apiKey = Configuration::get('WB_GETRESPONSEPOPUP_API_KEY');

        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API key is not configured'];
        }

        // Resolve custom field names to API IDs before sending
        $resolvedFields = $this->resolveCustomFieldIds($customFields, $apiKey);

        $data = [
            'email' => $email,
            'campaign' => [
                'campaignId' => $campaignToken,
            ],
            'dayOfCycle' => 0,
        ];

        if ($name !== null && $name !== '') {
            $data['name'] = $name;
        }

        if (!empty($resolvedFields)) {
            $data['customFieldValues'] = $resolvedFields;
        }

        $result = $this->apiRequest('POST', '/contacts', $data, $apiKey);

        if ($result['httpCode'] >= 200 && $result['httpCode'] < 300) {
            return ['success' => true, 'httpCode' => $result['httpCode']];
        }

        if ($result['httpCode'] === 409) {
            return ['success' => true, 'httpCode' => $result['httpCode'], 'alreadyExists' => true];
        }

        if ($result['error'] !== null) {
            return ['success' => false, 'error' => $result['error'], 'httpCode' => 0];
        }

        $responseData = json_decode((string) $result['body'], true);
        $errorMessage = $responseData['message'] ?? 'Unknown error';

        return ['success' => false, 'error' => $errorMessage, 'httpCode' => $result['httpCode']];
    }

    /**
     * Resolves human-readable field names to GetResponse API customFieldIds.
     * Input: [['customFieldId' => 'discount_code', 'value' => ['GR-ABC']]]
     * Output: [['customFieldId' => 'realHashFromApi', 'value' => ['GR-ABC']]]
     * Fields that cannot be resolved are silently skipped.
     */
    private function resolveCustomFieldIds(array $fields, string $apiKey): array
    {
        $resolved = [];

        foreach ($fields as $field) {
            $fieldName = $field['customFieldId'] ?? '';
            $value = $field['value'] ?? [];

            if (empty($fieldName) || empty($value)) {
                continue;
            }

            $realId = $this->lookupCustomFieldId($fieldName, $apiKey);

            if ($realId !== null) {
                $resolved[] = [
                    'customFieldId' => $realId,
                    'value' => $value,
                ];
            }
        }

        return $resolved;
    }

    /**
     * Looks up the real API customFieldId by field name via GET /custom-fields.
     * Cached per request to avoid duplicate API calls.
     */
    private function lookupCustomFieldId(string $fieldName, string $apiKey): ?string
    {
        if (isset($this->fieldNameToIdCache[$fieldName])) {
            return $this->fieldNameToIdCache[$fieldName];
        }

        $query = http_build_query(['query' => ['name' => $fieldName]]);
        $result = $this->apiRequest('GET', '/custom-fields?' . $query, null, $apiKey);

        if ($result['httpCode'] === 200) {
            $fields = json_decode((string) $result['body'], true);
            if (!empty($fields) && is_array($fields)) {
                foreach ($fields as $f) {
                    if (($f['name'] ?? '') === $fieldName && !empty($f['customFieldId'])) {
                        $this->fieldNameToIdCache[$fieldName] = $f['customFieldId'];

                        return $f['customFieldId'];
                    }
                }
            }
        }

        return null;
    }

    private function apiRequest(string $method, string $endpoint, ?array $data, string $apiKey): array
    {
        $url = self::API_BASE_URL . $endpoint;

        $ch = curl_init($url);
        $headers = [
            'Content-Type: application/json',
            'X-Auth-Token: api-key ' . $apiKey,
        ];

        $options = [
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
        ];

        if ($method === 'POST') {
            $options[CURLOPT_POST] = true;
            if ($data !== null) {
                $options[CURLOPT_POSTFIELDS] = json_encode($data);
            }
        } elseif ($method === 'GET') {
            $options[CURLOPT_HTTPGET] = true;
        }

        curl_setopt_array($ch, $options);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        return [
            'body' => $response !== false ? (string) $response : '',
            'httpCode' => $httpCode,
            'error' => $curlError !== '' ? 'Connection error: ' . $curlError : null,
        ];
    }
}
