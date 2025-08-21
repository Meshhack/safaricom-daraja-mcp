#!/usr/bin/env python3
"""
Safaricom Daraja API MCP Server - Python Implementation
Author: Meshack Musyoka
"""

import os
import asyncio
from typing import Any, Dict
import structlog
from dotenv import load_dotenv
from mcp.server.fastmcp import FastMCP
from pydantic import ValidationError

try:
    from .client import DarajaClient
    from .types import (
        DarajaConfig, Environment, DarajaError,
        STKPushInput, STKQueryInput, C2BRegisterInput, C2BSimulateInput,
        B2CPaymentInput, B2BPaymentInput, AccountBalanceInput,
        TransactionStatusInput, ReversalInput, GenerateQRInput
    )
except ImportError:
    from mcp_daraja.client import DarajaClient
    from mcp_daraja.types import (
        DarajaConfig, Environment, DarajaError,
        STKPushInput, STKQueryInput, C2BRegisterInput, C2BSimulateInput,
        B2CPaymentInput, B2BPaymentInput, AccountBalanceInput,
        TransactionStatusInput, ReversalInput, GenerateQRInput
    )

# Load environment variables
load_dotenv()

# Configure logging
structlog.configure(
    processors=[
        structlog.stdlib.filter_by_level,
        structlog.stdlib.add_logger_name,
        structlog.stdlib.add_log_level,
        structlog.stdlib.PositionalArgumentsFormatter(),
        structlog.processors.TimeStamper(fmt="iso"),
        structlog.processors.StackInfoRenderer(),
        structlog.processors.format_exc_info,
        structlog.processors.UnicodeDecoder(),
        structlog.processors.JSONRenderer()
    ],
    context_class=dict,
    logger_factory=structlog.stdlib.LoggerFactory(),
    cache_logger_on_first_use=True,
)

logger = structlog.get_logger(__name__)

# Initialize FastMCP server
mcp = FastMCP("safaricom-daraja-mcp")


def get_daraja_client() -> DarajaClient:
    """Initialize and return Daraja client"""
    config = DarajaConfig(
        consumer_key=os.getenv("DARAJA_CONSUMER_KEY", ""),
        consumer_secret=os.getenv("DARAJA_CONSUMER_SECRET", ""),
        business_short_code=os.getenv("DARAJA_BUSINESS_SHORT_CODE", ""),
        pass_key=os.getenv("DARAJA_PASS_KEY", ""),
        environment=Environment(os.getenv("DARAJA_ENVIRONMENT", "sandbox")),
        initiator_name=os.getenv("DARAJA_INITIATOR_NAME"),
        initiator_password=os.getenv("DARAJA_INITIATOR_PASSWORD")
    )
    return DarajaClient(config)


@mcp.tool()
async def daraja_generate_token() -> str:
    """Generate OAuth access token for Daraja API authentication"""
    try:
        daraja_client = get_daraja_client()
        result = await daraja_client.generate_token()
        await daraja_client.close()
        
        return f"✅ Token generated successfully!\n\n📋 **Token Details:**\n- Access Token: {result.access_token}\n- Expires In: {result.expires_in} seconds\n- Valid Until: {result.access_token}\n\n⚠️ **Security Note:** Store this token securely and use it for subsequent API calls."
    except Exception as e:
        logger.error("Token generation failed", error=str(e))
        return f"❌ Token generation failed: {str(e)}"


@mcp.tool()
async def daraja_stk_push(
    amount: int,
    phone_number: str,
    callback_url: str,
    account_reference: str,
    transaction_desc: str
) -> str:
    """
    Initiate STK Push (M-Pesa Express) payment request to customer phone
    
    Args:
        amount: Payment amount (1-70000 KSH)
        phone_number: Customer phone number (254XXXXXXXX or 07XXXXXXXX)
        callback_url: HTTPS URL to receive payment result callbacks
        account_reference: Account reference for the transaction (max 12 chars)
        transaction_desc: Transaction description (max 13 chars)
    """
    try:
        validated_args = STKPushInput(
            amount=amount,
            phone_number=phone_number,
            callback_url=callback_url,
            account_reference=account_reference,
            transaction_desc=transaction_desc
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.stk_push(
            amount=validated_args.amount,
            phone_number=validated_args.phone_number,
            callback_url=validated_args.callback_url,
            account_reference=validated_args.account_reference,
            transaction_desc=validated_args.transaction_desc
        )
        await daraja_client.close()
        
        return f"🚀 STK Push initiated successfully!\n\n📱 **Payment Request:**\n- Amount: KSH {validated_args.amount}\n- Phone: {validated_args.phone_number}\n- Reference: {validated_args.account_reference}\n\n📋 **Response Details:**\n- Merchant Request ID: {result.MerchantRequestID}\n- Checkout Request ID: {result.CheckoutRequestID}\n- Response Code: {result.ResponseCode}\n- Description: {result.ResponseDescription}\n- Customer Message: {result.CustomerMessage}\n\n⏳ Customer will receive a payment prompt on their phone. Use the Checkout Request ID to query payment status."
    
    except ValidationError as e:
        logger.error("Input validation error", error=str(e))
        return f"❌ Input validation error: {str(e)}"
    except Exception as e:
        logger.error("STK Push failed", error=str(e))
        return f"❌ STK Push failed: {str(e)}"


@mcp.tool()
async def daraja_stk_query(checkout_request_id: str) -> str:
    """
    Query the status of an STK Push transaction
    
    Args:
        checkout_request_id: CheckoutRequestID from STK Push response
    """
    try:
        validated_args = STKQueryInput(checkout_request_id=checkout_request_id)
        
        daraja_client = get_daraja_client()
        result = await daraja_client.stk_query(validated_args.checkout_request_id)
        await daraja_client.close()
        
        status_emoji = "❓"
        if result.ResultCode == "0":
            status_emoji = "✅"
        elif result.ResultCode == "1032":
            status_emoji = "❌"
        elif result.ResultCode == "1037":
            status_emoji = "⏳"
        
        return f"{status_emoji} STK Push Status Query Complete!\n\n📋 **Query Results:**\n- Merchant Request ID: {result.MerchantRequestID}\n- Checkout Request ID: {result.CheckoutRequestID}\n- Result Code: {result.ResultCode}\n- Result Description: {result.ResultDesc}\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n\n💡 **Status Interpretation:**\n- Code 0: Payment successful\n- Code 1032: Payment cancelled by user\n- Code 1037: Payment timeout\n- Other codes: Check Daraja documentation"
    
    except Exception as e:
        logger.error("STK Query failed", error=str(e))
        return f"❌ STK Query failed: {str(e)}"


@mcp.tool()
async def daraja_c2b_register(
    confirmation_url: str,
    validation_url: str,
    response_type: str = "Completed"
) -> str:
    """
    Register validation and confirmation URLs for C2B transactions
    
    Args:
        confirmation_url: HTTPS URL to receive payment confirmations
        validation_url: HTTPS URL for payment validation
        response_type: Response type for validation (default: "Completed")
    """
    try:
        validated_args = C2BRegisterInput(
            confirmation_url=confirmation_url,
            validation_url=validation_url,
            response_type=response_type
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.c2b_register(
            confirmation_url=validated_args.confirmation_url,
            validation_url=validated_args.validation_url,
            response_type=validated_args.response_type
        )
        await daraja_client.close()
        
        return f"✅ C2B URLs registered successfully!\n\n🔗 **Registered URLs:**\n- Confirmation URL: {validated_args.confirmation_url}\n- Validation URL: {validated_args.validation_url}\n- Response Type: {validated_args.response_type}\n\n📋 **Response:**\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n\n✨ Your business can now receive C2B payment notifications at the registered URLs."
    
    except Exception as e:
        logger.error("C2B registration failed", error=str(e))
        return f"❌ C2B registration failed: {str(e)}"


@mcp.tool()
async def daraja_c2b_simulate(
    amount: int,
    msisdn: str,
    command_id: str = "CustomerPayBillOnline",
    bill_ref_number: str = None
) -> str:
    """
    Simulate C2B payment for testing (sandbox only)
    
    Args:
        amount: Payment amount
        msisdn: Customer phone number
        command_id: Transaction command ID (default: "CustomerPayBillOnline")
        bill_ref_number: Bill reference number (optional)
    """
    try:
        validated_args = C2BSimulateInput(
            amount=amount,
            msisdn=msisdn,
            command_id=command_id,
            bill_ref_number=bill_ref_number
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.c2b_simulate(
            amount=validated_args.amount,
            msisdn=validated_args.msisdn,
            command_id=validated_args.command_id,
            bill_ref_number=validated_args.bill_ref_number
        )
        await daraja_client.close()
        
        return f"🧪 C2B Payment Simulated! (Sandbox Only)\n\n💰 **Simulated Payment:**\n- Amount: KSH {validated_args.amount}\n- From: {validated_args.msisdn}\n- Command: {validated_args.command_id}\n{f'- Bill Reference: {validated_args.bill_ref_number}' if validated_args.bill_ref_number else ''}\n\n📋 **Response:**\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n- Conversation ID: {result.ConversationID}\n- Originator Conversation ID: {result.OriginatorConversationID}\n\n📡 Check your registered C2B URLs for the payment notification."
    
    except Exception as e:
        logger.error("C2B simulation failed", error=str(e))
        return f"❌ C2B simulation failed: {str(e)}"


@mcp.tool()
async def daraja_b2c_payment(
    amount: int,
    party_b: str,
    remarks: str,
    queue_timeout_url: str,
    result_url: str,
    command_id: str = "BusinessPayment",
    occasion: str = None
) -> str:
    """
    Send money from business to customer (B2C)
    
    Args:
        amount: Payment amount
        party_b: Recipient phone number
        remarks: Payment remarks (max 100 chars)
        queue_timeout_url: URL for timeout notifications
        result_url: URL for result notifications
        command_id: Payment command type (default: "BusinessPayment")
        occasion: Payment occasion (optional, max 100 chars)
    """
    try:
        validated_args = B2CPaymentInput(
            amount=amount,
            party_b=party_b,
            command_id=command_id,
            remarks=remarks,
            queue_timeout_url=queue_timeout_url,
            result_url=result_url,
            occasion=occasion
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.b2c_payment(
            amount=validated_args.amount,
            party_b=validated_args.party_b,
            command_id=validated_args.command_id,
            remarks=validated_args.remarks,
            queue_timeout_url=validated_args.queue_timeout_url,
            result_url=validated_args.result_url,
            occasion=validated_args.occasion
        )
        await daraja_client.close()
        
        return f"💸 B2C Payment Request Submitted!\n\n💰 **Payment Details:**\n- Amount: KSH {validated_args.amount}\n- Recipient: {validated_args.party_b}\n- Type: {validated_args.command_id}\n- Remarks: {validated_args.remarks}\n{f'- Occasion: {validated_args.occasion}' if validated_args.occasion else ''}\n\n📋 **Response:**\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n- Conversation ID: {result.ConversationID}\n- Originator Conversation ID: {result.OriginatorConversationID}\n\n📡 Payment result will be sent to your callback URLs."
    
    except Exception as e:
        logger.error("B2C payment failed", error=str(e))
        return f"❌ B2C payment failed: {str(e)}"


@mcp.tool()
async def daraja_b2b_payment(
    amount: int,
    party_b: str,
    remarks: str,
    queue_timeout_url: str,
    result_url: str,
    account_reference: str,
    command_id: str = "BusinessPayBill"
) -> str:
    """
    Transfer money between business accounts (B2B)
    
    Args:
        amount: Transfer amount
        party_b: Recipient business shortcode or till number
        remarks: Transfer remarks (max 100 chars)
        queue_timeout_url: URL for timeout notifications
        result_url: URL for result notifications
        account_reference: Account reference (max 12 chars)
        command_id: Transfer command type (default: "BusinessPayBill")
    """
    try:
        validated_args = B2BPaymentInput(
            amount=amount,
            party_b=party_b,
            command_id=command_id,
            remarks=remarks,
            queue_timeout_url=queue_timeout_url,
            result_url=result_url,
            account_reference=account_reference
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.b2b_payment(
            amount=validated_args.amount,
            party_b=validated_args.party_b,
            command_id=validated_args.command_id,
            remarks=validated_args.remarks,
            queue_timeout_url=validated_args.queue_timeout_url,
            result_url=validated_args.result_url,
            account_reference=validated_args.account_reference
        )
        await daraja_client.close()
        
        return f"🏢 B2B Transfer Request Submitted!\n\n💰 **Transfer Details:**\n- Amount: KSH {validated_args.amount}\n- To Business: {validated_args.party_b}\n- Type: {validated_args.command_id}\n- Account Reference: {validated_args.account_reference}\n- Remarks: {validated_args.remarks}\n\n📋 **Response:**\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n- Conversation ID: {result.ConversationID}\n- Originator Conversation ID: {result.OriginatorConversationID}\n\n📡 Transfer result will be sent to your callback URLs."
    
    except Exception as e:
        logger.error("B2B payment failed", error=str(e))
        return f"❌ B2B payment failed: {str(e)}"


@mcp.tool()
async def daraja_account_balance(
    remarks: str,
    queue_timeout_url: str,
    result_url: str,
    identifier_type: str = "4"
) -> str:
    """
    Query M-Pesa account balance
    
    Args:
        remarks: Query remarks (max 100 chars)
        queue_timeout_url: URL for timeout notifications
        result_url: URL for result notifications
        identifier_type: Identifier type (1=MSISDN, 2=Till, 4=Shortcode, default: "4")
    """
    try:
        validated_args = AccountBalanceInput(
            identifier_type=identifier_type,
            remarks=remarks,
            queue_timeout_url=queue_timeout_url,
            result_url=result_url
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.account_balance(
            identifier_type=validated_args.identifier_type,
            remarks=validated_args.remarks,
            queue_timeout_url=validated_args.queue_timeout_url,
            result_url=validated_args.result_url
        )
        await daraja_client.close()
        
        return f"💰 Account Balance Query Submitted!\n\n📋 **Query Details:**\n- Identifier Type: {validated_args.identifier_type}\n- Remarks: {validated_args.remarks}\n\n📋 **Response:**\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n- Conversation ID: {result.ConversationID}\n- Originator Conversation ID: {result.OriginatorConversationID}\n\n📡 Balance information will be sent to your result URL."
    
    except Exception as e:
        logger.error("Account balance query failed", error=str(e))
        return f"❌ Account balance query failed: {str(e)}"


@mcp.tool()
async def daraja_transaction_status(
    transaction_id: str,
    result_url: str,
    queue_timeout_url: str,
    remarks: str,
    identifier_type: str = "4",
    occasion: str = None
) -> str:
    """
    Query the status of any Daraja transaction
    
    Args:
        transaction_id: Transaction ID to query
        result_url: URL for result notifications
        queue_timeout_url: URL for timeout notifications
        remarks: Query remarks (max 100 chars)
        identifier_type: Identifier type (1=MSISDN, 2=Till, 4=Shortcode, default: "4")
        occasion: Query occasion (optional, max 100 chars)
    """
    try:
        validated_args = TransactionStatusInput(
            transaction_id=transaction_id,
            identifier_type=identifier_type,
            result_url=result_url,
            queue_timeout_url=queue_timeout_url,
            remarks=remarks,
            occasion=occasion
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.transaction_status(
            transaction_id=validated_args.transaction_id,
            identifier_type=validated_args.identifier_type,
            result_url=validated_args.result_url,
            queue_timeout_url=validated_args.queue_timeout_url,
            remarks=validated_args.remarks,
            occasion=validated_args.occasion
        )
        await daraja_client.close()
        
        return f"🔍 Transaction Status Query Submitted!\n\n📋 **Query Details:**\n- Transaction ID: {validated_args.transaction_id}\n- Identifier Type: {validated_args.identifier_type}\n- Remarks: {validated_args.remarks}\n{f'- Occasion: {validated_args.occasion}' if validated_args.occasion else ''}\n\n📋 **Response:**\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n- Conversation ID: {result.ConversationID}\n- Originator Conversation ID: {result.OriginatorConversationID}\n\n📡 Transaction status will be sent to your result URL."
    
    except Exception as e:
        logger.error("Transaction status query failed", error=str(e))
        return f"❌ Transaction status query failed: {str(e)}"


@mcp.tool()
async def daraja_reversal(
    transaction_id: str,
    amount: int,
    receiver_party: str,
    result_url: str,
    queue_timeout_url: str,
    remarks: str,
    receiver_identifier_type: str = "11",
    occasion: str = None
) -> str:
    """
    Reverse a Daraja transaction
    
    Args:
        transaction_id: Transaction ID to reverse
        amount: Amount to reverse
        receiver_party: Party to receive the reversal
        result_url: URL for result notifications
        queue_timeout_url: URL for timeout notifications
        remarks: Reversal remarks (max 100 chars)
        receiver_identifier_type: Receiver identifier type (default: "11")
        occasion: Reversal occasion (optional, max 100 chars)
    """
    try:
        validated_args = ReversalInput(
            transaction_id=transaction_id,
            amount=amount,
            receiver_party=receiver_party,
            receiver_identifier_type=receiver_identifier_type,
            result_url=result_url,
            queue_timeout_url=queue_timeout_url,
            remarks=remarks,
            occasion=occasion
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.reverse_transaction(
            transaction_id=validated_args.transaction_id,
            amount=validated_args.amount,
            receiver_party=validated_args.receiver_party,
            receiver_identifier_type=validated_args.receiver_identifier_type,
            result_url=validated_args.result_url,
            queue_timeout_url=validated_args.queue_timeout_url,
            remarks=validated_args.remarks,
            occasion=validated_args.occasion
        )
        await daraja_client.close()
        
        return f"🔄 Transaction Reversal Request Submitted!\n\n📋 **Reversal Details:**\n- Transaction ID: {validated_args.transaction_id}\n- Amount: KSH {validated_args.amount}\n- Receiver: {validated_args.receiver_party}\n- Receiver Type: {validated_args.receiver_identifier_type}\n- Remarks: {validated_args.remarks}\n{f'- Occasion: {validated_args.occasion}' if validated_args.occasion else ''}\n\n📋 **Response:**\n- Response Code: {result.ResponseCode}\n- Response Description: {result.ResponseDescription}\n- Conversation ID: {result.ConversationID}\n- Originator Conversation ID: {result.OriginatorConversationID}\n\n📡 Reversal result will be sent to your result URL."
    
    except Exception as e:
        logger.error("Transaction reversal failed", error=str(e))
        return f"❌ Transaction reversal failed: {str(e)}"


@mcp.tool()
async def daraja_generate_qr(
    merchant_name: str,
    ref_no: str,
    amount: int,
    trx_code: str,
    cpi: str,
    size: str = "300"
) -> str:
    """
    Generate dynamic QR code for M-Pesa payments
    
    Args:
        merchant_name: Merchant name (max 22 chars)
        ref_no: Reference number (max 12 chars)
        amount: Payment amount
        trx_code: Transaction code (BG=BuyGoods, WA=Withdraw, PB=PayBill, SM=SendMoney)
        cpi: Consumer Price Index identifier
        size: QR code size in pixels (default: "300")
    """
    try:
        validated_args = GenerateQRInput(
            merchant_name=merchant_name,
            ref_no=ref_no,
            amount=amount,
            trx_code=trx_code,
            cpi=cpi,
            size=size
        )
        
        daraja_client = get_daraja_client()
        result = await daraja_client.generate_qr(
            merchant_name=validated_args.merchant_name,
            ref_no=validated_args.ref_no,
            amount=validated_args.amount,
            trx_code=validated_args.trx_code,
            cpi=validated_args.cpi,
            size=validated_args.size
        )
        await daraja_client.close()
        
        qr_data = f"🔗 **QR Code Data:**\n```\n{result.get('QRCode', 'N/A')}\n```" if result.get('QRCode') else ""
        return f"📱 QR Code Generated Successfully!\n\n📋 **QR Code Details:**\n- Merchant: {validated_args.merchant_name}\n- Reference: {validated_args.ref_no}\n- Amount: KSH {validated_args.amount}\n- Transaction Code: {validated_args.trx_code}\n- Size: {validated_args.size}px\n\n📋 **Response:**\n- Response Code: {result.get('ResponseCode', 'N/A')}\n- Response Description: {result.get('ResponseDescription', 'N/A')}\n\n{qr_data}\n\n💡 **Transaction Codes:**\n- BG: Buy Goods\n- WA: Withdraw Agent\n- PB: Pay Bill\n- SM: Send Money"
    
    except Exception as e:
        logger.error("QR generation failed", error=str(e))
        return f"❌ QR generation failed: {str(e)}"


def main():
    """Run the MCP server"""
    logger.info("🚀 Starting Safaricom Daraja MCP Server (Python)")
    logger.info("📋 Author: Meshack Musyoka") 
    logger.info("🌍 Environment: %s", os.getenv("DARAJA_ENVIRONMENT", "sandbox"))
    
    # Run the FastMCP server with stdio transport
    mcp.run("stdio")


if __name__ == "__main__":
    main()