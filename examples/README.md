# Safaricom Daraja MCP - Examples

This directory contains practical examples demonstrating how to use the Safaricom Daraja MCP server in different scenarios and programming languages.

**Author:** Meshack Musyoka

## Available Examples

### 1. STK Push Example (Node.js)
**File:** `stk-push-example.js`

Complete implementation of STK Push payment flow including:
- Payment initiation
- Callback handling
- Status monitoring
- Error handling

**Key Features:**
- Express.js web server
- Automatic callback handling
- Payment status tracking
- Comprehensive error handling
- Real-time payment monitoring

**Usage:**
```bash
cd examples
npm install express @modelcontextprotocol/sdk
node stk-push-example.js
```

**API Endpoints:**
- `POST /pay` - Initiate STK Push payment
- `POST /callback` - Handle M-Pesa callbacks
- `GET /status/:checkoutRequestId` - Check payment status
- `GET /payments` - List all payments
- `GET /health` - Health check

### 2. B2C Payment Example (Python)
**File:** `b2c-payment-example.py`

Business to Customer payment implementation with:
- Payment initiation
- Result handling
- Timeout management
- Comprehensive logging

**Key Features:**
- FastAPI web framework
- Asynchronous processing
- Background task handling
- Structured logging
- Payment status tracking

**Usage:**
```bash
cd examples
pip install fastapi uvicorn httpx
python b2c-payment-example.py
```

**API Endpoints:**
- `POST /send-money` - Initiate B2C payment
- `POST /result` - Handle payment results
- `POST /timeout` - Handle payment timeouts
- `GET /payments` - List all payments
- `GET /payments/{id}` - Get payment details
- `GET /health` - Health check

### 3. QR Code Example (PHP)
**File:** `qr-code-example.php`

Dynamic QR code generation for M-Pesa payments:
- QR code generation
- Multiple transaction types
- Usage instructions
- Status tracking

**Key Features:**
- Built-in PHP server
- Direct Daraja client integration
- Multiple QR code types (BG, PB, WA, SM)
- Detailed usage instructions
- QR code management

**Usage:**
```bash
cd examples
php -S localhost:8080 qr-code-example.php
```

**API Endpoints:**
- `POST /generate-qr` - Generate QR code
- `GET /qr/{id}` - Get QR code details
- `GET /qr-codes` - List all QR codes
- `GET /health` - Health check

## Common Usage Patterns

### 1. E-commerce Integration

```javascript
// STK Push for checkout
const initiatePayment = async (orderData) => {
    const response = await fetch('/pay', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
            amount: orderData.total,
            phoneNumber: orderData.customerPhone,
            reference: orderData.orderId,
            description: `Order #${orderData.orderId}`
        })
    });
    
    return response.json();
};
```

### 2. Payroll System

```python
# B2C payment for salary
salary_data = {
    "recipient_phone": employee.phone,
    "amount": employee.salary,
    "command_id": "SalaryPayment",
    "remarks": f"Salary for {employee.name}",
    "occasion": f"{current_month} Salary"
}

response = requests.post('/send-money', json=salary_data)
```

### 3. Point of Sale

```php
// Generate QR code for payment
$qrRequest = [
    'merchant_name' => 'My Store',
    'amount' => $itemTotal,
    'trx_code' => 'BG', // Buy Goods
    'ref_no' => $transactionRef,
    'cpi' => '373132'
];

$curl = curl_init();
curl_setopt($curl, CURLOPT_URL, 'http://localhost:8080/generate-qr');
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($qrRequest));
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($curl);
```

## Testing with Examples

### Sandbox Testing

All examples are configured for sandbox testing by default. Use these test credentials:

```env
DARAJA_ENVIRONMENT=sandbox
DARAJA_CONSUMER_KEY=your_sandbox_key
DARAJA_CONSUMER_SECRET=your_sandbox_secret
DARAJA_BUSINESS_SHORT_CODE=174379
DARAJA_PASS_KEY=bfb279f9aa9bdbcf158e97dd71a467cd2e0c893059b10f78e6b72ada1ed2c919
```

**Test Phone Numbers:**
- 254708374149
- 254721234567

### Example Test Requests

#### STK Push Test
```bash
curl -X POST http://localhost:3000/pay \
  -H "Content-Type: application/json" \
  -d '{
    "amount": 100,
    "phoneNumber": "254708374149",
    "reference": "TEST123",
    "description": "Test Payment"
  }'
```

#### B2C Payment Test
```bash
curl -X POST http://localhost:8000/send-money \
  -H "Content-Type: application/json" \
  -d '{
    "recipient_phone": "254708374149",
    "amount": 1000,
    "command_id": "BusinessPayment",
    "remarks": "Test B2C payment",
    "occasion": "Testing"
  }'
```

#### QR Code Test
```bash
curl -X POST http://localhost:8080/generate-qr \
  -H "Content-Type: application/json" \
  -d '{
    "merchant_name": "Test Store",
    "amount": 500,
    "trx_code": "BG",
    "ref_no": "QR123",
    "cpi": "373132"
  }'
```

## Error Handling Patterns

### Network Errors
```javascript
const retryRequest = async (requestFn, maxRetries = 3) => {
    for (let i = 0; i < maxRetries; i++) {
        try {
            return await requestFn();
        } catch (error) {
            if (i === maxRetries - 1) throw error;
            await new Promise(resolve => setTimeout(resolve, 1000 * Math.pow(2, i)));
        }
    }
};
```

### Validation Errors
```python
def validate_phone_number(phone: str) -> str:
    """Validate and normalize phone number"""
    # Remove spaces and special characters
    phone = re.sub(r'[^\d+]', '', phone)
    
    # Handle different formats
    if phone.startswith('0'):
        return '254' + phone[1:]
    elif phone.startswith('+254'):
        return phone[1:]
    elif phone.startswith('254'):
        return phone
    else:
        raise ValueError(f"Invalid phone number format: {phone}")
```

### API Errors
```php
function handleDarajaError(DarajaException $e): array
{
    $errorMap = [
        '400001' => 'Invalid phone number format',
        '500001' => 'Internal server error, please try again',
        '401' => 'Authentication failed, check credentials'
    ];
    
    $code = $e->getDarajaCode();
    $message = $errorMap[$code] ?? $e->getMessage();
    
    return [
        'error' => true,
        'code' => $code,
        'message' => $message,
        'timestamp' => date('c')
    ];
}
```

## Callback URL Setup

### Ngrok for Local Development
```bash
# Install ngrok
npm install -g ngrok

# Expose local server
ngrok http 3000

# Use the HTTPS URL for callbacks
# Example: https://abc123.ngrok.io/callback
```

### Production Callback Requirements
1. **HTTPS Required**: All callback URLs must use HTTPS
2. **Public Access**: URLs must be publicly accessible
3. **Response Format**: Must return JSON with ResultCode and ResultDesc
4. **Timeout**: Respond within 30 seconds
5. **Idempotency**: Handle duplicate callbacks gracefully

## Monitoring and Logging

### Log Levels
- **INFO**: Normal operations, payment initiation/completion
- **WARN**: Recoverable errors, timeouts, retries
- **ERROR**: API errors, validation failures
- **DEBUG**: Detailed request/response data (sandbox only)

### Metrics to Track
- Payment success/failure rates
- Average processing time
- Callback response time
- API error rates
- Network timeout frequency

## Security Best Practices

### Credential Management
```javascript
// Use environment variables
const config = {
    consumerKey: process.env.DARAJA_CONSUMER_KEY,
    consumerSecret: process.env.DARAJA_CONSUMER_SECRET,
    // Never hardcode credentials
};
```

### Callback Validation
```python
def validate_callback_source(request):
    """Validate callback comes from Safaricom"""
    # Implement IP whitelist, signature verification, etc.
    allowed_ips = ['196.201.214.200', '196.201.214.206']  # Safaricom IPs
    client_ip = request.headers.get('X-Forwarded-For', request.remote_addr)
    
    if client_ip not in allowed_ips:
        raise ValidationError("Invalid callback source")
```

### Data Privacy
```php
function sanitizeLogData(array $data): array
{
    $sensitive = ['phone_number', 'account_reference', 'customer_name'];
    
    foreach ($sensitive as $field) {
        if (isset($data[$field])) {
            $data[$field] = substr($data[$field], 0, 3) . '***';
        }
    }
    
    return $data;
}
```

## Next Steps

1. **Customize Examples**: Modify examples to match your use case
2. **Add Database**: Implement proper data persistence
3. **Add Authentication**: Implement user authentication and authorization
4. **Error Recovery**: Implement retry logic and failure handling
5. **Monitoring**: Add comprehensive monitoring and alerting
6. **Testing**: Create comprehensive test suites
7. **Documentation**: Document your API endpoints and workflows

## Support

For issues with these examples:
1. Check the logs for error messages
2. Verify your environment configuration
3. Test in sandbox before production
4. Review the [Integration Guide](../docs/integration-guide.md)
5. Check the [API Reference](../docs/api-reference.md)

## Contributing

To add new examples:
1. Follow the existing code structure
2. Include comprehensive error handling
3. Add detailed comments and documentation
4. Test in both sandbox and production environments
5. Update this README with usage instructions