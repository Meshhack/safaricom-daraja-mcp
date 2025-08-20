# Safaricom Daraja MCP - Integration Guide

Step-by-step guide to integrate the Safaricom Daraja MCP server into your applications.

**Author:** Meshack Musyoka

## Overview

The Safaricom Daraja MCP server provides a Model Context Protocol interface to the Safaricom Daraja API, enabling seamless integration with M-Pesa payment services.

## Prerequisites

### System Requirements

**All Implementations:**
- Active internet connection
- HTTPS-enabled domain for callbacks
- Safaricom developer account

**Node.js/TypeScript:**
- Node.js 18.0+
- npm or yarn

**Python:**
- Python 3.8+
- pip

**PHP:**
- PHP 8.0+
- Composer
- ext-json, ext-curl

### Safaricom Developer Account

1. Visit [developer.safaricom.co.ke](https://developer.safaricom.co.ke/)
2. Create an account and verify your email
3. Create a new application to get credentials
4. Note down your Consumer Key and Consumer Secret

## Quick Start Guide

### Step 1: Choose Implementation

Select the implementation that matches your technology stack:

- **Node.js/TypeScript**: Best for JavaScript/TypeScript projects
- **Python**: Best for Python projects or AI/ML applications
- **PHP**: Best for PHP web applications

### Step 2: Installation

#### Node.js/TypeScript
```bash
cd nodejs
npm install
cp .env.example .env
npm run build
```

#### Python
```bash
cd python
pip install -r requirements.txt
cp .env.example .env
```

#### PHP
```bash
cd php
composer install
cp .env.example .env
```

### Step 3: Configuration

Edit your `.env` file with your Daraja credentials:

```env
# Required credentials from Safaricom Developer Portal
DARAJA_CONSUMER_KEY=your_consumer_key_here
DARAJA_CONSUMER_SECRET=your_consumer_secret_here
DARAJA_BUSINESS_SHORT_CODE=your_business_shortcode
DARAJA_PASS_KEY=your_pass_key

# Environment (sandbox for testing, production for live)
DARAJA_ENVIRONMENT=sandbox

# Optional: Required for B2C, B2B, Balance, Status, Reversal operations
DARAJA_INITIATOR_NAME=your_initiator_name
DARAJA_INITIATOR_PASSWORD=your_initiator_password
```

### Step 4: Run the Server

#### Node.js/TypeScript
```bash
npm start
```

#### Python
```bash
python -m mcp_daraja.server
```

#### PHP
```bash
./bin/mcp-daraja
```

## Detailed Integration Steps

### 1. Environment Setup

#### Sandbox Environment
For testing and development:

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

#### Production Environment
For live transactions:

```env
DARAJA_ENVIRONMENT=production
DARAJA_CONSUMER_KEY=your_production_key
DARAJA_CONSUMER_SECRET=your_production_secret
DARAJA_BUSINESS_SHORT_CODE=your_live_shortcode
DARAJA_PASS_KEY=your_live_pass_key
```

### 2. Callback URL Setup

Most Daraja operations require callback URLs to receive results. Set up HTTPS endpoints:

#### Example Callback Handler (Node.js/Express)
```javascript
const express = require('express');
const app = express();

app.use(express.json());

// STK Push callback
app.post('/daraja/callback', (req, res) => {
    console.log('STK Push callback:', req.body);
    
    const { Body } = req.body;
    if (Body && Body.stkCallback) {
        const callback = Body.stkCallback;
        
        if (callback.ResultCode === 0) {
            // Payment successful
            console.log('Payment successful:', callback.CheckoutRequestID);
        } else {
            // Payment failed or cancelled
            console.log('Payment failed:', callback.ResultDesc);
        }
    }
    
    res.json({ ResultCode: 0, ResultDesc: "Accepted" });
});

// B2C/B2B result callback
app.post('/daraja/result', (req, res) => {
    console.log('Transaction result:', req.body);
    res.json({ ResultCode: 0, ResultDesc: "Accepted" });
});

// Timeout callback
app.post('/daraja/timeout', (req, res) => {
    console.log('Transaction timeout:', req.body);
    res.json({ ResultCode: 0, ResultDesc: "Accepted" });
});

app.listen(3000, () => {
    console.log('Callback server running on port 3000');
});
```

### 3. MCP Client Integration

#### Using with MCP Desktop Clients

Add to your MCP configuration:

```json
{
  "mcpServers": {
    "daraja": {
      "command": "node",
      "args": ["/path/to/safaricom-daraja-mcp/nodejs/dist/index.js"],
      "env": {
        "DARAJA_CONSUMER_KEY": "your_key",
        "DARAJA_CONSUMER_SECRET": "your_secret",
        "DARAJA_BUSINESS_SHORT_CODE": "your_shortcode",
        "DARAJA_PASS_KEY": "your_pass_key",
        "DARAJA_ENVIRONMENT": "sandbox"
      }
    }
  }
}
```

#### Using with Custom MCP Client

```javascript
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';

// Create client and transport
const transport = new StdioClientTransport({
  command: 'node',
  args: ['/path/to/daraja/server.js']
});

const client = new Client(
  { name: 'daraja-client', version: '1.0.0' },
  { capabilities: {} }
);

// Connect to server
await client.connect(transport);

// Call a tool
const result = await client.request({
  method: 'tools/call',
  params: {
    name: 'daraja_stk_push',
    arguments: {
      amount: 100,
      phone_number: '254708374149',
      callback_url: 'https://yourdomain.com/callback',
      account_reference: 'TEST123',
      transaction_desc: 'Test Payment'
    }
  }
});

console.log('STK Push result:', result);
```

## Common Integration Patterns

### 1. E-commerce Checkout

```javascript
// Step 1: Customer initiates payment
const checkoutResult = await client.request({
  method: 'tools/call',
  params: {
    name: 'daraja_stk_push',
    arguments: {
      amount: orderTotal,
      phone_number: customerPhone,
      callback_url: 'https://mystore.com/payment/callback',
      account_reference: orderId,
      transaction_desc: 'Order Payment'
    }
  }
});

// Step 2: Store checkout request ID
const checkoutRequestId = extractCheckoutRequestId(checkoutResult);
await storePaymentRequest(orderId, checkoutRequestId);

// Step 3: Handle callback in your webhook
app.post('/payment/callback', async (req, res) => {
    const { Body } = req.body;
    if (Body && Body.stkCallback) {
        const callback = Body.stkCallback;
        
        if (callback.ResultCode === 0) {
            // Payment successful - fulfill order
            await fulfillOrder(callback.CheckoutRequestID);
        } else {
            // Payment failed - handle failure
            await handlePaymentFailure(callback.CheckoutRequestID);
        }
    }
    res.json({ ResultCode: 0, ResultDesc: "Accepted" });
});
```

### 2. Subscription Billing

```javascript
// Monthly subscription payment
const billSubscription = async (subscriberId, amount) => {
    const subscriber = await getSubscriber(subscriberId);
    
    const result = await client.request({
        method: 'tools/call',
        params: {
            name: 'daraja_stk_push',
            arguments: {
                amount: amount,
                phone_number: subscriber.phone,
                callback_url: 'https://myservice.com/billing/callback',
                account_reference: `SUB-${subscriberId}`,
                transaction_desc: 'Monthly Fee'
            }
        }
    });
    
    return result;
};
```

### 3. Payroll Distribution

```javascript
// B2C payment for salary
const paySalary = async (employeeId, amount) => {
    const employee = await getEmployee(employeeId);
    
    const result = await client.request({
        method: 'tools/call',
        params: {
            name: 'daraja_b2c_payment',
            arguments: {
                amount: amount,
                party_b: employee.phone,
                command_id: 'SalaryPayment',
                remarks: `Salary for ${employee.name}`,
                queue_timeout_url: 'https://mycompany.com/payroll/timeout',
                result_url: 'https://mycompany.com/payroll/result',
                occasion: `${getCurrentMonth()} Salary`
            }
        }
    });
    
    return result;
};
```

### 4. Payment Status Monitoring

```javascript
// Query payment status
const checkPaymentStatus = async (checkoutRequestId) => {
    const result = await client.request({
        method: 'tools/call',
        params: {
            name: 'daraja_stk_query',
            arguments: {
                checkout_request_id: checkoutRequestId
            }
        }
    });
    
    return parseStatusFromResult(result);
};

// Periodic status checking
const monitorPayment = async (checkoutRequestId, maxRetries = 10) => {
    for (let i = 0; i < maxRetries; i++) {
        const status = await checkPaymentStatus(checkoutRequestId);
        
        if (status.completed) {
            return status;
        }
        
        // Wait 10 seconds before next check
        await new Promise(resolve => setTimeout(resolve, 10000));
    }
    
    throw new Error('Payment monitoring timeout');
};
```

## Error Handling Best Practices

### 1. Network Errors

```javascript
const makePaymentRequest = async (paymentData) => {
    try {
        return await client.request({
            method: 'tools/call',
            params: {
                name: 'daraja_stk_push',
                arguments: paymentData
            }
        });
    } catch (error) {
        if (error.code === 'NETWORK_ERROR') {
            // Retry with exponential backoff
            return await retryWithBackoff(() => makePaymentRequest(paymentData));
        }
        
        throw error;
    }
};
```

### 2. API Errors

```javascript
const handleApiError = (error) => {
    const { code, status, response } = error;
    
    switch (code) {
        case '400001':
            return 'Invalid phone number format';
        case '500001':
            return 'Internal server error, please try again';
        case '401':
            return 'Authentication failed, check credentials';
        default:
            return `API Error: ${error.message}`;
    }
};
```

### 3. Validation Errors

```javascript
const validatePaymentData = (data) => {
    const errors = [];
    
    if (!data.amount || data.amount <= 0) {
        errors.push('Amount must be positive');
    }
    
    if (!data.phone_number || !/^254[17]\d{8}$/.test(data.phone_number)) {
        errors.push('Invalid phone number format');
    }
    
    if (!data.callback_url || !isValidHttpsUrl(data.callback_url)) {
        errors.push('Callback URL must be HTTPS');
    }
    
    if (errors.length > 0) {
        throw new ValidationError(errors);
    }
};
```

## Security Considerations

### 1. Credential Management

- Store credentials in environment variables, not code
- Use different credentials for sandbox and production
- Rotate credentials periodically
- Never log sensitive credentials

### 2. Callback Security

```javascript
// Validate callback authenticity
app.post('/daraja/callback', (req, res) => {
    // Verify request comes from Safaricom
    if (!isValidSafaricomRequest(req)) {
        return res.status(403).json({ error: 'Unauthorized' });
    }
    
    // Process callback
    processCallback(req.body);
    res.json({ ResultCode: 0, ResultDesc: "Accepted" });
});
```

### 3. Data Privacy

- Hash or encrypt sensitive data in logs
- Implement data retention policies
- Follow GDPR/privacy regulations
- Sanitize user inputs

## Testing Strategy

### 1. Sandbox Testing

```javascript
// Test suite for sandbox environment
describe('Daraja MCP Integration', () => {
    beforeAll(async () => {
        // Ensure sandbox environment
        expect(process.env.DARAJA_ENVIRONMENT).toBe('sandbox');
    });
    
    test('should initiate STK push successfully', async () => {
        const result = await client.request({
            method: 'tools/call',
            params: {
                name: 'daraja_stk_push',
                arguments: {
                    amount: 1,
                    phone_number: '254708374149',
                    callback_url: 'https://test.example.com/callback',
                    account_reference: 'TEST',
                    transaction_desc: 'Test'
                }
            }
        });
        
        expect(result.content[0].text).toContain('STK Push initiated successfully');
    });
});
```

### 2. Production Testing

- Test with small amounts first
- Verify all callback URLs work correctly
- Test error scenarios
- Monitor transaction logs

## Monitoring and Logging

### 1. Application Logging

```javascript
const logger = require('winston');

// Log all Daraja API calls
const logDarajaCall = (toolName, arguments, result) => {
    logger.info('Daraja API Call', {
        tool: toolName,
        arguments: sanitizeArguments(arguments),
        result: sanitizeResult(result),
        timestamp: new Date().toISOString()
    });
};
```

### 2. Health Monitoring

```javascript
// Health check endpoint
app.get('/health/daraja', async (req, res) => {
    try {
        // Test token generation
        const result = await client.request({
            method: 'tools/call',
            params: { name: 'daraja_generate_token', arguments: {} }
        });
        
        res.json({ 
            status: 'healthy', 
            timestamp: new Date().toISOString() 
        });
    } catch (error) {
        res.status(503).json({ 
            status: 'unhealthy', 
            error: error.message,
            timestamp: new Date().toISOString()
        });
    }
});
```

## Performance Optimization

### 1. Token Caching

The MCP server automatically handles token caching, but you can implement additional optimizations:

```javascript
// Cache token at application level
class DarajaTokenCache {
    constructor() {
        this.token = null;
        this.expiry = null;
    }
    
    async getToken() {
        if (this.token && this.expiry > new Date()) {
            return this.token;
        }
        
        const result = await this.generateNewToken();
        this.token = result.access_token;
        this.expiry = new Date(Date.now() + (result.expires_in * 1000));
        
        return this.token;
    }
}
```

### 2. Request Batching

```javascript
// Batch multiple operations
const batchPayments = async (payments) => {
    const promises = payments.map(payment => 
        client.request({
            method: 'tools/call',
            params: {
                name: 'daraja_stk_push',
                arguments: payment
            }
        })
    );
    
    return Promise.allSettled(promises);
};
```

## Troubleshooting

### Common Issues

1. **Token Generation Fails**
   - Check consumer key and secret
   - Verify network connectivity
   - Ensure correct environment

2. **STK Push Not Received**
   - Verify phone number format
   - Check if phone is online
   - Confirm M-Pesa app is installed

3. **Callback Not Received**
   - Verify HTTPS callback URL
   - Check firewall settings
   - Test URL accessibility

4. **B2C/B2B Fails**
   - Verify initiator credentials
   - Check business account balance
   - Confirm recipient details

### Debug Mode

Enable verbose logging for debugging:

```bash
# Node.js
DEBUG=daraja* npm start

# Python
LOGLEVEL=DEBUG python -m mcp_daraja.server

# PHP
LOG_LEVEL=DEBUG ./bin/mcp-daraja
```

## Next Steps

1. **Go Live Process**: Complete Safaricom's production approval process
2. **Monitoring Setup**: Implement comprehensive monitoring and alerting
3. **Backup Strategies**: Set up failover mechanisms
4. **Scaling**: Plan for high-volume transaction handling
5. **Compliance**: Ensure regulatory compliance for financial transactions

## 💝 Support This Project

If this integration guide has been helpful for your M-Pesa development, consider supporting the project:

### ☕ Buy Me a Coffee
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-Support-orange?style=for-the-badge&logo=buy-me-a-coffee)](https://buymeacoffee.com/meshhack)

### 📱 M-Pesa (Kenya)
For local supporters in Kenya:  
**📞 +254 702 152 220** (Meshack Musyoka)

*Your support helps maintain and expand this comprehensive MCP integration! 🙏*

---
**Developed with ❤️ by [Meshack Musyoka](https://github.com/Meshhack) in Kenya 🇰🇪**