# Safaricom Daraja API MCP

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Node.js](https://img.shields.io/badge/Node.js-18%2B-green.svg)](https://nodejs.org/)
[![Python](https://img.shields.io/badge/Python-3.8%2B-blue.svg)](https://python.org/)
[![PHP](https://img.shields.io/badge/PHP-8.0%2B-purple.svg)](https://php.net/)

A comprehensive Model Context Protocol (MCP) implementation for the Safaricom Daraja API, enabling seamless integration with M-Pesa payment services.

**Developed by:** Meshack Musyoka

## 🚀 Features

### Core Payment Operations
- **M-Pesa Express (STK Push)** - Lipa Na M-Pesa Online payments
- **C2B Payments** - Customer to Business transactions
- **B2C Payments** - Business to Customer disbursements
- **B2B Payments** - Business to Business transfers

### Utility Operations
- **Account Balance** - Query M-Pesa account balance
- **Transaction Status** - Check transaction status
- **Transaction Reversal** - Reverse erroneous transactions
- **Dynamic QR Codes** - Generate payment QR codes

### Advanced Features
- **Sandbox & Production** environment support
- **Comprehensive error handling** with detailed responses
- **Rate limiting** and retry logic
- **Secure credential management**
- **Full TypeScript support** with type definitions

## 📦 Available Implementations

| Language | Status | Directory | Package Manager | Features |
|----------|--------|-----------|-----------------|----------|
| **Node.js/TypeScript** | ✅ Complete | `nodejs/` | [npm](https://www.npmjs.com/package/@meshark/safaricom-daraja-mcp) | Full MCP server with async/await |
| **Python** | ✅ Complete | `python/` | PyPI (coming soon) | Pythonic implementation with type hints |
| **PHP** | ✅ Complete | `php/` | Composer (coming soon) | Modern PHP 8+ with OOP structure |

## 🛠 MCP Tools

### Authentication
- `daraja_generate_token` - Generate OAuth access tokens for API calls

### Payment Operations
- `daraja_stk_push` - Initiate M-Pesa Express payment request
- `daraja_stk_query` - Query STK Push transaction status
- `daraja_c2b_register` - Register C2B confirmation and validation URLs
- `daraja_c2b_simulate` - Simulate C2B payment (sandbox only)
- `daraja_b2c_payment` - Send money from business to customer
- `daraja_b2b_payment` - Transfer funds between business accounts

### Utility Operations
- `daraja_account_balance` - Check M-Pesa account balance
- `daraja_transaction_status` - Query the status of any transaction
- `daraja_reversal` - Reverse a transaction
- `daraja_generate_qr` - Generate dynamic QR codes for payments

## 🚀 Quick Start

### Node.js/TypeScript

#### Install from npm (Recommended)
```bash
# Global installation
npm install -g @meshark/safaricom-daraja-mcp

# Set environment variables and run
export DARAJA_CONSUMER_KEY=your_key
export DARAJA_CONSUMER_SECRET=your_secret
export DARAJA_BUSINESS_SHORT_CODE=your_shortcode
export DARAJA_PASS_KEY=your_pass_key
export DARAJA_ENVIRONMENT=sandbox
mcp-daraja
```

#### Build from source
```bash
cd nodejs
npm install
npm run build
npm start
```

### Python
```bash
cd python
pip install -r requirements.txt
python -m mcp_daraja
```

### PHP
```bash
cd php
composer install
php src/server.php
```

## 📖 Documentation

- [**API Reference**](docs/api-reference.md) - Detailed documentation of all MCP tools
- [**Integration Guide**](docs/integration-guide.md) - Step-by-step integration instructions  
- [**Configuration**](docs/configuration.md) - Environment setup and configuration
- [**Examples**](examples/) - Code examples for each implementation
- [**Troubleshooting**](docs/troubleshooting.md) - Common issues and solutions

## 🔐 Configuration

All implementations require the following Daraja API credentials:

```json
{
  "consumer_key": "your_consumer_key",
  "consumer_secret": "your_consumer_secret", 
  "business_short_code": "your_shortcode",
  "pass_key": "your_pass_key",
  "environment": "sandbox" // or "production"
}
```

## 🌐 Environment Support

### Sandbox URLs
- **Base URL**: `https://sandbox.safaricom.co.ke`
- **OAuth**: `/oauth/v1/generate`
- **STK Push**: `/mpesa/stkpush/v1/processrequest`

### Production URLs  
- **Base URL**: `https://api.safaricom.co.ke`
- **OAuth**: `/oauth/v1/generate`
- **STK Push**: `/mpesa/stkpush/v1/processrequest`

## 🤝 Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 👨‍💻 Author

**Meshack Musyoka**
- Email: meshnzai1@gmail.com
- GitHub: [@Meshhack](https://github.com/Meshhack)

## 💝 Support This Project

If this MCP server has been helpful for your M-Pesa integration, consider supporting its development:

### ☕ Buy Me a Coffee
[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-Support-orange?style=for-the-badge&logo=buy-me-a-coffee)](https://buymeacoffee.com/meshhack)

### 📱 M-Pesa (Kenya)
For local supporters in Kenya, you can send M-Pesa donations directly:  
**📞 +254 702 152 220** (Meshack Musyoka)

*Your support helps maintain and improve this open-source project! 🙏*

## 🙏 Acknowledgments

- [Safaricom](https://www.safaricom.co.ke/) for the Daraja API
- [Model Context Protocol](https://modelcontextprotocol.io/) by Anthropic
- The open-source community for inspiration and tools

## 📞 Support

For support and questions:
- Create an [issue](https://github.com/Meshhack/safaricom-daraja-mcp/issues)
- Check the [documentation](docs/)
- Review [examples](examples/)

---

**Made with ❤️ in Kenya 🇰🇪**