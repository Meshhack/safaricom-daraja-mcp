#!/usr/bin/env node

/**
 * Safaricom Daraja API MCP Server
 * Author: Meshack Musyoka
 */

import { Server } from '@modelcontextprotocol/sdk/server/index.js';
import { StdioServerTransport } from '@modelcontextprotocol/sdk/server/stdio.js';
import {
  CallToolRequestSchema,
  ErrorCode,
  ListToolsRequestSchema,
  McpError
} from '@modelcontextprotocol/sdk/types.js';
import { DarajaClient } from './daraja-client.js';
import { DarajaConfig } from './types.js';
import {
  generateTokenSchema,
  stkPushSchema,
  stkQuerySchema,
  c2bRegisterSchema,
  c2bSimulateSchema,
  b2cPaymentSchema,
  b2bPaymentSchema,
  accountBalanceSchema,
  transactionStatusSchema,
  reversalSchema,
  generateQRSchema,
  GenerateTokenInput,
  STKPushInput,
  STKQueryInput,
  C2BRegisterInput,
  C2BSimulateInput,
  B2CPaymentInput,
  B2BPaymentInput,
  AccountBalanceInput,
  TransactionStatusInput,
  ReversalInput,
  GenerateQRInput
} from './schemas.js';
import dotenv from 'dotenv';

// Load environment variables
dotenv.config();

class DarajaMCPServer {
  private server: Server;
  private darajaClient: DarajaClient;

  constructor() {
    this.server = new Server(
      {
        name: 'safaricom-daraja-mcp',
        version: '1.0.0'
      },
      {
        capabilities: {
          tools: {}
        }
      }
    );

    // Initialize Daraja client
    const config: DarajaConfig = {
      consumer_key: process.env.DARAJA_CONSUMER_KEY || '',
      consumer_secret: process.env.DARAJA_CONSUMER_SECRET || '',
      business_short_code: process.env.DARAJA_BUSINESS_SHORT_CODE || '',
      pass_key: process.env.DARAJA_PASS_KEY || '',
      environment: (process.env.DARAJA_ENVIRONMENT as 'sandbox' | 'production') || 'sandbox',
      initiator_name: process.env.DARAJA_INITIATOR_NAME,
      initiator_password: process.env.DARAJA_INITIATOR_PASSWORD
    };

    this.darajaClient = new DarajaClient(config);
    this.setupHandlers();
  }

  private setupHandlers(): void {
    this.server.setRequestHandler(ListToolsRequestSchema, async () => {
      return {
        tools: [
          {
            name: 'daraja_generate_token',
            description: 'Generate OAuth access token for Daraja API authentication',
            inputSchema: {
              type: 'object',
              properties: {},
              required: []
            }
          },
          {
            name: 'daraja_stk_push',
            description: 'Initiate STK Push (M-Pesa Express) payment request to customer phone',
            inputSchema: {
              type: 'object',
              properties: {
                amount: {
                  type: 'number',
                  description: 'Payment amount (1-70000 KSH)',
                  minimum: 1,
                  maximum: 70000
                },
                phone_number: {
                  type: 'string',
                  description: 'Customer phone number (254XXXXXXXX or 07XXXXXXXX)',
                  pattern: '^(?:254|\\+254|0)?([17]\\d{8})$'
                },
                callback_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'HTTPS URL to receive payment result callbacks'
                },
                account_reference: {
                  type: 'string',
                  description: 'Account reference for the transaction (max 12 chars)',
                  maxLength: 12
                },
                transaction_desc: {
                  type: 'string',
                  description: 'Transaction description (max 13 chars)',
                  maxLength: 13
                }
              },
              required: ['amount', 'phone_number', 'callback_url', 'account_reference', 'transaction_desc']
            }
          },
          {
            name: 'daraja_stk_query',
            description: 'Query the status of an STK Push transaction',
            inputSchema: {
              type: 'object',
              properties: {
                checkout_request_id: {
                  type: 'string',
                  description: 'CheckoutRequestID from STK Push response'
                }
              },
              required: ['checkout_request_id']
            }
          },
          {
            name: 'daraja_c2b_register',
            description: 'Register validation and confirmation URLs for C2B transactions',
            inputSchema: {
              type: 'object',
              properties: {
                confirmation_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'HTTPS URL to receive payment confirmations'
                },
                validation_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'HTTPS URL for payment validation'
                },
                response_type: {
                  type: 'string',
                  enum: ['Cancelled', 'Completed'],
                  description: 'Response type for validation',
                  default: 'Completed'
                }
              },
              required: ['confirmation_url', 'validation_url']
            }
          },
          {
            name: 'daraja_c2b_simulate',
            description: 'Simulate C2B payment for testing (sandbox only)',
            inputSchema: {
              type: 'object',
              properties: {
                amount: {
                  type: 'number',
                  description: 'Payment amount',
                  minimum: 1
                },
                msisdn: {
                  type: 'string',
                  description: 'Customer phone number',
                  pattern: '^(?:254|\\+254|0)?([17]\\d{8})$'
                },
                command_id: {
                  type: 'string',
                  enum: ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'],
                  description: 'Transaction command ID',
                  default: 'CustomerPayBillOnline'
                },
                bill_ref_number: {
                  type: 'string',
                  description: 'Bill reference number (optional)'
                }
              },
              required: ['amount', 'msisdn']
            }
          },
          {
            name: 'daraja_b2c_payment',
            description: 'Send money from business to customer (B2C)',
            inputSchema: {
              type: 'object',
              properties: {
                amount: {
                  type: 'number',
                  description: 'Payment amount',
                  minimum: 1
                },
                party_b: {
                  type: 'string',
                  description: 'Recipient phone number',
                  pattern: '^(?:254|\\+254|0)?([17]\\d{8})$'
                },
                command_id: {
                  type: 'string',
                  enum: ['SalaryPayment', 'BusinessPayment', 'PromotionPayment'],
                  description: 'Payment command type',
                  default: 'BusinessPayment'
                },
                remarks: {
                  type: 'string',
                  description: 'Payment remarks (max 100 chars)',
                  maxLength: 100
                },
                queue_timeout_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for timeout notifications'
                },
                result_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for result notifications'
                },
                occasion: {
                  type: 'string',
                  description: 'Payment occasion (optional, max 100 chars)',
                  maxLength: 100
                }
              },
              required: ['amount', 'party_b', 'remarks', 'queue_timeout_url', 'result_url']
            }
          },
          {
            name: 'daraja_b2b_payment',
            description: 'Transfer money between business accounts (B2B)',
            inputSchema: {
              type: 'object',
              properties: {
                amount: {
                  type: 'number',
                  description: 'Transfer amount',
                  minimum: 1
                },
                party_b: {
                  type: 'string',
                  description: 'Recipient business shortcode or till number'
                },
                command_id: {
                  type: 'string',
                  enum: ['BusinessPayBill', 'BusinessBuyGoods', 'DisburseFundsToBusiness', 'BusinessToBusinessTransfer'],
                  description: 'Transfer command type',
                  default: 'BusinessPayBill'
                },
                remarks: {
                  type: 'string',
                  description: 'Transfer remarks (max 100 chars)',
                  maxLength: 100
                },
                queue_timeout_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for timeout notifications'
                },
                result_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for result notifications'
                },
                account_reference: {
                  type: 'string',
                  description: 'Account reference (max 12 chars)',
                  maxLength: 12
                }
              },
              required: ['amount', 'party_b', 'remarks', 'queue_timeout_url', 'result_url', 'account_reference']
            }
          },
          {
            name: 'daraja_account_balance',
            description: 'Query M-Pesa account balance',
            inputSchema: {
              type: 'object',
              properties: {
                identifier_type: {
                  type: 'string',
                  enum: ['1', '2', '4'],
                  description: 'Identifier type (1=MSISDN, 2=Till, 4=Shortcode)',
                  default: '4'
                },
                remarks: {
                  type: 'string',
                  description: 'Query remarks (max 100 chars)',
                  maxLength: 100
                },
                queue_timeout_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for timeout notifications'
                },
                result_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for result notifications'
                }
              },
              required: ['remarks', 'queue_timeout_url', 'result_url']
            }
          },
          {
            name: 'daraja_transaction_status',
            description: 'Query the status of any Daraja transaction',
            inputSchema: {
              type: 'object',
              properties: {
                transaction_id: {
                  type: 'string',
                  description: 'Transaction ID to query'
                },
                identifier_type: {
                  type: 'string',
                  enum: ['1', '2', '4'],
                  description: 'Identifier type (1=MSISDN, 2=Till, 4=Shortcode)',
                  default: '4'
                },
                result_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for result notifications'
                },
                queue_timeout_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for timeout notifications'
                },
                remarks: {
                  type: 'string',
                  description: 'Query remarks (max 100 chars)',
                  maxLength: 100
                },
                occasion: {
                  type: 'string',
                  description: 'Query occasion (optional, max 100 chars)',
                  maxLength: 100
                }
              },
              required: ['transaction_id', 'result_url', 'queue_timeout_url', 'remarks']
            }
          },
          {
            name: 'daraja_reversal',
            description: 'Reverse a Daraja transaction',
            inputSchema: {
              type: 'object',
              properties: {
                transaction_id: {
                  type: 'string',
                  description: 'Transaction ID to reverse'
                },
                amount: {
                  type: 'number',
                  description: 'Amount to reverse',
                  minimum: 1
                },
                receiver_party: {
                  type: 'string',
                  description: 'Party to receive the reversal'
                },
                receiver_identifier_type: {
                  type: 'string',
                  enum: ['1', '2', '4', '11'],
                  description: 'Receiver identifier type',
                  default: '11'
                },
                result_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for result notifications'
                },
                queue_timeout_url: {
                  type: 'string',
                  format: 'uri',
                  description: 'URL for timeout notifications'
                },
                remarks: {
                  type: 'string',
                  description: 'Reversal remarks (max 100 chars)',
                  maxLength: 100
                },
                occasion: {
                  type: 'string',
                  description: 'Reversal occasion (optional, max 100 chars)',
                  maxLength: 100
                }
              },
              required: ['transaction_id', 'amount', 'receiver_party', 'result_url', 'queue_timeout_url', 'remarks']
            }
          },
          {
            name: 'daraja_generate_qr',
            description: 'Generate dynamic QR code for M-Pesa payments',
            inputSchema: {
              type: 'object',
              properties: {
                merchant_name: {
                  type: 'string',
                  description: 'Merchant name (max 22 chars)',
                  maxLength: 22
                },
                ref_no: {
                  type: 'string',
                  description: 'Reference number (max 12 chars)',
                  maxLength: 12
                },
                amount: {
                  type: 'number',
                  description: 'Payment amount',
                  minimum: 1
                },
                trx_code: {
                  type: 'string',
                  enum: ['BG', 'WA', 'PB', 'SM'],
                  description: 'Transaction code (BG=BuyGoods, WA=Withdraw, PB=PayBill, SM=SendMoney)'
                },
                cpi: {
                  type: 'string',
                  description: 'Consumer Price Index identifier'
                },
                size: {
                  type: 'string',
                  enum: ['300'],
                  description: 'QR code size in pixels',
                  default: '300'
                }
              },
              required: ['merchant_name', 'ref_no', 'amount', 'trx_code', 'cpi']
            }
          }
        ]
      };
    });

    this.server.setRequestHandler(CallToolRequestSchema, async (request) => {
      const { name, arguments: args } = request.params;

      try {
        switch (name) {
          case 'daraja_generate_token':
            return await this.handleGenerateToken(args);
          case 'daraja_stk_push':
            return await this.handleSTKPush(args);
          case 'daraja_stk_query':
            return await this.handleSTKQuery(args);
          case 'daraja_c2b_register':
            return await this.handleC2BRegister(args);
          case 'daraja_c2b_simulate':
            return await this.handleC2BSimulate(args);
          case 'daraja_b2c_payment':
            return await this.handleB2CPayment(args);
          case 'daraja_b2b_payment':
            return await this.handleB2BPayment(args);
          case 'daraja_account_balance':
            return await this.handleAccountBalance(args);
          case 'daraja_transaction_status':
            return await this.handleTransactionStatus(args);
          case 'daraja_reversal':
            return await this.handleReversal(args);
          case 'daraja_generate_qr':
            return await this.handleGenerateQR(args);
          default:
            throw new McpError(ErrorCode.MethodNotFound, `Unknown tool: ${name}`);
        }
      } catch (error) {
        if (error instanceof McpError) {
          throw error;
        }
        throw new McpError(ErrorCode.InternalError, `Tool execution failed: ${error}`);
      }
    });
  }

  private async handleGenerateToken(args: any) {
    const validatedArgs = generateTokenSchema.parse(args);
    const result = await this.darajaClient.generateToken();
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `✅ Token generated successfully!\n\n📋 **Token Details:**\n- Access Token: ${result.access_token}\n- Expires In: ${result.expires_in} seconds\n- Valid Until: ${new Date(Date.now() + parseInt(result.expires_in) * 1000).toISOString()}\n\n⚠️ **Security Note:** Store this token securely and use it for subsequent API calls.`
        }
      ]
    };
  }

  private async handleSTKPush(args: any) {
    const validatedArgs: STKPushInput = stkPushSchema.parse(args);
    const result = await this.darajaClient.stkPush(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `🚀 STK Push initiated successfully!\n\n📱 **Payment Request:**\n- Amount: KSH ${validatedArgs.amount}\n- Phone: ${validatedArgs.phone_number}\n- Reference: ${validatedArgs.account_reference}\n\n📋 **Response Details:**\n- Merchant Request ID: ${result.MerchantRequestID}\n- Checkout Request ID: ${result.CheckoutRequestID}\n- Response Code: ${result.ResponseCode}\n- Description: ${result.ResponseDescription}\n- Customer Message: ${result.CustomerMessage}\n\n⏳ Customer will receive a payment prompt on their phone. Use the Checkout Request ID to query payment status.`
        }
      ]
    };
  }

  private async handleSTKQuery(args: any) {
    const validatedArgs: STKQueryInput = stkQuerySchema.parse(args);
    const result = await this.darajaClient.stkQuery(validatedArgs.checkout_request_id);
    
    let statusEmoji = '❓';
    if (result.ResultCode === '0') statusEmoji = '✅';
    else if (result.ResultCode === '1032') statusEmoji = '❌';
    else if (result.ResultCode === '1037') statusEmoji = '⏳';

    return {
      content: [
        {
          type: 'text' as const,
          text: `${statusEmoji} STK Push Status Query Complete!\n\n📋 **Query Results:**\n- Merchant Request ID: ${result.MerchantRequestID}\n- Checkout Request ID: ${result.CheckoutRequestID}\n- Result Code: ${result.ResultCode}\n- Result Description: ${result.ResultDesc}\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n\n💡 **Status Interpretation:**\n- Code 0: Payment successful\n- Code 1032: Payment cancelled by user\n- Code 1037: Payment timeout\n- Other codes: Check Daraja documentation`
        }
      ]
    };
  }

  private async handleC2BRegister(args: any) {
    const validatedArgs: C2BRegisterInput = c2bRegisterSchema.parse(args);
    const result = await this.darajaClient.c2bRegister(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `✅ C2B URLs registered successfully!\n\n🔗 **Registered URLs:**\n- Confirmation URL: ${validatedArgs.confirmation_url}\n- Validation URL: ${validatedArgs.validation_url}\n- Response Type: ${validatedArgs.response_type}\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n\n✨ Your business can now receive C2B payment notifications at the registered URLs.`
        }
      ]
    };
  }

  private async handleC2BSimulate(args: any) {
    const validatedArgs: C2BSimulateInput = c2bSimulateSchema.parse(args);
    const result = await this.darajaClient.c2bSimulate(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `🧪 C2B Payment Simulated! (Sandbox Only)\n\n💰 **Simulated Payment:**\n- Amount: KSH ${validatedArgs.amount}\n- From: ${validatedArgs.msisdn}\n- Command: ${validatedArgs.command_id}\n${validatedArgs.bill_ref_number ? `- Bill Reference: ${validatedArgs.bill_ref_number}` : ''}\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n- Conversation ID: ${result.ConversationID}\n- Originator Conversation ID: ${result.OriginatorConversationID}\n\n📡 Check your registered C2B URLs for the payment notification.`
        }
      ]
    };
  }

  private async handleB2CPayment(args: any) {
    const validatedArgs: B2CPaymentInput = b2cPaymentSchema.parse(args);
    const result = await this.darajaClient.b2cPayment(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `💸 B2C Payment Request Submitted!\n\n💰 **Payment Details:**\n- Amount: KSH ${validatedArgs.amount}\n- Recipient: ${validatedArgs.party_b}\n- Type: ${validatedArgs.command_id}\n- Remarks: ${validatedArgs.remarks}\n${validatedArgs.occasion ? `- Occasion: ${validatedArgs.occasion}` : ''}\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n- Conversation ID: ${result.ConversationID}\n- Originator Conversation ID: ${result.OriginatorConversationID}\n\n📡 Payment result will be sent to your callback URLs.`
        }
      ]
    };
  }

  private async handleB2BPayment(args: any) {
    const validatedArgs: B2BPaymentInput = b2bPaymentSchema.parse(args);
    const result = await this.darajaClient.b2bPayment(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `🏢 B2B Transfer Request Submitted!\n\n💰 **Transfer Details:**\n- Amount: KSH ${validatedArgs.amount}\n- To Business: ${validatedArgs.party_b}\n- Type: ${validatedArgs.command_id}\n- Account Reference: ${validatedArgs.account_reference}\n- Remarks: ${validatedArgs.remarks}\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n- Conversation ID: ${result.ConversationID}\n- Originator Conversation ID: ${result.OriginatorConversationID}\n\n📡 Transfer result will be sent to your callback URLs.`
        }
      ]
    };
  }

  private async handleAccountBalance(args: any) {
    const validatedArgs: AccountBalanceInput = accountBalanceSchema.parse(args);
    const result = await this.darajaClient.accountBalance(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `💰 Account Balance Query Submitted!\n\n📋 **Query Details:**\n- Identifier Type: ${validatedArgs.identifier_type}\n- Remarks: ${validatedArgs.remarks}\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n- Conversation ID: ${result.ConversationID}\n- Originator Conversation ID: ${result.OriginatorConversationID}\n\n📡 Balance information will be sent to your result URL.`
        }
      ]
    };
  }

  private async handleTransactionStatus(args: any) {
    const validatedArgs: TransactionStatusInput = transactionStatusSchema.parse(args);
    const result = await this.darajaClient.transactionStatus(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `🔍 Transaction Status Query Submitted!\n\n📋 **Query Details:**\n- Transaction ID: ${validatedArgs.transaction_id}\n- Identifier Type: ${validatedArgs.identifier_type}\n- Remarks: ${validatedArgs.remarks}\n${validatedArgs.occasion ? `- Occasion: ${validatedArgs.occasion}` : ''}\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n- Conversation ID: ${result.ConversationID}\n- Originator Conversation ID: ${result.OriginatorConversationID}\n\n📡 Transaction status will be sent to your result URL.`
        }
      ]
    };
  }

  private async handleReversal(args: any) {
    const validatedArgs: ReversalInput = reversalSchema.parse(args);
    const result = await this.darajaClient.reverseTransaction(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `🔄 Transaction Reversal Request Submitted!\n\n📋 **Reversal Details:**\n- Transaction ID: ${validatedArgs.transaction_id}\n- Amount: KSH ${validatedArgs.amount}\n- Receiver: ${validatedArgs.receiver_party}\n- Receiver Type: ${validatedArgs.receiver_identifier_type}\n- Remarks: ${validatedArgs.remarks}\n${validatedArgs.occasion ? `- Occasion: ${validatedArgs.occasion}` : ''}\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n- Conversation ID: ${result.ConversationID}\n- Originator Conversation ID: ${result.OriginatorConversationID}\n\n📡 Reversal result will be sent to your result URL.`
        }
      ]
    };
  }

  private async handleGenerateQR(args: any) {
    const validatedArgs: GenerateQRInput = generateQRSchema.parse(args);
    const result = await this.darajaClient.generateQR(validatedArgs);
    
    return {
      content: [
        {
          type: 'text' as const,
          text: `📱 QR Code Generated Successfully!\n\n📋 **QR Code Details:**\n- Merchant: ${validatedArgs.merchant_name}\n- Reference: ${validatedArgs.ref_no}\n- Amount: KSH ${validatedArgs.amount}\n- Transaction Code: ${validatedArgs.trx_code}\n- Size: ${validatedArgs.size}px\n\n📋 **Response:**\n- Response Code: ${result.ResponseCode}\n- Response Description: ${result.ResponseDescription}\n\n${result.QRCode ? `🔗 **QR Code Data:**\n\`\`\`\n${result.QRCode}\n\`\`\`` : ''}\n\n💡 **Transaction Codes:**\n- BG: Buy Goods\n- WA: Withdraw Agent\n- PB: Pay Bill\n- SM: Send Money`
        }
      ]
    };
  }

  async run(): Promise<void> {
    const transport = new StdioServerTransport();
    await this.server.connect(transport);
    console.error('🚀 Safaricom Daraja MCP Server running...');
    console.error('📋 Author: Meshack Musyoka');
    console.error('🌍 Environment:', process.env.DARAJA_ENVIRONMENT || 'sandbox');
  }
}

// Start the server
const server = new DarajaMCPServer();
server.run().catch(console.error);