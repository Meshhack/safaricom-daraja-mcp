# Safaricom Daraja MCP - PHP

A comprehensive Model Context Protocol (MCP) server implementation for the Safaricom Daraja API in modern PHP 8+ with full OOP architecture.

**Developed by:** Meshack Musyoka

## 🚀 Features

- **Modern PHP 8+** with readonly properties, enums, and match expressions
- **Full OOP Architecture** with proper separation of concerns
- **Comprehensive Error Handling** with custom exception classes
- **Automatic Token Management** with expiration handling
- **Input Validation** with strict typing and validation
- **Environment Configuration** with dotenv support
- **Structured Logging** with Monolog integration
- **Production Ready** with proper error handling and logging

## 📦 Installation

```bash
# Clone the repository
git clone https://github.com/meshackmusyoka/safaricom-daraja-mcp.git
cd safaricom-daraja-mcp/php

# Install dependencies
composer install

# Configure environment
cp .env.example .env
# Edit .env with your Daraja credentials

# Run the MCP server
./bin/mcp-daraja

# Or using PHP directly
php bin/mcp-daraja
```

## ⚙️ Configuration

Create a `.env` file with your Daraja API credentials:

```env
DARAJA_CONSUMER_KEY=your_consumer_key
DARAJA_CONSUMER_SECRET=your_consumer_secret
DARAJA_BUSINESS_SHORT_CODE=your_shortcode
DARAJA_PASS_KEY=your_pass_key
DARAJA_ENVIRONMENT=sandbox
DARAJA_INITIATOR_NAME=your_initiator_name
DARAJA_INITIATOR_PASSWORD=your_initiator_password
```

## 🛠 MCP Tools

### Authentication
- `daraja_generate_token` - Generate OAuth access tokens

### Payment Operations
- `daraja_stk_push` - Initiate M-Pesa Express payments
- `daraja_stk_query` - Query STK Push status
- `daraja_c2b_register` - Register C2B URLs
- `daraja_c2b_simulate` - Simulate C2B payments (sandbox)
- `daraja_b2c_payment` - Business to Customer payments
- `daraja_b2b_payment` - Business to Business transfers

### Utility Operations
- `daraja_account_balance` - Query account balance
- `daraja_transaction_status` - Query transaction status
- `daraja_reversal` - Reverse transactions
- `daraja_generate_qr` - Generate payment QR codes

## 📝 Usage Examples

### STK Push Payment

```php
// Tool call to daraja_stk_push
[
    "amount" => 100,
    "phone_number" => "254708374149",
    "callback_url" => "https://yourdomain.com/callback",
    "account_reference" => "TEST123",
    "transaction_desc" => "Payment Test"
]
```

### Query Payment Status

```php
// Tool call to daraja_stk_query
[
    "checkout_request_id" => "ws_CO_191220231234567890"
]
```

### B2C Payment

```php
// Tool call to daraja_b2c_payment
[
    "amount" => 500,
    "party_b" => "254708374149",
    "command_id" => "BusinessPayment",
    "remarks" => "Salary payment",
    "queue_timeout_url" => "https://yourdomain.com/timeout",
    "result_url" => "https://yourdomain.com/result"
]
```

## 🏗 Development

### Project Structure

```
php/
├── src/
│   ├── Types/
│   │   ├── DarajaConfig.php     # Configuration class
│   │   ├── ApiEndpoints.php     # API endpoint management
│   │   ├── Requests.php         # Request DTOs
│   │   └── Responses.php        # Response DTOs
│   ├── Client/
│   │   └── DarajaClient.php     # Daraja API client
│   ├── Server/
│   │   └── McpServer.php        # MCP server implementation
│   └── Exceptions/
│       └── DarajaException.php  # Custom exception class
├── bin/
│   └── mcp-daraja              # Executable script
├── composer.json               # Dependencies and configuration
├── .env.example               # Environment template
└── README.md                  # This file
```

### Dependencies

- **guzzlehttp/guzzle**: HTTP client for API requests
- **vlucas/phpdotenv**: Environment variable management
- **monolog/monolog**: Structured logging
- **react/socket**: Async socket support
- **react/stream**: Stream handling

### Code Features

#### Modern PHP 8+ Features
```php
// Readonly properties
class DarajaConfig
{
    public function __construct(
        public readonly string $consumerKey,
        public readonly string $consumerSecret,
        public readonly string $businessShortCode,
        // ...
    ) {
    }
}

// Match expressions
$statusEmoji = match($result->ResultCode) {
    '0' => '✅',
    '1032' => '❌',
    '1037' => '⏳',
    default => '❓'
};
```

#### Error Handling
```php
try {
    $result = $client->stkPush(...);
} catch (DarajaException $e) {
    $logger->error('API Error', [
        'code' => $e->getDarajaCode(),
        'status' => $e->getHttpStatus(),
        'response' => $e->getResponse()
    ]);
}
```

#### Type Safety
```php
class STKPushRequest
{
    public function __construct(
        public readonly string $BusinessShortCode,
        public readonly int $Amount,
        // ... other typed properties
    ) {
        if ($Amount <= 0) {
            throw new InvalidArgumentException('Amount must be positive');
        }
    }
}
```

## 🔐 Security

- **Environment Variables**: All sensitive credentials stored securely
- **Token Management**: Automatic token refresh with expiration checking
- **Input Validation**: Strict validation with custom exception handling
- **Error Handling**: Secure error messages without credential exposure
- **HTTPS Required**: All callback URLs must use HTTPS
- **Phone Number Normalization**: Automatic format normalization and validation

## 🌐 Environment Support

### Sandbox
- **Base URL**: `https://sandbox.safaricom.co.ke`
- **Test Credentials**: Available in `.env.example`
- **Test Phone Numbers**: 254708374149, 254721234567

### Production
- **Base URL**: `https://api.safaricom.co.ke`
- **Go Live**: Requires Safaricom approval
- **Security**: Enhanced security credentials required

## 📊 Error Handling

The server provides comprehensive error handling with:

- **Validation Errors**: Strict input validation with detailed messages
- **API Errors**: Proper error codes and descriptions from Daraja
- **Network Errors**: Timeout and connectivity handling
- **Authentication Errors**: Token generation and refresh issues
- **Custom Exceptions**: Typed exceptions with context information

## 🧪 Testing

### Sandbox Testing

1. Use sandbox credentials from `.env.example`
2. Test phone numbers: 254708374149, 254721234567
3. All sandbox transactions are simulated

### Production Testing

1. Complete Safaricom's go-live process
2. Use production credentials
3. Real money transactions - test carefully!

## 📚 Resources

- [Daraja API Documentation](https://developer.safaricom.co.ke/Documentation)
- [M-Pesa Integration Guide](https://developer.safaricom.co.ke/docs)
- [Model Context Protocol](https://modelcontextprotocol.io/)
- [PHP 8 Documentation](https://www.php.net/manual/en/)
- [Guzzle HTTP Client](http://docs.guzzlephp.org/)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes with proper PHP 8+ features
4. Add tests for new functionality
5. Ensure code passes quality checks (`composer quality`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## 🔧 Development Commands

```bash
# Run tests
composer test

# Check code style
composer cs-check

# Fix code style
composer cs-fix

# Static analysis
composer analyse

# All quality checks
composer quality
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](../LICENSE) file for details.

## 👨‍💻 Author

**Meshack Musyoka**
- Email: meshack@example.com
- GitHub: [@meshackmusyoka](https://github.com/meshackmusyoka)

---

**Made with ❤️ in Kenya 🇰🇪**