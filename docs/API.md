# Newsletter Signup API — (WB) GetResponse Newsletter Popup

REST endpoint for subscribing a contact to GetResponse and (optionally) generating
a PrestaShop discount code in a single request. It reuses the same logic as the
on-site popup: the GetResponse API key, campaign, custom fields, and discount rules
are all taken from the module configuration.

- **Module:** `wb_getresponsepopup`
- **Author:** WBShop.pl
- **Version:** 1.0.0

---

## Base URL

```
https://<your-shop-domain>/index.php?fc=module&module=wb_getresponsepopup&controller=api
```

The exact URL for your installation is shown in the module configuration
(**GetResponse Connection** section → *Endpoint URL*). On shops with friendly
URLs the same controller may also be reachable at
`https://<your-shop-domain>/module/wb_getresponsepopup/api`.

---

## Authentication

Every request must include a secret key in the HTTP header:

```
X-Api-Key: <endpoint key>
```

The key is configured in the Back Office (**Modules → (WB) GetResponse Newsletter
Popup → GetResponse Connection → Endpoint API Key**). Use the **Generate Key**
button to create a strong key, then **Save**.

- Requests without a valid key are rejected with `401 Unauthorized`.
- If no key is configured, the endpoint is disabled and returns `503 Service Unavailable`.
- Keep the key secret. Regenerating and saving a new key immediately invalidates the old one.

---

## Request

| | |
|---|---|
| **Method** | `POST` |
| **Content-Type** | `application/json` |

### Body fields

| Field | Type | Required | Description |
|------------|--------|----------|-------------|
| `email` | string | **yes** | Subscriber email address. Must be a valid email. |
| `name` | string | no | Full name of the subscriber. Sent to GetResponse as the contact name. |
| `campaignId` | string | no | GetResponse campaign (list) token. When empty, the campaign token configured in the module is used. |

> `campaignId` is the **campaign list token** (e.g. `PsgxP`), not the numeric ID.

### Example request

```bash
curl -X POST \
  "https://your-shop.com/index.php?fc=module&module=wb_getresponsepopup&controller=api" \
  -H "X-Api-Key: YOUR_ENDPOINT_KEY" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "john@example.com",
    "name": "John Doe",
    "campaignId": "PsgxP"
  }'
```

---

## Responses

All responses are JSON with the header `Content-Type: application/json; charset=utf-8`.
The outcome is signalled by the **HTTP status code** and the `success` boolean.

### Success — `201 Created`

A new contact was subscribed. If discount generation is enabled in the module, a
discount code is created and returned; otherwise `discount` is `null`.

```json
{
  "success": true,
  "message": "Subscribed successfully.",
  "discount": {
    "code": "GR-AB12CD34",
    "type": "percentage",
    "value": 10,
    "currency": "PLN",
    "minimum_cart": 0,
    "expires_at": "2026-07-09 12:00:00"
  }
}
```

#### `discount` object

| Field | Type | Description |
|----------------|----------------|-------------|
| `code` | string | The discount/voucher code to use at checkout. |
| `type` | string | `percentage`, `amount`, or `free_shipping`. |
| `value` | number | Percentage or fixed amount. Not meaningful for `free_shipping`. |
| `currency` | string | ISO code of the shop default currency (relevant for `amount`). |
| `minimum_cart` | number | Minimum cart value required to use the code (`0` = no minimum). |
| `expires_at` | string\|null | Expiry date-time (`YYYY-MM-DD HH:MM:SS`). |

When discount generation is disabled in the module, the response is:

```json
{
  "success": true,
  "message": "Subscribed successfully.",
  "discount": null
}
```

### Error responses

All errors share the same shape:

```json
{
  "success": false,
  "error": "<machine_code>",
  "message": "<human readable description>"
}
```

| HTTP status | `error` | Meaning |
|-------------|----------------------|---------|
| `400 Bad Request` | `invalid_request` | Missing/invalid `email`, invalid `name`, or malformed JSON body. |
| `401 Unauthorized` | `unauthorized` | Missing or incorrect `X-Api-Key`. |
| `405 Method Not Allowed` | `method_not_allowed` | The request method is not `POST`. |
| `409 Conflict` | `already_exists` | The email is already subscribed (locally or in GetResponse). |
| `500 Internal Server Error` | `server_error` | No campaign configured, or an unexpected error occurred. |
| `502 Bad Gateway` | `upstream_error` | GetResponse could not be reached or returned an error. |
| `503 Service Unavailable` | `endpoint_disabled` | The endpoint key is not configured. |

#### Example — already subscribed (`409`)

```json
{
  "success": false,
  "error": "already_exists",
  "message": "This email address is already subscribed."
}
```

#### Example — unauthorized (`401`)

```json
{
  "success": false,
  "error": "unauthorized",
  "message": "Invalid or missing API key."
}
```

---

## Behaviour notes

- **Discount policy.** A code is generated only when *Discount Code Generation* is
  enabled in the module. The same rules as the popup apply (type, value, validity,
  minimum cart, category/product/carrier restrictions, exclusivity).
- **Atomicity.** If the GetResponse call fails or reports a duplicate after a code
  was created, the generated discount (CartRule) is removed automatically.
- **Duplicate detection.** The endpoint checks the module's local subscriber records
  and honours GetResponse's duplicate response. A repeated call for an existing email
  returns `409` and does not create a new code.
- **Discount code in GetResponse.** If a *Discount Code Custom Field Name* is set in
  the module, the generated code is also stored on the GetResponse contact.
- **Last call timestamp.** The date and time of the last authenticated call is shown
  in the module configuration (*Last endpoint call*).
- **Multistore.** Subscription and discount are scoped to the shop resolved from the
  request context.

---

## Integration examples

### JavaScript (fetch)

```js
const res = await fetch(
  "https://your-shop.com/index.php?fc=module&module=wb_getresponsepopup&controller=api",
  {
    method: "POST",
    headers: {
      "X-Api-Key": "YOUR_ENDPOINT_KEY",
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ email: "john@example.com", name: "John Doe" }),
  }
);

const data = await res.json();
if (res.status === 201) {
  console.log("Discount code:", data.discount?.code ?? "(none)");
} else {
  console.warn(data.error, data.message);
}
```

### PHP (cURL)

```php
$ch = curl_init('https://your-shop.com/index.php?fc=module&module=wb_getresponsepopup&controller=api');
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'X-Api-Key: YOUR_ENDPOINT_KEY',
        'Content-Type: application/json',
    ],
    CURLOPT_POSTFIELDS => json_encode([
        'email' => 'john@example.com',
        'name' => 'John Doe',
    ]),
]);

$body = curl_exec($ch);
$status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

$data = json_decode($body, true);
// $status === 201 => success, $data['discount']['code'] holds the code
```
