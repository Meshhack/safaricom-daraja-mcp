# Safaricom Daraja MCP - API Reference

Complete reference for all MCP tools available in the Safaricom Daraja API server.

**Author:** Meshack Musyoka

## Authentication Tools

### `daraja_generate_token`

Generate OAuth access token for Daraja API authentication.

**Input Schema:**
```json
{}
```

**Usage:**
```javascript
// No parameters required
```

**Response:**
- Access token with expiration details
- Security notes about token storage

**Example Output:**
```
✅ Token generated successfully!

📋 Token Details:
- Access Token: eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...
- Expires In: 3599 seconds
- Valid Until: 2023-12-01T15:30:00Z

⚠️ Security Note: Store this token securely and use it for subsequent API calls.
```

## Payment Operations

### `daraja_stk_push`

Initiate STK Push (M-Pesa Express) payment request to customer's phone.

**Input Schema:**
```json
{
  "amount": {
    "type": "integer",
    "minimum": 1,
    "maximum": 70000,
    "description": "Payment amount in KSH"
  },
  "phone_number": {
    "type": "string",
    "pattern": "^(?:254|\\+254|0)?([17]\\d{8})$",
    "description": "Customer phone number"
  },
  "callback_url": {
    "type": "string",
    "format": "uri",
    "description": "HTTPS URL to receive payment callbacks"
  },
  "account_reference": {
    "type": "string",
    "maxLength": 12,
    "description": "Account reference for the transaction"
  },
  "transaction_desc": {
    "type": "string",
    "maxLength": 13,
    "description": "Transaction description"
  }
}
```

**Usage:**
```javascript
{
  "amount": 100,
  "phone_number": "254708374149",
  "callback_url": "https://yourdomain.com/callback",
  "account_reference": "TEST123",
  "transaction_desc": "Payment Test"
}
```

**Phone Number Formats:**
- `254708374149` (preferred)
- `0708374149` (converted to 254708374149)
- `+254708374149` (converted to 254708374149)

**Response:**
- Payment request details
- Checkout Request ID for status queries
- Customer message

### `daraja_stk_query`

Query the status of an STK Push transaction.

**Input Schema:**
```json
{
  "checkout_request_id": {
    "type": "string",
    "description": "CheckoutRequestID from STK Push response"
  }
}
```

**Usage:**
```javascript
{
  "checkout_request_id": "ws_CO_191220231234567890"
}
```

**Status Codes:**
- `0`: Payment successful
- `1032`: Payment cancelled by user
- `1037`: Payment timeout
- Other codes: Check Daraja documentation

### `daraja_c2b_register`

Register validation and confirmation URLs for C2B transactions.

**Input Schema:**
```json
{
  "confirmation_url": {
    "type": "string",
    "format": "uri",
    "description": "HTTPS URL to receive payment confirmations"
  },
  "validation_url": {
    "type": "string",
    "format": "uri",
    "description": "HTTPS URL for payment validation"
  },
  "response_type": {
    "type": "string",
    "enum": ["Cancelled", "Completed"],
    "default": "Completed",
    "description": "Response type for validation"
  }
}
```

**Usage:**
```javascript
{
  "confirmation_url": "https://yourdomain.com/c2b/confirmation",
  "validation_url": "https://yourdomain.com/c2b/validation",
  "response_type": "Completed"
}
```

### `daraja_c2b_simulate`

Simulate C2B payment for testing (sandbox environment only).

**Input Schema:**
```json
{
  "amount": {
    "type": "integer",
    "minimum": 1,
    "description": "Payment amount"
  },
  "msisdn": {
    "type": "string",
    "pattern": "^(?:254|\\+254|0)?([17]\\d{8})$",
    "description": "Customer phone number"
  },
  "command_id": {
    "type": "string",
    "enum": ["CustomerPayBillOnline", "CustomerBuyGoodsOnline"],
    "default": "CustomerPayBillOnline",
    "description": "Transaction command ID"
  },
  "bill_ref_number": {
    "type": "string",
    "description": "Bill reference number (optional)"
  }
}
```

**Usage:**
```javascript
{
  "amount": 1000,
  "msisdn": "254708374149",
  "command_id": "CustomerPayBillOnline",
  "bill_ref_number": "BILL123"
}
```

### `daraja_b2c_payment`

Send money from business to customer (B2C).

**Input Schema:**
```json
{
  "amount": {
    "type": "integer",
    "minimum": 1,
    "description": "Payment amount"
  },
  "party_b": {
    "type": "string",
    "pattern": "^(?:254|\\+254|0)?([17]\\d{8})$",
    "description": "Recipient phone number"
  },
  "command_id": {
    "type": "string",
    "enum": ["SalaryPayment", "BusinessPayment", "PromotionPayment"],
    "default": "BusinessPayment",
    "description": "Payment command type"
  },
  "remarks": {
    "type": "string",
    "maxLength": 100,
    "description": "Payment remarks"
  },
  "queue_timeout_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for timeout notifications"
  },
  "result_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for result notifications"
  },
  "occasion": {
    "type": "string",
    "maxLength": 100,
    "description": "Payment occasion (optional)"
  }
}
```

**Usage:**
```javascript
{
  "amount": 5000,
  "party_b": "254708374149",
  "command_id": "SalaryPayment",
  "remarks": "Monthly salary payment",
  "queue_timeout_url": "https://yourdomain.com/timeout",
  "result_url": "https://yourdomain.com/result",
  "occasion": "December 2023 Salary"
}
```

**Command Types:**
- `SalaryPayment`: Employee salary payments
- `BusinessPayment`: General business payments
- `PromotionPayment`: Promotional payments and rewards

### `daraja_b2b_payment`

Transfer money between business accounts (B2B).

**Input Schema:**
```json
{
  "amount": {
    "type": "integer",
    "minimum": 1,
    "description": "Transfer amount"
  },
  "party_b": {
    "type": "string",
    "description": "Recipient business shortcode or till number"
  },
  "command_id": {
    "type": "string",
    "enum": ["BusinessPayBill", "BusinessBuyGoods", "DisburseFundsToBusiness", "BusinessToBusinessTransfer"],
    "default": "BusinessPayBill",
    "description": "Transfer command type"
  },
  "remarks": {
    "type": "string",
    "maxLength": 100,
    "description": "Transfer remarks"
  },
  "queue_timeout_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for timeout notifications"
  },
  "result_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for result notifications"
  },
  "account_reference": {
    "type": "string",
    "maxLength": 12,
    "description": "Account reference"
  }
}
```

**Usage:**
```javascript
{
  "amount": 10000,
  "party_b": "123456",
  "command_id": "BusinessPayBill",
  "remarks": "Payment for services",
  "queue_timeout_url": "https://yourdomain.com/timeout",
  "result_url": "https://yourdomain.com/result",
  "account_reference": "INV2023001"
}
```

## Utility Operations

### `daraja_account_balance`

Query M-Pesa account balance.

**Input Schema:**
```json
{
  "identifier_type": {
    "type": "string",
    "enum": ["1", "2", "4"],
    "default": "4",
    "description": "Identifier type"
  },
  "remarks": {
    "type": "string",
    "maxLength": 100,
    "description": "Query remarks"
  },
  "queue_timeout_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for timeout notifications"
  },
  "result_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for result notifications"
  }
}
```

**Identifier Types:**
- `1`: MSISDN (Mobile number)
- `2`: Till Number
- `4`: Organization shortcode (default)

**Usage:**
```javascript
{
  "identifier_type": "4",
  "remarks": "Balance inquiry",
  "queue_timeout_url": "https://yourdomain.com/timeout",
  "result_url": "https://yourdomain.com/balance"
}
```

### `daraja_transaction_status`

Query the status of any Daraja transaction.

**Input Schema:**
```json
{
  "transaction_id": {
    "type": "string",
    "description": "Transaction ID to query"
  },
  "identifier_type": {
    "type": "string",
    "enum": ["1", "2", "4"],
    "default": "4",
    "description": "Identifier type"
  },
  "result_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for result notifications"
  },
  "queue_timeout_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for timeout notifications"
  },
  "remarks": {
    "type": "string",
    "maxLength": 100,
    "description": "Query remarks"
  },
  "occasion": {
    "type": "string",
    "maxLength": 100,
    "description": "Query occasion (optional)"
  }
}
```

**Usage:**
```javascript
{
  "transaction_id": "LGR019G3J2",
  "identifier_type": "4",
  "result_url": "https://yourdomain.com/status",
  "queue_timeout_url": "https://yourdomain.com/timeout",
  "remarks": "Status check for payment",
  "occasion": "Payment verification"
}
```

### `daraja_reversal`

Reverse a Daraja transaction.

**Input Schema:**
```json
{
  "transaction_id": {
    "type": "string",
    "description": "Transaction ID to reverse"
  },
  "amount": {
    "type": "integer",
    "minimum": 1,
    "description": "Amount to reverse"
  },
  "receiver_party": {
    "type": "string",
    "description": "Party to receive the reversal"
  },
  "receiver_identifier_type": {
    "type": "string",
    "enum": ["1", "2", "4", "11"],
    "default": "11",
    "description": "Receiver identifier type"
  },
  "result_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for result notifications"
  },
  "queue_timeout_url": {
    "type": "string",
    "format": "uri",
    "description": "URL for timeout notifications"
  },
  "remarks": {
    "type": "string",
    "maxLength": 100,
    "description": "Reversal remarks"
  },
  "occasion": {
    "type": "string",
    "maxLength": 100,
    "description": "Reversal occasion (optional)"
  }
}
```

**Receiver Identifier Types:**
- `1`: MSISDN
- `2`: Till Number  
- `4`: Organization shortcode
- `11`: Organization (default)

**Usage:**
```javascript
{
  "transaction_id": "LGR019G3J2",
  "amount": 1000,
  "receiver_party": "254708374149",
  "receiver_identifier_type": "1",
  "result_url": "https://yourdomain.com/reversal",
  "queue_timeout_url": "https://yourdomain.com/timeout",
  "remarks": "Refund for cancelled order",
  "occasion": "Order cancellation"
}
```

### `daraja_generate_qr`

Generate dynamic QR code for M-Pesa payments.

**Input Schema:**
```json
{
  "merchant_name": {
    "type": "string",
    "maxLength": 22,
    "description": "Merchant name"
  },
  "ref_no": {
    "type": "string",
    "maxLength": 12,
    "description": "Reference number"
  },
  "amount": {
    "type": "integer",
    "minimum": 1,
    "description": "Payment amount"
  },
  "trx_code": {
    "type": "string",
    "enum": ["BG", "WA", "PB", "SM"],
    "description": "Transaction code"
  },
  "cpi": {
    "type": "string",
    "description": "Consumer Price Index identifier"
  },
  "size": {
    "type": "string",
    "enum": ["300"],
    "default": "300",
    "description": "QR code size in pixels"
  }
}
```

**Transaction Codes:**
- `BG`: Buy Goods
- `WA`: Withdraw Agent
- `PB`: Pay Bill
- `SM`: Send Money

**Usage:**
```javascript
{
  "merchant_name": "Test Merchant",
  "ref_no": "QR123",
  "amount": 500,
  "trx_code": "BG",
  "cpi": "373132",
  "size": "300"
}
```

## Error Handling

All tools return consistent error responses with detailed information:

```json
{
  "content": [{
    "type": "text",
    "text": "❌ Error message with details"
  }],
  "isError": true
}
```

**Common Error Types:**
- **Validation Errors**: Input parameter validation failures
- **Authentication Errors**: Token generation or expired token issues
- **API Errors**: Daraja API response errors with codes and descriptions
- **Network Errors**: Connection timeouts or network issues
- **Configuration Errors**: Missing credentials or invalid environment setup

## Rate Limits and Best Practices

1. **Token Management**: Tokens are automatically managed with 1-hour expiration
2. **Rate Limits**: Respect Safaricom's API rate limits (varies by endpoint)
3. **Callback URLs**: Always use HTTPS for callback URLs
4. **Phone Numbers**: Use Kenyan format (254XXXXXXXX) for best compatibility
5. **Error Handling**: Implement proper error handling in your application
6. **Logging**: Monitor API responses for debugging and audit trails

## Environment Considerations

### Sandbox
- Use test credentials and phone numbers
- All transactions are simulated
- No real money involved

### Production
- Requires Safaricom go-live approval
- Real money transactions
- Enhanced security requirements
- Proper SSL certificates required for callbacks