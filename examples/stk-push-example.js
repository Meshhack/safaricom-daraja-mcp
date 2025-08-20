/**
 * STK Push Payment Example - Node.js
 * Author: Meshack Musyoka
 * 
 * This example demonstrates how to initiate an STK Push payment
 * and handle the callback response.
 */

const express = require('express');
const { Client } = require('@modelcontextprotocol/sdk/client/index.js');
const { StdioClientTransport } = require('@modelcontextprotocol/sdk/client/stdio.js');

class STKPushExample {
    constructor() {
        this.app = express();
        this.app.use(express.json());
        this.setupRoutes();
        this.pendingPayments = new Map(); // In production, use a database
    }

    async initializeMCPClient() {
        // Initialize MCP client to communicate with Daraja server
        const transport = new StdioClientTransport({
            command: 'node',
            args: ['../nodejs/dist/index.js']
        });

        this.client = new Client(
            { name: 'stk-push-example', version: '1.0.0' },
            { capabilities: {} }
        );

        await this.client.connect(transport);
        console.log('✅ Connected to Daraja MCP server');
    }

    setupRoutes() {
        // Route to initiate STK Push payment
        this.app.post('/pay', async (req, res) => {
            try {
                const { amount, phoneNumber, reference, description } = req.body;
                
                // Validate input
                if (!amount || !phoneNumber || !reference || !description) {
                    return res.status(400).json({
                        error: 'Missing required fields: amount, phoneNumber, reference, description'
                    });
                }

                // Call Daraja MCP tool
                const result = await this.client.request({
                    method: 'tools/call',
                    params: {
                        name: 'daraja_stk_push',
                        arguments: {
                            amount: parseInt(amount),
                            phone_number: phoneNumber,
                            callback_url: `${req.protocol}://${req.get('host')}/callback`,
                            account_reference: reference,
                            transaction_desc: description
                        }
                    }
                });

                // Extract checkout request ID from response
                const responseText = result.content[0].text;
                const checkoutRequestId = this.extractCheckoutRequestId(responseText);
                
                // Store pending payment
                this.pendingPayments.set(checkoutRequestId, {
                    amount,
                    phoneNumber,
                    reference,
                    status: 'pending',
                    initiatedAt: new Date()
                });

                console.log(`💳 STK Push initiated: ${checkoutRequestId}`);
                
                res.json({
                    success: true,
                    message: 'STK Push initiated successfully',
                    checkoutRequestId,
                    instructions: 'Please check your phone and enter your M-Pesa PIN'
                });

            } catch (error) {
                console.error('❌ STK Push failed:', error.message);
                res.status(500).json({
                    error: 'Payment initiation failed',
                    details: error.message
                });
            }
        });

        // Callback route to handle M-Pesa response
        this.app.post('/callback', (req, res) => {
            try {
                console.log('📞 Received callback:', JSON.stringify(req.body, null, 2));
                
                const { Body } = req.body;
                if (Body && Body.stkCallback) {
                    this.handleStkCallback(Body.stkCallback);
                }

                // Always respond with success to acknowledge receipt
                res.json({
                    ResultCode: 0,
                    ResultDesc: "Callback received successfully"
                });

            } catch (error) {
                console.error('❌ Callback handling error:', error.message);
                res.json({
                    ResultCode: 1,
                    ResultDesc: "Callback handling failed"
                });
            }
        });

        // Route to check payment status
        this.app.get('/status/:checkoutRequestId', async (req, res) => {
            try {
                const { checkoutRequestId } = req.params;
                
                // Query payment status using MCP
                const result = await this.client.request({
                    method: 'tools/call',
                    params: {
                        name: 'daraja_stk_query',
                        arguments: {
                            checkout_request_id: checkoutRequestId
                        }
                    }
                });

                // Get stored payment info
                const paymentInfo = this.pendingPayments.get(checkoutRequestId);
                
                res.json({
                    checkoutRequestId,
                    paymentInfo,
                    queryResult: result.content[0].text
                });

            } catch (error) {
                console.error('❌ Status check failed:', error.message);
                res.status(500).json({
                    error: 'Status check failed',
                    details: error.message
                });
            }
        });

        // Route to list all payments
        this.app.get('/payments', (req, res) => {
            const payments = Array.from(this.pendingPayments.entries()).map(([id, payment]) => ({
                checkoutRequestId: id,
                ...payment
            }));

            res.json({ payments });
        });

        // Health check
        this.app.get('/health', (req, res) => {
            res.json({ 
                status: 'healthy', 
                timestamp: new Date().toISOString(),
                pendingPayments: this.pendingPayments.size
            });
        });
    }

    handleStkCallback(callback) {
        const { CheckoutRequestID, ResultCode, ResultDesc } = callback;
        
        console.log(`📊 Payment result for ${CheckoutRequestID}:`);
        console.log(`   Result Code: ${ResultCode}`);
        console.log(`   Result Description: ${ResultDesc}`);

        // Update payment status
        const payment = this.pendingPayments.get(CheckoutRequestID);
        if (payment) {
            payment.resultCode = ResultCode;
            payment.resultDesc = ResultDesc;
            payment.completedAt = new Date();

            if (ResultCode === 0) {
                // Payment successful
                payment.status = 'completed';
                console.log(`✅ Payment completed successfully: ${CheckoutRequestID}`);
                
                // Extract transaction details
                if (callback.CallbackMetadata && callback.CallbackMetadata.Item) {
                    const metadata = this.parseCallbackMetadata(callback.CallbackMetadata.Item);
                    payment.transactionId = metadata.MpesaReceiptNumber;
                    payment.transactionDate = metadata.TransactionDate;
                    payment.actualAmount = metadata.Amount;
                    
                    console.log(`   Transaction ID: ${payment.transactionId}`);
                    console.log(`   Amount: KSH ${payment.actualAmount}`);
                    console.log(`   Date: ${payment.transactionDate}`);
                }

                // Here you would typically:
                // 1. Update your database
                // 2. Send confirmation email/SMS
                // 3. Trigger order fulfillment
                // 4. Update user account balance
                this.onPaymentSuccess(CheckoutRequestID, payment);

            } else {
                // Payment failed or cancelled
                payment.status = 'failed';
                console.log(`❌ Payment failed: ${CheckoutRequestID} - ${ResultDesc}`);
                
                // Handle failure scenarios
                this.onPaymentFailure(CheckoutRequestID, payment, ResultCode);
            }
        } else {
            console.log(`⚠️ Received callback for unknown payment: ${CheckoutRequestID}`);
        }
    }

    parseCallbackMetadata(items) {
        const metadata = {};
        items.forEach(item => {
            metadata[item.Name] = item.Value;
        });
        return metadata;
    }

    extractCheckoutRequestId(responseText) {
        // Extract checkout request ID from MCP response text
        const match = responseText.match(/Checkout Request ID: ([\w]+)/);
        return match ? match[1] : null;
    }

    onPaymentSuccess(checkoutRequestId, payment) {
        console.log(`🎉 Processing successful payment: ${checkoutRequestId}`);
        
        // Example: Send success webhook to your application
        // await this.sendWebhook('payment.success', { checkoutRequestId, payment });
        
        // Example: Update user account
        // await this.creditUserAccount(payment.reference, payment.actualAmount);
        
        // Example: Send confirmation SMS/Email
        // await this.sendConfirmation(payment.phoneNumber, payment.transactionId);
    }

    onPaymentFailure(checkoutRequestId, payment, resultCode) {
        console.log(`💔 Processing failed payment: ${checkoutRequestId}`);
        
        // Handle different failure scenarios
        switch (resultCode) {
            case '1032':
                console.log('   Reason: User cancelled the payment');
                break;
            case '1037':
                console.log('   Reason: Payment timeout');
                break;
            case '1':
                console.log('   Reason: Insufficient balance');
                break;
            default:
                console.log(`   Reason: Unknown error (${resultCode})`);
        }

        // Example: Send failure webhook
        // await this.sendWebhook('payment.failed', { checkoutRequestId, payment, resultCode });
        
        // Example: Retry logic for timeout
        if (resultCode === '1037') {
            console.log('   Implementing retry logic for timeout...');
            // this.retryPayment(checkoutRequestId, payment);
        }
    }

    async start(port = 3000) {
        try {
            await this.initializeMCPClient();
            
            this.app.listen(port, () => {
                console.log(`🚀 STK Push Example Server running on port ${port}`);
                console.log(`\n📋 Available endpoints:`);
                console.log(`   POST /pay - Initiate STK Push payment`);
                console.log(`   POST /callback - M-Pesa callback handler`);
                console.log(`   GET /status/:id - Check payment status`);
                console.log(`   GET /payments - List all payments`);
                console.log(`   GET /health - Health check`);
                console.log(`\n💡 Example payment request:`);
                console.log(`   curl -X POST http://localhost:${port}/pay \\`);
                console.log(`     -H "Content-Type: application/json" \\`);
                console.log(`     -d '{`);
                console.log(`       "amount": 100,`);
                console.log(`       "phoneNumber": "254708374149",`);
                console.log(`       "reference": "ORDER123",`);
                console.log(`       "description": "Test Payment"`);
                console.log(`     }'`);
            });
        } catch (error) {
            console.error('❌ Failed to start server:', error.message);
            process.exit(1);
        }
    }
}

// Run the example
if (require.main === module) {
    const example = new STKPushExample();
    example.start(process.env.PORT || 3000);
}

module.exports = STKPushExample;