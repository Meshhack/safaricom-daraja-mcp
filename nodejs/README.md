# Safaricom Daraja MCP - Node.js/TypeScript

[![npm version](https://badge.fury.io/js/@meshark%2Fsafaricom-daraja-mcp.svg)](https://badge.fury.io/js/@meshark%2Fsafaricom-daraja-mcp)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Node.js](https://img.shields.io/badge/Node.js-18%2B-green.svg)](https://nodejs.org/)

A comprehensive Model Context Protocol (MCP) server implementation for the Safaricom Daraja API in Node.js with full TypeScript support.

**Author:** Meshack Musyoka  
**Email:** meshnzai1@gmail.com  
**GitHub:** [@Meshhack](https://github.com/Meshhack)

## 🚀 Features

- **Full TypeScript Support** with comprehensive type definitions
- **Async/Await** for all API operations
- **Comprehensive Error Handling** with detailed error messages
- **Automatic Token Management** with refresh capability
- **Input Validation** using Zod schemas
- **Environment Configuration** support
- **Production Ready** with proper logging and error handling

## 📦 Installation

### From npm (Recommended)

```bash
# Install globally to use as CLI
npm install -g @meshark/safaricom-daraja-mcp

# Or install locally in your project
npm install @meshark/safaricom-daraja-mcp
```

### From Source

```bash
# Clone the repository
git clone https://github.com/Meshhack/safaricom-daraja-mcp.git
cd safaricom-daraja-mcp/nodejs

# Install dependencies
npm install

# Configure environment
cp .env.example .env
# Edit .env with your Daraja credentials

# Build the project
npm run build

# Start the MCP server
npm start
```

## 🎯 Quick Start (Global Installation)

After installing globally, you can run the MCP server anywhere:

```bash
# Set environment variables
export DARAJA_CONSUMER_KEY=your_consumer_key
export DARAJA_CONSUMER_SECRET=your_consumer_secret
export DARAJA_BUSINESS_SHORT_CODE=your_shortcode
export DARAJA_PASS_KEY=your_pass_key
export DARAJA_ENVIRONMENT=sandbox

# Start the MCP server
mcp-daraja
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

## 🔌 MCP Client Integration

### With MCP Desktop Clients

Add this to your MCP configuration:

```json
{
  "mcpServers": {
    "daraja": {
      "command": "mcp-daraja",
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

### With Custom MCP Client

```javascript
import { Client } from '@modelcontextprotocol/sdk/client/index.js';
import { StdioClientTransport } from '@modelcontextprotocol/sdk/client/stdio.js';

const transport = new StdioClientTransport({
  command: 'mcp-daraja'
});

const client = new Client(
  { name: 'daraja-client', version: '1.0.0' },
  { capabilities: {} }
);

await client.connect(transport);

// Make a payment
const result = await client.request({
  method: 'tools/call',
  params: {
    name: 'daraja_stk_push',
    arguments: {
      amount: 100,
      phone_number: '254708374149',
      callback_url: 'https://your-domain.com/callback',
      account_reference: 'ORDER123',
      transaction_desc: 'Payment'
    }
  }
});
```

## 📝 Usage Examples

### STK Push Payment

```javascript
// Tool call to daraja_stk_push
{
  "amount": 100,
  "phone_number": "254708374149",
  "callback_url": "https://yourdomain.com/callback",
  "account_reference": "TEST123",
  "transaction_desc": "Payment Test"
}
```

### Query Payment Status

```javascript
// Tool call to daraja_stk_query
{
  "checkout_request_id": "ws_CO_191220231234567890"
}
```

### B2C Payment

```javascript
// Tool call to daraja_b2c_payment
{
  "amount": 500,
  "party_b": "254708374149",
  "command_id": "BusinessPayment",
  "remarks": "Salary payment",
  "queue_timeout_url": "https://yourdomain.com/timeout",
  "result_url": "https://yourdomain.com/result"
}
```

## 🏗 Development

### Scripts

```bash
# Build TypeScript
npm run build

# Start development with watch mode
npm run dev

# Run tests
npm test

# Lint code
npm run lint

# Fix linting issues
npm run lint:fix
```

### Project Structure

```
nodejs/
├── src/
│   ├── index.ts          # MCP server entry point
│   ├── daraja-client.ts  # Daraja API client
│   ├── types.ts          # TypeScript definitions
│   └── schemas.ts        # Zod validation schemas
├── dist/                 # Compiled JavaScript
├── package.json
├── tsconfig.json
├── .env.example
└── README.md
```

## 🔐 Security

- **Environment Variables**: All sensitive credentials stored in environment variables
- **Token Management**: Automatic token refresh and secure storage
- **Input Validation**: Comprehensive validation using Zod schemas
- **Error Handling**: Secure error messages without credential exposure
- **HTTPS Required**: All callback URLs must use HTTPS

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

- **Validation Errors**: Input validation with detailed messages
- **API Errors**: Proper error codes and descriptions from Daraja
- **Network Errors**: Timeout and connectivity handling
- **Authentication Errors**: Token generation and refresh issues

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
- [TypeScript Documentation](https://www.typescriptlang.org/docs/)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes with proper TypeScript types
4. Add tests for new functionality
5. Run linting and tests (`npm run lint && npm test`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](../LICENSE) file for details.

## 👨‍💻 Author

**Meshack Musyoka**
- Email: meshnzai1@gmail.com
- GitHub: [@Meshhack](https://github.com/Meshhack)

## 💝 Support This Project

If this npm package has been helpful for your M-Pesa integration, consider supporting its development:

### ☕ Buy Me a Coffee
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-Support-orange?style=for-the-badge&logo=buy-me-a-coffee)](https://buymeacoffee.com/meshhack)

### 📱 M-Pesa (Kenya)
For local supporters in Kenya, you can send M-Pesa donations directly:  
**📞 +254 702 152 220** (Meshack Musyoka)

*Your support helps maintain and improve this open-source project! 🙏*

---

**Made with ❤️ in Kenya 🇰🇪**