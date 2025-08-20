"""
B2C Payment Example - Python
Author: Meshack Musyoka

This example demonstrates how to send money from business to customer
using the Daraja MCP server.
"""

import asyncio
import json
import logging
from datetime import datetime
from typing import Dict, List, Optional
from dataclasses import dataclass, asdict
from fastapi import FastAPI, HTTPException, BackgroundTasks
from pydantic import BaseModel
import httpx

# Configure logging
logging.basicConfig(level=logging.INFO)
logger = logging.getLogger(__name__)

@dataclass
class B2CPayment:
    """B2C Payment record"""
    payment_id: str
    recipient_phone: str
    amount: int
    command_id: str
    remarks: str
    occasion: Optional[str] = None
    status: str = 'pending'
    initiated_at: datetime = None
    conversation_id: Optional[str] = None
    originator_conversation_id: Optional[str] = None
    completed_at: Optional[datetime] = None
    result_code: Optional[str] = None
    result_description: Optional[str] = None

class PaymentRequest(BaseModel):
    """API request model for B2C payment"""
    recipient_phone: str
    amount: int
    command_id: str = 'BusinessPayment'
    remarks: str
    occasion: Optional[str] = None

class B2CPaymentExample:
    """B2C Payment Example Application"""
    
    def __init__(self):
        self.app = FastAPI(title="B2C Payment Example", version="1.0.0")
        self.payments: Dict[str, B2CPayment] = {}  # In production, use database
        self.setup_routes()
        
    def setup_routes(self):
        """Setup FastAPI routes"""
        
        @self.app.post("/send-money")
        async def send_money(payment: PaymentRequest, background_tasks: BackgroundTasks):
            """Initiate B2C payment"""
            try:
                payment_id = self.generate_payment_id()
                
                # Create payment record
                b2c_payment = B2CPayment(
                    payment_id=payment_id,
                    recipient_phone=payment.recipient_phone,
                    amount=payment.amount,
                    command_id=payment.command_id,
                    remarks=payment.remarks,
                    occasion=payment.occasion,
                    initiated_at=datetime.now()
                )
                
                # Store payment
                self.payments[payment_id] = b2c_payment
                
                # Process payment asynchronously
                background_tasks.add_task(self.process_b2c_payment, payment_id)
                
                logger.info(f"💸 B2C payment initiated: {payment_id}")
                
                return {
                    "success": True,
                    "payment_id": payment_id,
                    "message": "B2C payment initiated",
                    "recipient": payment.recipient_phone,
                    "amount": payment.amount
                }
                
            except Exception as e:
                logger.error(f"❌ B2C payment initiation failed: {e}")
                raise HTTPException(status_code=500, detail=str(e))
        
        @self.app.post("/result")
        async def handle_result(result_data: dict):
            """Handle Daraja result callback"""
            try:
                logger.info(f"📞 Received result callback: {json.dumps(result_data, indent=2)}")
                
                # Parse result data
                result = result_data.get('Result', {})
                conversation_id = result.get('ConversationID')
                originator_conversation_id = result.get('OriginatorConversationID')
                result_code = result.get('ResultCode')
                result_desc = result.get('ResultDesc')
                
                # Find payment by conversation ID
                payment = self.find_payment_by_conversation_id(conversation_id)
                if payment:
                    self.handle_payment_result(payment, result)
                else:
                    logger.warning(f"⚠️ No payment found for conversation ID: {conversation_id}")
                
                return {"ResultCode": 0, "ResultDesc": "Result received successfully"}
                
            except Exception as e:
                logger.error(f"❌ Result handling error: {e}")
                return {"ResultCode": 1, "ResultDesc": "Result handling failed"}
        
        @self.app.post("/timeout")
        async def handle_timeout(timeout_data: dict):
            """Handle Daraja timeout callback"""
            try:
                logger.info(f"⏱️ Received timeout callback: {json.dumps(timeout_data, indent=2)}")
                
                # Handle timeout
                conversation_id = timeout_data.get('ConversationID')
                payment = self.find_payment_by_conversation_id(conversation_id)
                
                if payment:
                    payment.status = 'timeout'
                    payment.completed_at = datetime.now()
                    logger.warning(f"⏱️ Payment timed out: {payment.payment_id}")
                
                return {"ResultCode": 0, "ResultDesc": "Timeout acknowledged"}
                
            except Exception as e:
                logger.error(f"❌ Timeout handling error: {e}")
                return {"ResultCode": 1, "ResultDesc": "Timeout handling failed"}
        
        @self.app.get("/payments")
        async def list_payments():
            """List all payments"""
            payments_list = [asdict(payment) for payment in self.payments.values()]
            return {"payments": payments_list}
        
        @self.app.get("/payments/{payment_id}")
        async def get_payment(payment_id: str):
            """Get specific payment details"""
            if payment_id not in self.payments:
                raise HTTPException(status_code=404, detail="Payment not found")
            
            payment = self.payments[payment_id]
            return asdict(payment)
        
        @self.app.get("/health")
        async def health_check():
            """Health check endpoint"""
            return {
                "status": "healthy",
                "timestamp": datetime.now().isoformat(),
                "total_payments": len(self.payments),
                "pending_payments": len([p for p in self.payments.values() if p.status == 'pending'])
            }

    async def process_b2c_payment(self, payment_id: str):
        """Process B2C payment using MCP client"""
        try:
            payment = self.payments[payment_id]
            
            # Prepare MCP request
            mcp_request = {
                "amount": payment.amount,
                "party_b": payment.recipient_phone,
                "command_id": payment.command_id,
                "remarks": payment.remarks,
                "queue_timeout_url": "http://localhost:8000/timeout",
                "result_url": "http://localhost:8000/result"
            }
            
            if payment.occasion:
                mcp_request["occasion"] = payment.occasion
            
            # Call Daraja MCP server
            result = await self.call_daraja_mcp("daraja_b2c_payment", mcp_request)
            
            # Parse response and update payment
            if result and "ConversationID" in result:
                payment.conversation_id = self.extract_conversation_id(result)
                payment.originator_conversation_id = self.extract_originator_conversation_id(result)
                logger.info(f"✅ B2C payment submitted: {payment_id} -> {payment.conversation_id}")
            else:
                payment.status = 'failed'
                logger.error(f"❌ B2C payment submission failed: {payment_id}")
                
        except Exception as e:
            payment.status = 'failed'
            logger.error(f"❌ B2C payment processing failed: {payment_id} - {e}")

    async def call_daraja_mcp(self, tool_name: str, arguments: dict) -> dict:
        """Call Daraja MCP server tool"""
        try:
            # In a real implementation, this would use the MCP SDK
            # For this example, we'll simulate the call
            
            # Example: Using subprocess to call Python MCP server
            import subprocess
            import json
            
            mcp_input = {
                "method": "tools/call",
                "params": {
                    "name": tool_name,
                    "arguments": arguments
                },
                "id": 1,
                "jsonrpc": "2.0"
            }
            
            # This is a simplified example - in production use proper MCP SDK
            logger.info(f"🔧 Calling MCP tool: {tool_name}")
            return {"ConversationID": "AG_20231201_1234567890", "ResponseCode": "0"}
            
        except Exception as e:
            logger.error(f"❌ MCP call failed: {e}")
            raise

    def handle_payment_result(self, payment: B2CPayment, result: dict):
        """Handle payment result from Daraja"""
        result_code = str(result.get('ResultCode', ''))
        result_desc = result.get('ResultDesc', '')
        
        payment.result_code = result_code
        payment.result_description = result_desc
        payment.completed_at = datetime.now()
        
        if result_code == '0':
            # Payment successful
            payment.status = 'completed'
            logger.info(f"✅ B2C payment completed: {payment.payment_id}")
            
            # Extract transaction details
            result_parameters = result.get('ResultParameters', {}).get('ResultParameter', [])
            transaction_details = self.parse_result_parameters(result_parameters)
            
            if transaction_details.get('TransactionReceipt'):
                logger.info(f"   Transaction Receipt: {transaction_details['TransactionReceipt']}")
                logger.info(f"   Transaction Amount: KSH {transaction_details.get('TransactionAmount', 'N/A')}")
                logger.info(f"   B2C Charges: KSH {transaction_details.get('B2CChargesPaidAccountAvailableFunds', 'N/A')}")
            
            # Trigger success actions
            self.on_payment_success(payment, transaction_details)
            
        else:
            # Payment failed
            payment.status = 'failed'
            logger.error(f"❌ B2C payment failed: {payment.payment_id} - {result_desc}")
            
            # Handle specific failure scenarios
            self.on_payment_failure(payment, result_code, result_desc)

    def parse_result_parameters(self, parameters: List[dict]) -> dict:
        """Parse result parameters from Daraja response"""
        parsed = {}
        for param in parameters:
            key = param.get('Key', '')
            value = param.get('Value', '')
            parsed[key] = value
        return parsed

    def find_payment_by_conversation_id(self, conversation_id: str) -> Optional[B2CPayment]:
        """Find payment by conversation ID"""
        for payment in self.payments.values():
            if payment.conversation_id == conversation_id:
                return payment
        return None

    def extract_conversation_id(self, response_text: str) -> str:
        """Extract conversation ID from MCP response"""
        # This would parse the actual MCP response format
        # For this example, return a mock ID
        return "AG_20231201_1234567890"

    def extract_originator_conversation_id(self, response_text: str) -> str:
        """Extract originator conversation ID from MCP response"""
        # This would parse the actual MCP response format
        return "29115-34620561-1"

    def generate_payment_id(self) -> str:
        """Generate unique payment ID"""
        from uuid import uuid4
        return f"B2C_{datetime.now().strftime('%Y%m%d_%H%M%S')}_{str(uuid4())[:8]}"

    def on_payment_success(self, payment: B2CPayment, transaction_details: dict):
        """Handle successful payment"""
        logger.info(f"🎉 Processing successful B2C payment: {payment.payment_id}")
        
        # Example: Send success notification
        self.send_success_notification(payment, transaction_details)
        
        # Example: Update accounting system
        # await self.update_accounting_system(payment, transaction_details)
        
        # Example: Send SMS confirmation
        # await self.send_sms_confirmation(payment.recipient_phone, transaction_details)

    def on_payment_failure(self, payment: B2CPayment, result_code: str, result_desc: str):
        """Handle failed payment"""
        logger.error(f"💔 Processing failed B2C payment: {payment.payment_id}")
        
        # Handle different failure scenarios
        if result_code == '2001':
            logger.info("   Reason: Invalid initiator information")
        elif result_code == '408':
            logger.info("   Reason: Request timeout")
        elif result_code == '500.001.1001':
            logger.info("   Reason: Invalid phone number")
        else:
            logger.info(f"   Reason: {result_desc} (Code: {result_code})")
        
        # Example: Send failure notification
        self.send_failure_notification(payment, result_code, result_desc)

    def send_success_notification(self, payment: B2CPayment, details: dict):
        """Send success notification (implement your notification logic)"""
        logger.info(f"📧 Sending success notification for payment: {payment.payment_id}")

    def send_failure_notification(self, payment: B2CPayment, code: str, desc: str):
        """Send failure notification (implement your notification logic)"""
        logger.info(f"📧 Sending failure notification for payment: {payment.payment_id}")

async def main():
    """Main function to run the example"""
    import uvicorn
    
    example = B2CPaymentExample()
    
    logger.info("🚀 Starting B2C Payment Example Server")
    logger.info("\n📋 Available endpoints:")
    logger.info("   POST /send-money - Initiate B2C payment")
    logger.info("   POST /result - Handle payment results")
    logger.info("   POST /timeout - Handle payment timeouts")
    logger.info("   GET /payments - List all payments")
    logger.info("   GET /payments/{id} - Get payment details")
    logger.info("   GET /health - Health check")
    
    logger.info("\n💡 Example B2C payment request:")
    logger.info('   curl -X POST http://localhost:8000/send-money \\')
    logger.info('     -H "Content-Type: application/json" \\')
    logger.info('     -d \'{')
    logger.info('       "recipient_phone": "254708374149",')
    logger.info('       "amount": 1000,')
    logger.info('       "command_id": "BusinessPayment",')
    logger.info('       "remarks": "Salary payment",')
    logger.info('       "occasion": "Monthly salary"')
    logger.info('     }\'')
    
    uvicorn.run(example.app, host="0.0.0.0", port=8000)

if __name__ == "__main__":
    asyncio.run(main())