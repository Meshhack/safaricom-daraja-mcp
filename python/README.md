# Safaricom Daraja MCP - Python

A comprehensive Model Context Protocol (MCP) server implementation for the Safaricom Daraja API in Python with full type hints and async support.

**Developed by:** Meshack Musyoka

## 🚀 Features

- **Full Type Hints** with Pydantic models for data validation
- **Async/Await** for all API operations with httpx
- **Structured Logging** with detailed request/response logging
- **Comprehensive Error Handling** with custom exception types
- **Automatic Token Management** with expiration handling
- **Input Validation** using Pydantic models
- **Environment Configuration** with dotenv support
- **Production Ready** with proper resource management

## 📦 Installation

```bash
# Clone the repository
git clone https://github.com/meshackmusyoka/safaricom-daraja-mcp.git
cd safaricom-daraja-mcp/python

# Install dependencies
pip install -r requirements.txt

# Or install with pip (after building)
pip install -e .

# Configure environment
cp .env.example .env
# Edit .env with your Daraja credentials

# Run the MCP server
python -m mcp_daraja.server

# Or using the installed command
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

## 📝 Usage Examples

### STK Push Payment

```python
# Tool call to daraja_stk_push
{
    "amount": 100,
    "phone_number": "254708374149",
    "callback_url": "https://yourdomain.com/callback",
    "account_reference": "TEST123",
    "transaction_desc": "Payment Test"
}
```

### Query Payment Status

```python
# Tool call to daraja_stk_query
{
    "checkout_request_id": "ws_CO_191220231234567890"
}
```

### B2C Payment

```python
# Tool call to daraja_b2c_payment
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

### Project Structure

```
python/
├── mcp_daraja/
│   ├── __init__.py       # Package initialization
│   ├── server.py         # MCP server implementation
│   ├── client.py         # Daraja API client
│   └── types.py          # Pydantic models and types
├── requirements.txt      # Dependencies
├── pyproject.toml       # Build configuration
├── .env.example         # Environment template
└── README.md            # This file
```

### Dependencies

- **mcp**: Model Context Protocol SDK
- **httpx**: Modern async HTTP client
- **pydantic**: Data validation with type hints
- **python-dotenv**: Environment variable management
- **structlog**: Structured logging
- **anyio**: Async compatibility layer

### Code Features

#### Type Safety
```python
from mcp_daraja.types import STKPushInput, Environment, DarajaConfig

# Full type hints throughout
config = DarajaConfig(
    consumer_key="key",
    consumer_secret="secret",
    business_short_code="123456",
    pass_key="pass_key",
    environment=Environment.SANDBOX
)
```

#### Error Handling
```python
from mcp_daraja.types import DarajaError

try:
    result = await client.stk_push(...)
except DarajaError as e:
    logger.error("API Error", code=e.code, status=e.status)
except ValidationError as e:
    logger.error("Validation Error", errors=e.errors())
```

#### Structured Logging
```python
import structlog

logger = structlog.get_logger(__name__)

logger.info(
    "STK Push initiated",
    amount=amount,
    phone_number=phone_number,
    checkout_request_id=result.CheckoutRequestID
)
```

## 🔐 Security

- **Environment Variables**: All sensitive credentials stored securely
- **Token Management**: Automatic token refresh with expiration checking
- **Input Validation**: Comprehensive validation using Pydantic models
- **Error Handling**: Secure error messages without credential exposure
- **HTTPS Required**: All callback URLs must use HTTPS
- **Phone Number Normalization**: Automatic format normalization

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

- **Validation Errors**: Pydantic validation with detailed field errors
- **API Errors**: Proper error codes and descriptions from Daraja
- **Network Errors**: Timeout and connectivity handling with retries
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
- [Pydantic Documentation](https://docs.pydantic.dev/)
- [HTTPX Documentation](https://www.python-httpx.org/)

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Make your changes with proper type hints
4. Add tests for new functionality
5. Ensure code passes linting (`black`, `isort`, `mypy`)
6. Commit your changes (`git commit -m 'Add amazing feature'`)
7. Push to the branch (`git push origin feature/amazing-feature`)
8. Open a Pull Request

## 🔧 Development Setup

```bash
# Install development dependencies
pip install -e .[dev]

# Format code
black mcp_daraja/
isort mcp_daraja/

# Type checking
mypy mcp_daraja/

# Run tests
pytest
```

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](../LICENSE) file for details.

## 👨‍💻 Author

**Meshack Musyoka**
- Email: meshack@example.com
- GitHub: [@meshackmusyoka](https://github.com/meshackmusyoka)

---

**Made with ❤️ in Kenya 🇰🇪**