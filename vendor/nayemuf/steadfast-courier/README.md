# Laravel SteadFast Courier Package

[![Latest Version on Packagist](https://img.shields.io/packagist/v/nayemuf/steadfast-courier.svg?style=flat-square)](https://packagist.org/packages/nayemuf/steadfast-courier)
[![Total Downloads](https://img.shields.io/packagist/dt/nayemuf/steadfast-courier.svg?style=flat-square)](https://packagist.org/packages/nayemuf/steadfast-courier)
[![License](https://img.shields.io/packagist/l/nayemuf/steadfast-courier.svg?style=flat-square)](https://packagist.org/packages/nayemuf/steadfast-courier)
[![Laravel](https://img.shields.io/badge/Laravel-10.x%20%7C%2011.x%20%7C%2012.x-orange.svg?style=flat-square)](https://laravel.com)
[![PHP Version](https://img.shields.io/packagist/php-v/nayemuf/steadfast-courier.svg?style=flat-square)](https://packagist.org/packages/nayemuf/steadfast-courier)

A professional Laravel package for integrating with **SteadFast Courier API**. This package provides a clean, well-structured interface for all SteadFast API endpoints with built-in caching, rate limiting, and comprehensive error handling.

## ✨ Features

- ✅ **Complete API Coverage** - All SteadFast Courier API endpoints implemented
- ✅ **API Key Authentication** - Secure authentication with API Key and Secret Key
- ✅ **Rate Limiting** - Built-in protection against API abuse (configurable)
- ✅ **Input Validation** - Comprehensive validation before API calls
- ✅ **Error Handling** - Detailed exception messages with field-level errors
- ✅ **Laravel Best Practices** - Service Provider, Facades, and publishable config
- ✅ **Type Safety** - Complete type hints and PHPDoc documentation
- ✅ **Zero Configuration** - Works out of the box with sensible defaults
- ✅ **Production Ready** - Battle-tested in production environments

## 📋 Requirements

- PHP >= 8.2
- Laravel >= 10.0
- Guzzle HTTP Client >= 7.0

## 📦 Installation

Install the package via Composer:

```bash
composer require nayemuf/steadfast-courier
```

The package will automatically register its service provider and facade.

## ⚙️ Configuration

### Step 1: Publish Configuration

Publish the configuration file to your `config` directory:

```bash
php artisan vendor:publish --tag=steadfast-config
```

This will create `config/steadfast.php` in your Laravel application.

### Step 2: Environment Variables

Add the following to your `.env` file:

```env
STEADFAST_BASE_URL=https://portal.packzy.com/api/v1
STEADFAST_API_KEY=your-api-key
STEADFAST_SECRET_KEY=your-secret-key

# Optional: Webhook Bearer Token
STEADFAST_BEARER_TOKEN=your-generated-bearer-token

# Optional: Rate Limiting
STEADFAST_RATE_LIMIT_ENABLED=true
STEADFAST_RATE_LIMIT_PER_MINUTE=60
```

**Note:** You can obtain your API credentials from the [SteadFast Courier Portal](https://steadfast.com.bd/).

## 🚀 Usage

### Placing an Order

```php
use Nayemuf\SteadfastCourier\Facades\SteadfastCourier;

$orderData = [
    'invoice' => 'ORD-123456',
    'recipient_name' => 'John Doe',
    'recipient_phone' => '01712345678',
    'recipient_address' => 'House 44, Road 2/A, Dhanmondi, Dhaka 1209',
    'cod_amount' => 1000.00,
    'note' => 'Handle with care',
    'recipient_email' => 'john@example.com', // Optional
    'alternative_phone' => '01812345678', // Optional
    'item_description' => 'Product description', // Optional
    'total_lot' => 1, // Optional
    'delivery_type' => 0, // Optional: 0 = home delivery, 1 = hub pickup
];

try {
    $response = SteadfastCourier::order()->placeOrder($orderData);
    
    // Access response data
    $consignmentId = $response['consignment']['consignment_id'];
    $trackingCode = $response['consignment']['tracking_code'];
    
    echo "Order created! Consignment ID: {$consignmentId}, Tracking: {$trackingCode}";
} catch (\Nayemuf\SteadfastCourier\Exceptions\SteadfastException $e) {
    // Handle error
    logger()->error('SteadFast order creation failed', [
        'message' => $e->getMessage(),
        'errors' => $e->getErrors(),
    ]);
}
```

**Response:**

```json
{
    "status": 200,
    "message": "Consignment has been created successfully.",
    "consignment": {
        "consignment_id": 1424107,
        "invoice": "ORD-123456",
        "tracking_code": "15BAEB8A",
        "recipient_name": "John Doe",
        "recipient_phone": "01712345678",
        "recipient_address": "House 44, Road 2/A, Dhanmondi, Dhaka 1209",
        "cod_amount": 1000.00,
        "status": "in_review",
        "created_at": "2021-03-21T07:05:31.000000Z"
    }
}
```

### Bulk Order Creation

Create up to 500 orders in a single request:

```php
$orders = [
    [
        'invoice' => 'ORD-001',
        'recipient_name' => 'John Doe',
        'recipient_phone' => '01712345678',
        'recipient_address' => 'House 44, Road 2/A, Dhanmondi, Dhaka 1209',
        'cod_amount' => 1000.00,
    ],
    [
        'invoice' => 'ORD-002',
        'recipient_name' => 'Jane Smith',
        'recipient_phone' => '01812345678',
        'recipient_address' => 'House 55, Road 3/B, Gulshan, Dhaka 1212',
        'cod_amount' => 1500.00,
    ],
];

$response = SteadfastCourier::order()->placeBulkOrders($orders);

// Response contains array of results for each order
foreach ($response as $result) {
    if ($result['status'] === 'success') {
        echo "Order {$result['invoice']} created: {$result['tracking_code']}\n";
    } else {
        echo "Order {$result['invoice']} failed\n";
    }
}
```

### Checking Delivery Status

```php
// By Consignment ID
$status = SteadfastCourier::status()->getStatusByConsignmentId(1424107);

// By Invoice ID
$status = SteadfastCourier::status()->getStatusByInvoice('ORD-123456');

// By Tracking Code
$status = SteadfastCourier::status()->getStatusByTrackingCode('15BAEB8A');

// Response:
// {
//     "status": 200,
//     "delivery_status": "in_review"
// }
```

### Checking Current Balance

```php
$balance = SteadfastCourier::balance()->getCurrentBalance();

// Response:
// {
//     "status": 200,
//     "current_balance": 5000.00
// }
```

### Return Requests

```php
// Create return request
$returnRequest = SteadfastCourier::return()->createReturnRequest([
    'consignment_id' => 1424107, // or 'invoice' => 'ORD-123456' or 'tracking_code' => '15BAEB8A'
    'reason' => 'Customer requested return', // Optional
]);

// Get single return request
$return = SteadfastCourier::return()->getReturnRequest(1);

// Get all return requests
$returns = SteadfastCourier::return()->getReturnRequests();
```

### Payments

```php
// Get payments list
$payments = SteadfastCourier::payment()->getPayments();

// Get single payment with consignments
$payment = SteadfastCourier::payment()->getPayment(123);
```

### Police Stations

```php
$policeStations = SteadfastCourier::policeStation()->getPoliceStations();
```

## 📚 Complete API Reference

### Order API

#### `placeOrder(array $orderData): array`

Place a single order with SteadFast Courier.

**Required Parameters:**

- `invoice` (string): Unique invoice ID (alphanumeric with hyphens/underscores)
- `recipient_name` (string): Recipient name (max 100 characters)
- `recipient_phone` (string): Recipient phone (exactly 11 digits)
- `recipient_address` (string): Recipient address (max 250 characters)
- `cod_amount` (float): Cash on delivery amount (>= 0)

**Optional Parameters:**

- `alternative_phone` (string): Alternative phone (11 digits)
- `recipient_email` (string): Recipient email
- `note` (string): Delivery instructions
- `item_description` (string): Item description
- `total_lot` (int): Total lot of items
- `delivery_type` (int): 0 = home delivery, 1 = hub pickup

#### `placeBulkOrders(array $orders): array`

Place multiple orders (max 500) in a single request. Each order follows the same structure as `placeOrder()`.

### Status API

#### `getStatusByConsignmentId(int $consignmentId): array`

Get delivery status by consignment ID.

#### `getStatusByInvoice(string $invoice): array`

Get delivery status by invoice ID.

#### `getStatusByTrackingCode(string $trackingCode): array`

Get delivery status by tracking code.

### Balance API

#### `getCurrentBalance(): array`

Get current account balance.

### Return API

#### `createReturnRequest(array $data): array`

Create a return request. Requires one of: `consignment_id`, `invoice`, or `tracking_code`.

#### `getReturnRequest(int $id): array`

Get single return request by ID.

#### `getReturnRequests(): array`

Get all return requests.

### Payment API

#### `getPayments(): array`

Get payments list.

#### `getPayment(int $paymentId): array`

Get single payment with consignments.

### Police Station API

#### `getPoliceStations(): array`

Get police stations list.

## 🔒 Error Handling

The package throws `SteadfastException` for API errors with detailed information:

```php
use Nayemuf\SteadfastCourier\Exceptions\SteadfastException;

try {
    $response = SteadfastCourier::order()->placeOrder($orderData);
} catch (SteadfastException $e) {
    // Get error message
    $message = $e->getMessage();
    
    // Get field-level errors (if available)
    $errors = $e->getErrors();
    
    // Get HTTP status code
    $code = $e->getCode();
    
    // Log or handle error
    logger()->error('SteadFast API Error', [
        'message' => $message,
        'errors' => $errors,
        'code' => $code,
    ]);
}
```

## ⚡ Caching & Rate Limiting

The package includes built-in caching and rate limiting to optimize API usage:

### Rate Limiting

- **Default**: 60 requests per minute
- **Configurable**: Set `STEADFAST_RATE_LIMIT_PER_MINUTE` in your `.env`
- **Automatic**: Prevents exceeding API limits

### Caching

API responses can be cached to reduce API calls. Configure in `config/steadfast.php`:

```php
'cache' => [
    'prefix' => 'steadfast_courier_',
    'token_ttl' => 432000, // 5 days
],
```

## ✅ Validation Rules

The package validates all data before sending to the API to prevent errors:

| Field | Rules |
|-------|-------|
| `invoice` | Required, alphanumeric with hyphens/underscores only |
| `recipient_name` | Required, max 100 characters |
| `recipient_phone` | Required, exactly 11 digits |
| `recipient_address` | Required, max 250 characters |
| `cod_amount` | Required, numeric, >= 0 |
| `alternative_phone` | Optional, exactly 11 digits if provided |
| `recipient_email` | Optional, valid email format |
| `note` | Optional |
| `item_description` | Optional |
| `total_lot` | Optional, numeric |
| `delivery_type` | Optional, 0 or 1 |

## 📝 Delivery Statuses

Possible delivery statuses returned by the API:

| Status | Description |
|--------|-------------|
| `pending` | Not yet delivered or cancelled |
| `delivered_approval_pending` | Delivered, awaiting admin approval |
| `partial_delivered_approval_pending` | Partially delivered, awaiting approval |
| `cancelled_approval_pending` | Cancelled, awaiting approval |
| `unknown_approval_pending` | Unknown state, needs support intervention |
| `delivered` | Delivered and balance updated |
| `partial_delivered` | Partially delivered and balance updated |
| `cancelled` | Cancelled and balance updated |
| `hold` | On hold |
| `in_review` | Order placed, under review |
| `unknown` | Unknown status, need contact with support team |

## 🔧 Advanced Usage

### Using Without Facade

You can also use the package without the facade:

```php
use Nayemuf\SteadfastCourier\SteadfastCourier;

$courier = new SteadfastCourier(
    config('steadfast.api_key'),
    config('steadfast.secret_key'),
    config('steadfast.base_url')
);

$response = $courier->order()->placeOrder($orderData);
```

### Custom Base URL

You can override the base URL for custom endpoints:

```php
$courier = new SteadfastCourier(
    'your-api-key',
    'your-secret-key',
    'https://custom-url.com/api/v1'
);
```

## 🧪 Testing

The package is fully tested and ready for production use. For testing in your application:

```php
// In your tests
use Nayemuf\SteadfastCourier\Facades\SteadfastCourier;

// Mock the facade if needed
SteadfastCourier::shouldReceive('order->placeOrder')
    ->andReturn(['status' => 200, 'consignment' => [...]]);
```

## 🤝 Contributing

Contributions are welcome! Please follow these guidelines:

1. **Follow PSR-4** autoloading standards
2. **Adhere to SteadFast's official API documentation** - Always refer to the latest API docs
3. **Write clear, documented code** - Add PHPDoc comments
4. **Add tests** for new features
5. **Update README and CHANGELOG** when adding features
6. **Follow Laravel conventions** - Use Laravel's coding standards

### Development Setup

```bash
# Clone the repository
git clone https://github.com/nayemuf/steadfast-courier.git
cd steadfast-courier

# Install dependencies
composer install

# Run tests
composer test
```

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

## 👤 Author

### Nayem Uddin

- Email: [nayem110899@gmail.com](mailto:nayem110899@gmail.com)
- LinkedIn: [Connect with me](https://www.linkedin.com/in/nayemuf/)

## 🙏 Acknowledgments

- [SteadFast Courier Limited](https://steadfast.com.bd/) for providing the API
- Laravel community for the amazing framework
- All contributors who help improve this package

## 📚 Additional Resources

- [SteadFast Courier Official Website](https://steadfast.com.bd/)
- [SteadFast API Documentation](https://portal.packzy.com/api/v1)
- [Laravel Documentation](https://laravel.com/docs)

## 🐛 Reporting Issues

If you encounter any issues or have suggestions, please open an issue on [GitHub](https://github.com/nayemuf/steadfast-courier/issues).

## ⭐ Support

If this package helps you, please consider giving it a ⭐ on [Packagist](https://packagist.org/packages/nayemuf/steadfast-courier) or [GitHub](https://github.com/nayemuf/steadfast-courier).

## 💬 Issues

For issues, questions, or feature requests, please open an issue on [GitHub](https://github.com/nayemuf/pathao-courier/issues).
---