<?php

declare(strict_types=1);

namespace MeshackMusyoka\SafaricomDarajaMcp\Server;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use MeshackMusyoka\SafaricomDarajaMcp\Types\DarajaConfig;
use MeshackMusyoka\SafaricomDarajaMcp\Client\DarajaClient;
use MeshackMusyoka\SafaricomDarajaMcp\Exceptions\DarajaException;

/**
 * MCP Server for Safaricom Daraja API
 */
class McpServer
{
    private Logger $logger;
    private DarajaClient $darajaClient;
    
    public function __construct()
    {
        // Initialize logger
        $this->logger = new Logger('daraja-mcp');
        $this->logger->pushHandler(new StreamHandler('php://stderr', Logger::INFO));
        
        // Load configuration from environment
        $config = new DarajaConfig(
            consumerKey: $_ENV['DARAJA_CONSUMER_KEY'] ?? '',
            consumerSecret: $_ENV['DARAJA_CONSUMER_SECRET'] ?? '',
            businessShortCode: $_ENV['DARAJA_BUSINESS_SHORT_CODE'] ?? '',
            passKey: $_ENV['DARAJA_PASS_KEY'] ?? '',
            environment: $_ENV['DARAJA_ENVIRONMENT'] ?? 'sandbox',
            initiatorName: $_ENV['DARAJA_INITIATOR_NAME'] ?? null,
            initiatorPassword: $_ENV['DARAJA_INITIATOR_PASSWORD'] ?? null
        );
        
        $this->darajaClient = new DarajaClient($config, $this->logger);
    }

    public function getTools(): array
    {
        return [
            [
                'name' => 'daraja_generate_token',
                'description' => 'Generate OAuth access token for Daraja API authentication',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [],
                    'required' => []
                ]
            ],
            [
                'name' => 'daraja_stk_push',
                'description' => 'Initiate STK Push (M-Pesa Express) payment request to customer phone',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'amount' => [
                            'type' => 'integer',
                            'description' => 'Payment amount (1-70000 KSH)',
                            'minimum' => 1,
                            'maximum' => 70000
                        ],
                        'phone_number' => [
                            'type' => 'string',
                            'description' => 'Customer phone number (254XXXXXXXX or 07XXXXXXXX)',
                            'pattern' => '^(?:254|\+254|0)?([17]\d{8})$'
                        ],
                        'callback_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'HTTPS URL to receive payment result callbacks'
                        ],
                        'account_reference' => [
                            'type' => 'string',
                            'description' => 'Account reference for the transaction (max 12 chars)',
                            'maxLength' => 12
                        ],
                        'transaction_desc' => [
                            'type' => 'string',
                            'description' => 'Transaction description (max 13 chars)',
                            'maxLength' => 13
                        ]
                    ],
                    'required' => ['amount', 'phone_number', 'callback_url', 'account_reference', 'transaction_desc']
                ]
            ],
            [
                'name' => 'daraja_stk_query',
                'description' => 'Query the status of an STK Push transaction',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'checkout_request_id' => [
                            'type' => 'string',
                            'description' => 'CheckoutRequestID from STK Push response'
                        ]
                    ],
                    'required' => ['checkout_request_id']
                ]
            ],
            [
                'name' => 'daraja_c2b_register',
                'description' => 'Register validation and confirmation URLs for C2B transactions',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'confirmation_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'HTTPS URL to receive payment confirmations'
                        ],
                        'validation_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'HTTPS URL for payment validation'
                        ],
                        'response_type' => [
                            'type' => 'string',
                            'enum' => ['Cancelled', 'Completed'],
                            'description' => 'Response type for validation',
                            'default' => 'Completed'
                        ]
                    ],
                    'required' => ['confirmation_url', 'validation_url']
                ]
            ],
            [
                'name' => 'daraja_c2b_simulate',
                'description' => 'Simulate C2B payment for testing (sandbox only)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'amount' => [
                            'type' => 'integer',
                            'description' => 'Payment amount',
                            'minimum' => 1
                        ],
                        'msisdn' => [
                            'type' => 'string',
                            'description' => 'Customer phone number',
                            'pattern' => '^(?:254|\+254|0)?([17]\d{8})$'
                        ],
                        'command_id' => [
                            'type' => 'string',
                            'enum' => ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'],
                            'description' => 'Transaction command ID',
                            'default' => 'CustomerPayBillOnline'
                        ],
                        'bill_ref_number' => [
                            'type' => 'string',
                            'description' => 'Bill reference number (optional)'
                        ]
                    ],
                    'required' => ['amount', 'msisdn']
                ]
            ],
            [
                'name' => 'daraja_b2c_payment',
                'description' => 'Send money from business to customer (B2C)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'amount' => [
                            'type' => 'integer',
                            'description' => 'Payment amount',
                            'minimum' => 1
                        ],
                        'party_b' => [
                            'type' => 'string',
                            'description' => 'Recipient phone number',
                            'pattern' => '^(?:254|\+254|0)?([17]\d{8})$'
                        ],
                        'command_id' => [
                            'type' => 'string',
                            'enum' => ['SalaryPayment', 'BusinessPayment', 'PromotionPayment'],
                            'description' => 'Payment command type',
                            'default' => 'BusinessPayment'
                        ],
                        'remarks' => [
                            'type' => 'string',
                            'description' => 'Payment remarks (max 100 chars)',
                            'maxLength' => 100
                        ],
                        'queue_timeout_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for timeout notifications'
                        ],
                        'result_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for result notifications'
                        ],
                        'occasion' => [
                            'type' => 'string',
                            'description' => 'Payment occasion (optional, max 100 chars)',
                            'maxLength' => 100
                        ]
                    ],
                    'required' => ['amount', 'party_b', 'remarks', 'queue_timeout_url', 'result_url']
                ]
            ],
            [
                'name' => 'daraja_b2b_payment',
                'description' => 'Transfer money between business accounts (B2B)',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'amount' => [
                            'type' => 'integer',
                            'description' => 'Transfer amount',
                            'minimum' => 1
                        ],
                        'party_b' => [
                            'type' => 'string',
                            'description' => 'Recipient business shortcode or till number'
                        ],
                        'command_id' => [
                            'type' => 'string',
                            'enum' => ['BusinessPayBill', 'BusinessBuyGoods', 'DisburseFundsToBusiness', 'BusinessToBusinessTransfer'],
                            'description' => 'Transfer command type',
                            'default' => 'BusinessPayBill'
                        ],
                        'remarks' => [
                            'type' => 'string',
                            'description' => 'Transfer remarks (max 100 chars)',
                            'maxLength' => 100
                        ],
                        'queue_timeout_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for timeout notifications'
                        ],
                        'result_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for result notifications'
                        ],
                        'account_reference' => [
                            'type' => 'string',
                            'description' => 'Account reference (max 12 chars)',
                            'maxLength' => 12
                        ]
                    ],
                    'required' => ['amount', 'party_b', 'remarks', 'queue_timeout_url', 'result_url', 'account_reference']
                ]
            ],
            [
                'name' => 'daraja_account_balance',
                'description' => 'Query M-Pesa account balance',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'identifier_type' => [
                            'type' => 'string',
                            'enum' => ['1', '2', '4'],
                            'description' => 'Identifier type (1=MSISDN, 2=Till, 4=Shortcode)',
                            'default' => '4'
                        ],
                        'remarks' => [
                            'type' => 'string',
                            'description' => 'Query remarks (max 100 chars)',
                            'maxLength' => 100
                        ],
                        'queue_timeout_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for timeout notifications'
                        ],
                        'result_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for result notifications'
                        ]
                    ],
                    'required' => ['remarks', 'queue_timeout_url', 'result_url']
                ]
            ],
            [
                'name' => 'daraja_transaction_status',
                'description' => 'Query the status of any Daraja transaction',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'transaction_id' => [
                            'type' => 'string',
                            'description' => 'Transaction ID to query'
                        ],
                        'identifier_type' => [
                            'type' => 'string',
                            'enum' => ['1', '2', '4'],
                            'description' => 'Identifier type (1=MSISDN, 2=Till, 4=Shortcode)',
                            'default' => '4'
                        ],
                        'result_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for result notifications'
                        ],
                        'queue_timeout_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for timeout notifications'
                        ],
                        'remarks' => [
                            'type' => 'string',
                            'description' => 'Query remarks (max 100 chars)',
                            'maxLength' => 100
                        ],
                        'occasion' => [
                            'type' => 'string',
                            'description' => 'Query occasion (optional, max 100 chars)',
                            'maxLength' => 100
                        ]
                    ],
                    'required' => ['transaction_id', 'result_url', 'queue_timeout_url', 'remarks']
                ]
            ],
            [
                'name' => 'daraja_reversal',
                'description' => 'Reverse a Daraja transaction',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'transaction_id' => [
                            'type' => 'string',
                            'description' => 'Transaction ID to reverse'
                        ],
                        'amount' => [
                            'type' => 'integer',
                            'description' => 'Amount to reverse',
                            'minimum' => 1
                        ],
                        'receiver_party' => [
                            'type' => 'string',
                            'description' => 'Party to receive the reversal'
                        ],
                        'receiver_identifier_type' => [
                            'type' => 'string',
                            'enum' => ['1', '2', '4', '11'],
                            'description' => 'Receiver identifier type',
                            'default' => '11'
                        ],
                        'result_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for result notifications'
                        ],
                        'queue_timeout_url' => [
                            'type' => 'string',
                            'format' => 'uri',
                            'description' => 'URL for timeout notifications'
                        ],
                        'remarks' => [
                            'type' => 'string',
                            'description' => 'Reversal remarks (max 100 chars)',
                            'maxLength' => 100
                        ],
                        'occasion' => [
                            'type' => 'string',
                            'description' => 'Reversal occasion (optional, max 100 chars)',
                            'maxLength' => 100
                        ]
                    ],
                    'required' => ['transaction_id', 'amount', 'receiver_party', 'result_url', 'queue_timeout_url', 'remarks']
                ]
            ],
            [
                'name' => 'daraja_generate_qr',
                'description' => 'Generate dynamic QR code for M-Pesa payments',
                'inputSchema' => [
                    'type' => 'object',
                    'properties' => [
                        'merchant_name' => [
                            'type' => 'string',
                            'description' => 'Merchant name (max 22 chars)',
                            'maxLength' => 22
                        ],
                        'ref_no' => [
                            'type' => 'string',
                            'description' => 'Reference number (max 12 chars)',
                            'maxLength' => 12
                        ],
                        'amount' => [
                            'type' => 'integer',
                            'description' => 'Payment amount',
                            'minimum' => 1
                        ],
                        'trx_code' => [
                            'type' => 'string',
                            'enum' => ['BG', 'WA', 'PB', 'SM'],
                            'description' => 'Transaction code (BG=BuyGoods, WA=Withdraw, PB=PayBill, SM=SendMoney)'
                        ],
                        'cpi' => [
                            'type' => 'string',
                            'description' => 'Consumer Price Index identifier'
                        ],
                        'size' => [
                            'type' => 'string',
                            'enum' => ['300'],
                            'description' => 'QR code size in pixels',
                            'default' => '300'
                        ]
                    ],
                    'required' => ['merchant_name', 'ref_no', 'amount', 'trx_code', 'cpi']
                ]
            ]
        ];
    }

    public function callTool(string $name, array $arguments): array
    {
        try {
            switch ($name) {
                case 'daraja_generate_token':
                    return $this->handleGenerateToken();
                
                case 'daraja_stk_push':
                    return $this->handleStkPush($arguments);
                
                case 'daraja_stk_query':
                    return $this->handleStkQuery($arguments);
                
                case 'daraja_c2b_register':
                    return $this->handleC2bRegister($arguments);
                
                case 'daraja_c2b_simulate':
                    return $this->handleC2bSimulate($arguments);
                
                case 'daraja_b2c_payment':
                    return $this->handleB2cPayment($arguments);
                
                case 'daraja_b2b_payment':
                    return $this->handleB2bPayment($arguments);
                
                case 'daraja_account_balance':
                    return $this->handleAccountBalance($arguments);
                
                case 'daraja_transaction_status':
                    return $this->handleTransactionStatus($arguments);
                
                case 'daraja_reversal':
                    return $this->handleReversal($arguments);
                
                case 'daraja_generate_qr':
                    return $this->handleGenerateQr($arguments);
                
                default:
                    return [
                        'content' => [['type' => 'text', 'text' => "❌ Unknown tool: {$name}"]],
                        'isError' => true
                    ];
            }
        } catch (DarajaException $e) {
            $this->logger->error('Daraja API error', [
                'tool' => $name,
                'error' => $e->getMessage(),
                'code' => $e->getDarajaCode(),
                'status' => $e->getHttpStatus()
            ]);
            
            return [
                'content' => [[
                    'type' => 'text',
                    'text' => "❌ Daraja API Error: {$e->getMessage()}\nCode: {$e->getDarajaCode()}\nStatus: {$e->getHttpStatus()}"
                ]],
                'isError' => true
            ];
        } catch (\Throwable $e) {
            $this->logger->error('Unexpected error', [
                'tool' => $name,
                'error' => $e->getMessage()
            ]);
            
            return [
                'content' => [[
                    'type' => 'text',
                    'text' => "❌ Unexpected error: {$e->getMessage()}"
                ]],
                'isError' => true
            ];
        }
    }

    private function handleGenerateToken(): array
    {
        $result = $this->darajaClient->generateToken();
        
        $expiresAt = (new \DateTime())->add(new \DateInterval('PT' . $result->expires_in . 'S'));
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "✅ Token generated successfully!\n\n📋 **Token Details:**\n- Access Token: {$result->access_token}\n- Expires In: {$result->expires_in} seconds\n- Valid Until: {$expiresAt->format('c')}\n\n⚠️ **Security Note:** Store this token securely and use it for subsequent API calls."
            ]]
        ];
    }

    private function handleStkPush(array $args): array
    {
        $result = $this->darajaClient->stkPush(
            amount: $args['amount'],
            phoneNumber: $args['phone_number'],
            callbackUrl: $args['callback_url'],
            accountReference: $args['account_reference'],
            transactionDesc: $args['transaction_desc']
        );
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "🚀 STK Push initiated successfully!\n\n📱 **Payment Request:**\n- Amount: KSH {$args['amount']}\n- Phone: {$args['phone_number']}\n- Reference: {$args['account_reference']}\n\n📋 **Response Details:**\n- Merchant Request ID: {$result->MerchantRequestID}\n- Checkout Request ID: {$result->CheckoutRequestID}\n- Response Code: {$result->ResponseCode}\n- Description: {$result->ResponseDescription}\n- Customer Message: {$result->CustomerMessage}\n\n⏳ Customer will receive a payment prompt on their phone. Use the Checkout Request ID to query payment status."
            ]]
        ];
    }

    private function handleStkQuery(array $args): array
    {
        $result = $this->darajaClient->stkQuery($args['checkout_request_id']);
        
        $statusEmoji = match($result->ResultCode) {
            '0' => '✅',
            '1032' => '❌',
            '1037' => '⏳',
            default => '❓'
        };
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "{$statusEmoji} STK Push Status Query Complete!\n\n📋 **Query Results:**\n- Merchant Request ID: {$result->MerchantRequestID}\n- Checkout Request ID: {$result->CheckoutRequestID}\n- Result Code: {$result->ResultCode}\n- Result Description: {$result->ResultDesc}\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n\n💡 **Status Interpretation:**\n- Code 0: Payment successful\n- Code 1032: Payment cancelled by user\n- Code 1037: Payment timeout\n- Other codes: Check Daraja documentation"
            ]]
        ];
    }

    private function handleC2bRegister(array $args): array
    {
        $result = $this->darajaClient->c2bRegister(
            confirmationUrl: $args['confirmation_url'],
            validationUrl: $args['validation_url'],
            responseType: $args['response_type'] ?? 'Completed'
        );
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "✅ C2B URLs registered successfully!\n\n🔗 **Registered URLs:**\n- Confirmation URL: {$args['confirmation_url']}\n- Validation URL: {$args['validation_url']}\n- Response Type: " . ($args['response_type'] ?? 'Completed') . "\n\n📋 **Response:**\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n\n✨ Your business can now receive C2B payment notifications at the registered URLs."
            ]]
        ];
    }

    private function handleC2bSimulate(array $args): array
    {
        $result = $this->darajaClient->c2bSimulate(
            amount: $args['amount'],
            msisdn: $args['msisdn'],
            commandId: $args['command_id'] ?? 'CustomerPayBillOnline',
            billRefNumber: $args['bill_ref_number'] ?? null
        );
        
        $billRef = isset($args['bill_ref_number']) ? "\n- Bill Reference: {$args['bill_ref_number']}" : '';
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "🧪 C2B Payment Simulated! (Sandbox Only)\n\n💰 **Simulated Payment:**\n- Amount: KSH {$args['amount']}\n- From: {$args['msisdn']}\n- Command: " . ($args['command_id'] ?? 'CustomerPayBillOnline') . "{$billRef}\n\n📋 **Response:**\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n- Conversation ID: {$result->ConversationID}\n- Originator Conversation ID: {$result->OriginatorConversationID}\n\n📡 Check your registered C2B URLs for the payment notification."
            ]]
        ];
    }

    private function handleB2cPayment(array $args): array
    {
        $result = $this->darajaClient->b2cPayment(
            amount: $args['amount'],
            partyB: $args['party_b'],
            commandId: $args['command_id'] ?? 'BusinessPayment',
            remarks: $args['remarks'],
            queueTimeoutUrl: $args['queue_timeout_url'],
            resultUrl: $args['result_url'],
            occasion: $args['occasion'] ?? null
        );
        
        $occasion = isset($args['occasion']) ? "\n- Occasion: {$args['occasion']}" : '';
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "💸 B2C Payment Request Submitted!\n\n💰 **Payment Details:**\n- Amount: KSH {$args['amount']}\n- Recipient: {$args['party_b']}\n- Type: " . ($args['command_id'] ?? 'BusinessPayment') . "\n- Remarks: {$args['remarks']}{$occasion}\n\n📋 **Response:**\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n- Conversation ID: {$result->ConversationID}\n- Originator Conversation ID: {$result->OriginatorConversationID}\n\n📡 Payment result will be sent to your callback URLs."
            ]]
        ];
    }

    private function handleB2bPayment(array $args): array
    {
        $result = $this->darajaClient->b2bPayment(
            amount: $args['amount'],
            partyB: $args['party_b'],
            commandId: $args['command_id'] ?? 'BusinessPayBill',
            remarks: $args['remarks'],
            queueTimeoutUrl: $args['queue_timeout_url'],
            resultUrl: $args['result_url'],
            accountReference: $args['account_reference']
        );
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "🏢 B2B Transfer Request Submitted!\n\n💰 **Transfer Details:**\n- Amount: KSH {$args['amount']}\n- To Business: {$args['party_b']}\n- Type: " . ($args['command_id'] ?? 'BusinessPayBill') . "\n- Account Reference: {$args['account_reference']}\n- Remarks: {$args['remarks']}\n\n📋 **Response:**\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n- Conversation ID: {$result->ConversationID}\n- Originator Conversation ID: {$result->OriginatorConversationID}\n\n📡 Transfer result will be sent to your callback URLs."
            ]]
        ];
    }

    private function handleAccountBalance(array $args): array
    {
        $result = $this->darajaClient->accountBalance(
            identifierType: $args['identifier_type'] ?? '4',
            remarks: $args['remarks'],
            queueTimeoutUrl: $args['queue_timeout_url'],
            resultUrl: $args['result_url']
        );
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "💰 Account Balance Query Submitted!\n\n📋 **Query Details:**\n- Identifier Type: " . ($args['identifier_type'] ?? '4') . "\n- Remarks: {$args['remarks']}\n\n📋 **Response:**\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n- Conversation ID: {$result->ConversationID}\n- Originator Conversation ID: {$result->OriginatorConversationID}\n\n📡 Balance information will be sent to your result URL."
            ]]
        ];
    }

    private function handleTransactionStatus(array $args): array
    {
        $result = $this->darajaClient->transactionStatus(
            transactionId: $args['transaction_id'],
            identifierType: $args['identifier_type'] ?? '4',
            resultUrl: $args['result_url'],
            queueTimeoutUrl: $args['queue_timeout_url'],
            remarks: $args['remarks'],
            occasion: $args['occasion'] ?? null
        );
        
        $occasion = isset($args['occasion']) ? "\n- Occasion: {$args['occasion']}" : '';
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "🔍 Transaction Status Query Submitted!\n\n📋 **Query Details:**\n- Transaction ID: {$args['transaction_id']}\n- Identifier Type: " . ($args['identifier_type'] ?? '4') . "\n- Remarks: {$args['remarks']}{$occasion}\n\n📋 **Response:**\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n- Conversation ID: {$result->ConversationID}\n- Originator Conversation ID: {$result->OriginatorConversationID}\n\n📡 Transaction status will be sent to your result URL."
            ]]
        ];
    }

    private function handleReversal(array $args): array
    {
        $result = $this->darajaClient->reverseTransaction(
            transactionId: $args['transaction_id'],
            amount: $args['amount'],
            receiverParty: $args['receiver_party'],
            receiverIdentifierType: $args['receiver_identifier_type'] ?? '11',
            resultUrl: $args['result_url'],
            queueTimeoutUrl: $args['queue_timeout_url'],
            remarks: $args['remarks'],
            occasion: $args['occasion'] ?? null
        );
        
        $occasion = isset($args['occasion']) ? "\n- Occasion: {$args['occasion']}" : '';
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "🔄 Transaction Reversal Request Submitted!\n\n📋 **Reversal Details:**\n- Transaction ID: {$args['transaction_id']}\n- Amount: KSH {$args['amount']}\n- Receiver: {$args['receiver_party']}\n- Receiver Type: " . ($args['receiver_identifier_type'] ?? '11') . "\n- Remarks: {$args['remarks']}{$occasion}\n\n📋 **Response:**\n- Response Code: {$result->ResponseCode}\n- Response Description: {$result->ResponseDescription}\n- Conversation ID: {$result->ConversationID}\n- Originator Conversation ID: {$result->OriginatorConversationID}\n\n📡 Reversal result will be sent to your result URL."
            ]]
        ];
    }

    private function handleGenerateQr(array $args): array
    {
        $result = $this->darajaClient->generateQr(
            merchantName: $args['merchant_name'],
            refNo: $args['ref_no'],
            amount: $args['amount'],
            trxCode: $args['trx_code'],
            cpi: $args['cpi'],
            size: $args['size'] ?? '300'
        );
        
        $qrCode = isset($result['QRCode']) ? "\n\n🔗 **QR Code Data:**\n```\n{$result['QRCode']}\n```" : '';
        
        return [
            'content' => [[
                'type' => 'text',
                'text' => "📱 QR Code Generated Successfully!\n\n📋 **QR Code Details:**\n- Merchant: {$args['merchant_name']}\n- Reference: {$args['ref_no']}\n- Amount: KSH {$args['amount']}\n- Transaction Code: {$args['trx_code']}\n- Size: " . ($args['size'] ?? '300') . "px\n\n📋 **Response:**\n- Response Code: " . ($result['ResponseCode'] ?? 'N/A') . "\n- Response Description: " . ($result['ResponseDescription'] ?? 'N/A') . "{$qrCode}\n\n💡 **Transaction Codes:**\n- BG: Buy Goods\n- WA: Withdraw Agent\n- PB: Pay Bill\n- SM: Send Money"
            ]]
        ];
    }

    public function run(): void
    {
        $this->logger->info('🚀 Safaricom Daraja MCP Server starting (PHP)');
        $this->logger->info('📋 Author: Meshack Musyoka');
        $this->logger->info('🌍 Environment: ' . ($_ENV['DARAJA_ENVIRONMENT'] ?? 'sandbox'));
        
        // Simple JSON-RPC over stdio implementation
        while (($line = fgets(STDIN)) !== false) {
            $line = trim($line);
            if (empty($line)) continue;
            
            try {
                $request = json_decode($line, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    continue;
                }
                
                $response = $this->handleRequest($request);
                echo json_encode($response) . "\n";
                
            } catch (\Throwable $e) {
                $this->logger->error('Request handling error', ['error' => $e->getMessage()]);
                
                $response = [
                    'jsonrpc' => '2.0',
                    'id' => $request['id'] ?? null,
                    'error' => [
                        'code' => -32603,
                        'message' => 'Internal error: ' . $e->getMessage()
                    ]
                ];
                echo json_encode($response) . "\n";
            }
        }
    }

    private function handleRequest(array $request): array
    {
        $method = $request['method'] ?? '';
        $params = $request['params'] ?? [];
        $id = $request['id'] ?? null;
        
        switch ($method) {
            case 'tools/list':
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => ['tools' => $this->getTools()]
                ];
            
            case 'tools/call':
                $toolName = $params['name'] ?? '';
                $arguments = $params['arguments'] ?? [];
                $result = $this->callTool($toolName, $arguments);
                
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => $result
                ];
            
            case 'initialize':
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'result' => [
                        'protocolVersion' => '2024-11-05',
                        'capabilities' => [
                            'tools' => []
                        ],
                        'serverInfo' => [
                            'name' => 'safaricom-daraja-mcp',
                            'version' => '1.0.0'
                        ]
                    ]
                ];
            
            default:
                return [
                    'jsonrpc' => '2.0',
                    'id' => $id,
                    'error' => [
                        'code' => -32601,
                        'message' => 'Method not found: ' . $method
                    ]
                ];
        }
    }
}