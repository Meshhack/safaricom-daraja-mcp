# MCP Integration Guide - Safaricom Daraja MCP Server

Complete setup guide for integrating the Safaricom Daraja MCP server with popular AI development tools and IDEs in 2025.

**Author:** Meshack Musyoka  
**Email:** meshnzai1@gmail.com  
**GitHub:** [@Meshhack](https://github.com/Meshhack)

## 🚀 Quick Install

First, install the npm package globally:

```bash
npm install -g @meshark/safaricom-daraja-mcp
```

Then configure your preferred MCP client using the guides below.

---

## 🖥️ Claude Desktop

Claude Desktop is the most popular way to use MCP servers with Claude.

### Installation Steps

1. **Install Claude Desktop**

   - Download from [claude.ai](https://claude.ai/download)
   - Available for macOS and Windows

2. **Open Configuration**

   - Click the Settings icon (⚙️) in the bottom corner
   - Select the "Developer" tab
   - Click "Edit Config" to open `claude_desktop_config.json`

3. **Add Daraja MCP Server**

```json
{
  "mcpServers": {
    "daraja": {
      "command": "mcp-daraja",
      "env": {
        "DARAJA_CONSUMER_KEY": "your_consumer_key_here",
        "DARAJA_CONSUMER_SECRET": "your_consumer_secret_here",
        "DARAJA_BUSINESS_SHORT_CODE": "your_business_shortcode",
        "DARAJA_PASS_KEY": "your_pass_key_here",
        "DARAJA_ENVIRONMENT": "sandbox",
        "DARAJA_INITIATOR_NAME": "your_initiator_name",
        "DARAJA_INITIATOR_PASSWORD": "your_initiator_password"
      }
    }
  }
}
```

4. **Restart Claude Desktop**
   - Save the configuration file
   - Completely quit and restart Claude Desktop
   - Look for the 🔌 icon indicating MCP servers are connected

### Platform-Specific Notes

**macOS:**

- Configuration file location: `~/Library/Application Support/Claude/claude_desktop_config.json`

**Windows:**

- Configuration file location: `%APPDATA%\Claude\claude_desktop_config.json`
- Use forward slashes `/` or double backslashes `\\\\` in paths

---

## 💻 Claude Code CLI

Claude Code CLI supports MCP servers for enhanced development workflows.

### Prerequisites

```bash
# Verify Claude Code installation
claude --version

# If not installed:
npm install -g @anthropic/claude-code
```

### Configuration Methods

#### Method 1: CLI Wizard (Recommended for beginners)

```bash
# Add the Daraja MCP server
claude mcp add daraja --package @meshark/safaricom-daraja-mcp
```

Follow the interactive prompts to configure your credentials.

#### Method 2: Direct Configuration (Recommended for advanced users)

1. **Find your configuration file:**

   ```bash
   # Show current config path
   claude config path
   ```

2. **Edit the configuration file:**

   ```json
   {
     "mcp": {
       "servers": {
         "daraja": {
           "command": "mcp-daraja",
           "env": {
             "DARAJA_CONSUMER_KEY": "your_consumer_key_here",
             "DARAJA_CONSUMER_SECRET": "your_consumer_secret_here",
             "DARAJA_BUSINESS_SHORT_CODE": "your_business_shortcode",
             "DARAJA_PASS_KEY": "your_pass_key_here",
             "DARAJA_ENVIRONMENT": "sandbox",
             "DARAJA_INITIATOR_NAME": "your_initiator_name",
             "DARAJA_INITIATOR_PASSWORD": "your_initiator_password"
           }
         }
       }
     }
   }
   ```

3. **Verify setup:**

   ```bash
   # List available MCP servers
   claude mcp list

   # Test the connection
   claude mcp test daraja
   ```

### Environment Variables Alternative

Instead of storing credentials in config, use environment variables:

```bash
# Set in your shell profile (.bashrc, .zshrc, etc.)
export DARAJA_CONSUMER_KEY="your_consumer_key_here"
export DARAJA_CONSUMER_SECRET="your_consumer_secret_here"
export DARAJA_BUSINESS_SHORT_CODE="your_business_shortcode"
export DARAJA_PASS_KEY="your_pass_key_here"
export DARAJA_ENVIRONMENT="sandbox"
export DARAJA_INITIATOR_NAME="your_initiator_name"
export DARAJA_INITIATOR_PASSWORD="your_initiator_password"

# Then use simplified config
claude mcp add daraja --command mcp-daraja
```

---

## 🗂️ VS Code

VS Code has built-in MCP support with preview features available in recent versions.

### Prerequisites

- **VS Code Version**: Latest version recommended (1.102+ for full MCP support)
- **Extensions**: Ensure VS Code is updated to the latest version
- **Node.js**: Version 18+ required for running MCP servers

### Setup Steps

1. **Install the Daraja MCP Package**

   ```bash
   npm install -g @meshark/safaricom-daraja-mcp
   ```

2. **Enable MCP Support in VS Code**

   Create or edit `.vscode/settings.json` in your workspace:

   ```json
   {
     "chat.mcp.enabled": true,
     "chat.agent.enabled": true,
     "mcp.discovery.enabled": true
   }
   ```

3. **Configure MCP Server**

   Create `.vscode/mcp.json` in your workspace root:

   ```json
   {
     "servers": {
       "daraja": {
         "type": "stdio",
         "command": "mcp-daraja",
         "env": {
           "DARAJA_CONSUMER_KEY": "${env:DARAJA_CONSUMER_KEY}",
           "DARAJA_CONSUMER_SECRET": "${env:DARAJA_CONSUMER_SECRET}",
           "DARAJA_BUSINESS_SHORT_CODE": "${env:DARAJA_BUSINESS_SHORT_CODE}",
           "DARAJA_PASS_KEY": "${env:DARAJA_PASS_KEY}",
           "DARAJA_ENVIRONMENT": "${env:DARAJA_ENVIRONMENT}",
           "DARAJA_INITIATOR_NAME": "${env:DARAJA_INITIATOR_NAME}",
           "DARAJA_INITIATOR_PASSWORD": "${env:DARAJA_INITIATOR_PASSWORD}"
         }
       }
     }
   }
   ```

4. **Restart VS Code and Verify Setup**
   - Restart VS Code to load the new MCP configuration
   - Open Command Palette (`Ctrl/Cmd + Shift + P`)
   - Run "MCP: List Servers" to verify the Daraja server appears
   - Enable Agent mode in VS Code Chat interface

---

## 🎯 Cursor IDE

Cursor uses a similar configuration to Claude Desktop but with some differences.

### Configuration

1. **Open Cursor Settings**

   - Go to Cursor > Settings > Features > MCP Servers

2. **Add Server Configuration**

```json
{
  "mcpServers": {
    "daraja": {
      "command": "mcp-daraja",
      "args": [],
      "env": {
        "DARAJA_CONSUMER_KEY": "your_consumer_key_here",
        "DARAJA_CONSUMER_SECRET": "your_consumer_secret_here",
        "DARAJA_BUSINESS_SHORT_CODE": "your_business_shortcode",
        "DARAJA_PASS_KEY": "your_pass_key_here",
        "DARAJA_ENVIRONMENT": "sandbox",
        "DARAJA_INITIATOR_NAME": "your_initiator_name",
        "DARAJA_INITIATOR_PASSWORD": "your_initiator_password"
      }
    }
  }
}
```

3. **Restart Cursor** after saving the configuration

---

## 🐳 Docker Setup

For containerized environments or easier deployment.

### Docker Compose

Create `docker-compose.yml`:

```yaml
version: "3.8"
services:
  daraja-mcp:
    image: node:18-alpine
    working_dir: /app
    volumes:
      - ./:/app
    environment:
      - DARAJA_CONSUMER_KEY=${DARAJA_CONSUMER_KEY}
      - DARAJA_CONSUMER_SECRET=${DARAJA_CONSUMER_SECRET}
      - DARAJA_BUSINESS_SHORT_CODE=${DARAJA_BUSINESS_SHORT_CODE}
      - DARAJA_PASS_KEY=${DARAJA_PASS_KEY}
      - DARAJA_ENVIRONMENT=sandbox
      - DARAJA_INITIATOR_NAME=${DARAJA_INITIATOR_NAME}
      - DARAJA_INITIATOR_PASSWORD=${DARAJA_INITIATOR_PASSWORD}
    command: >
      sh -c "npm install -g @meshark/safaricom-daraja-mcp && 
             mcp-daraja"
    stdin_open: true
    tty: true
```

Create `.env` file:

```env
DARAJA_CONSUMER_KEY=your_consumer_key_here
DARAJA_CONSUMER_SECRET=your_consumer_secret_here
DARAJA_BUSINESS_SHORT_CODE=your_business_shortcode
DARAJA_PASS_KEY=your_pass_key_here
DARAJA_INITIATOR_NAME=your_initiator_name
DARAJA_INITIATOR_PASSWORD=your_initiator_password
```

Run with:

```bash
docker-compose up -d
```

---

## 🛠️ Custom MCP Client

For building your own MCP client integration.

### Basic Client Example

```javascript
import { Client } from "@modelcontextprotocol/sdk/client/index.js";
import { StdioClientTransport } from "@modelcontextprotocol/sdk/client/stdio.js";

// Create transport
const transport = new StdioClientTransport({
  command: "mcp-daraja",
  env: {
    DARAJA_CONSUMER_KEY: "your_consumer_key_here",
    DARAJA_CONSUMER_SECRET: "your_consumer_secret_here",
    DARAJA_BUSINESS_SHORT_CODE: "your_business_shortcode",
    DARAJA_PASS_KEY: "your_pass_key_here",
    DARAJA_ENVIRONMENT: "sandbox",
    DARAJA_INITIATOR_NAME: "your_initiator_name",
    DARAJA_INITIATOR_PASSWORD: "your_initiator_password",
  },
});

// Create client
const client = new Client(
  { name: "daraja-client", version: "1.0.0" },
  { capabilities: {} }
);

// Connect and use
await client.connect(transport);

// List available tools
const tools = await client.request({
  method: "tools/list",
  params: {},
});

console.log("Available tools:", tools.result.tools);

// Call STK Push tool
const stkResult = await client.request({
  method: "tools/call",
  params: {
    name: "daraja_stk_push",
    arguments: {
      amount: 100,
      phone_number: "254708374149",
      callback_url: "https://your-domain.com/callback",
      account_reference: "TEST123",
      transaction_desc: "Test Payment",
    },
  },
});

console.log("STK Push result:", stkResult.result);
```

---

## 🔧 Configuration Management

### Environment Variables

Create a `.env` file in your project root:

```env
# Required for all operations
DARAJA_CONSUMER_KEY=your_consumer_key_here
DARAJA_CONSUMER_SECRET=your_consumer_secret_here
DARAJA_BUSINESS_SHORT_CODE=your_business_shortcode
DARAJA_PASS_KEY=your_pass_key_here

# Environment (sandbox or production)
DARAJA_ENVIRONMENT=sandbox

# Required for B2C, B2B, Balance, Status, Reversal operations
DARAJA_INITIATOR_NAME=your_initiator_name
DARAJA_INITIATOR_PASSWORD=your_initiator_password
```

### Credential Security

**Development:**

- Use `.env` files (never commit to git)
- Use different credentials for sandbox vs production

**Production:**

- Use environment variables or secure secret management
- Rotate credentials regularly
- Monitor API usage

**Team Setup:**

- Share `.env.example` template
- Document credential acquisition process
- Use team-specific sandbox credentials

---

## 📋 Available MCP Tools

Once configured, you'll have access to these 11 Daraja API tools:

### Authentication

- `daraja_generate_token` - Generate OAuth access tokens

### Payment Operations

- `daraja_stk_push` - Initiate M-Pesa Express payments
- `daraja_stk_query` - Query STK Push transaction status
- `daraja_c2b_register` - Register C2B payment URLs
- `daraja_c2b_simulate` - Simulate C2B payments (sandbox only)
- `daraja_b2c_payment` - Send money to customers
- `daraja_b2b_payment` - Transfer between business accounts

### Utility Operations

- `daraja_account_balance` - Check M-Pesa account balance
- `daraja_transaction_status` - Query transaction status
- `daraja_reversal` - Reverse transactions
- `daraja_generate_qr` - Generate dynamic QR codes

---

## 🚨 Troubleshooting

### Common Issues

**MCP Server Not Appearing:**

- Verify npm package is installed globally: `npm list -g @meshark/safaricom-daraja-mcp`
- Check MCP client logs for connection errors
- Ensure configuration file syntax is valid JSON

**Authentication Errors:**

- Verify credentials in Safaricom Developer Portal
- Confirm environment (sandbox/production) matches credentials
- Check credential environment variables are set

**Connection Issues:**

- Restart MCP client after configuration changes
- Check firewall settings for localhost connections
- Verify Node.js version compatibility (18+)

### Debug Logs

**Claude Desktop:**

```bash
# macOS
tail -f ~/Library/Logs/Claude/mcp*.log

# Windows
type %APPDATA%\Claude\Logs\mcp*.log
```

**Claude Code:**

```bash
# Enable debug mode
CLAUDE_DEBUG=1 claude <command>

# View logs
claude logs mcp
```

**VS Code:**

- Open Developer Tools (`Help > Toggle Developer Tools`)
- Check console for MCP-related errors

### Testing Connection

Test your MCP server directly:

```bash
# Test if server starts
echo '{"jsonrpc":"2.0","id":1,"method":"tools/list","params":{}}' | mcp-daraja

# Should return JSON with list of 11 tools
```

---

## 🎯 Usage Examples

### Example 1: STK Push Payment

**Prompt:** "Use the Daraja MCP to initiate a 100 KSH payment request to phone number 254708374149 for order reference ORDER123"

The MCP will:

1. Generate authentication token
2. Initiate STK Push with provided details
3. Return checkout request ID for tracking

### Example 2: Transaction Status Check

**Prompt:** "Check the status of Daraja transaction with checkout request ID ws_CO_123456789"

The MCP will:

1. Query the transaction status
2. Return current status and details
3. Provide status interpretation

### Example 3: B2C Payment

**Prompt:** "Send 500 KSH to customer 254712345678 as a business payment with remarks 'Refund for Order #456'"

The MCP will:

1. Validate initiator credentials
2. Process B2C payment request
3. Return transaction details

---

## 🔄 Updates & Maintenance

### Updating the MCP Server

```bash
# Update to latest version
npm update -g @meshark/safaricom-daraja-mcp

# Verify version
mcp-daraja --version
```

### Version Compatibility

- **Node.js**: 18.0+
- **MCP SDK**: 0.5.0+
- **Claude Desktop**: Latest version recommended
- **VS Code**: 1.102+

---

## 💝 Support This Project

If this MCP integration guide has been helpful, consider supporting the project:

### ☕ Buy Me a Coffee

[![Buy Me A Coffee](https://img.shields.io/badge/Buy%20Me%20A%20Coffee-Support-orange?style=for-the-badge&logo=buy-me-a-coffee)](https://buymeacoffee.com/meshhack)

### 📱 M-Pesa (Kenya)

For local supporters in Kenya:  
**📞 +254 702 152 220** (Meshack Musyoka)

_Your support helps maintain and improve this open-source MCP server! 🙏_

---

## 🔗 Additional Resources

- **npm Package**: https://www.npmjs.com/package/@meshark/safaricom-daraja-mcp
- **GitHub Repository**: https://github.com/Meshhack/safaricom-daraja-mcp
- **Safaricom Daraja API**: https://developer.safaricom.co.ke/
- **Model Context Protocol**: https://modelcontextprotocol.io/
- **Claude Desktop**: https://claude.ai/download

---

**Developed with ❤️ by [Meshack Musyoka](https://github.com/Meshhack) in Kenya 🇰🇪**
