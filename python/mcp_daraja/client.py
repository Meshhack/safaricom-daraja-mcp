"""
Safaricom Daraja API Client - Python Implementation
Author: Meshack Musyoka
"""

import base64
import hashlib
import hmac
from datetime import datetime, timedelta
from typing import Optional, Dict, Any
import structlog
import httpx

from .types import (
    DarajaConfig, DarajaUrls, Environment, DarajaError,
    TokenResponse, STKPushRequest, STKPushResponse, STKQueryRequest, STKQueryResponse,
    C2BRegisterRequest, C2BSimulateRequest, B2CRequest, B2BRequest,
    AccountBalanceRequest, TransactionStatusRequest, ReversalRequest, QRCodeRequest,
    DarajaResponse, CommandID, IdentifierType, ResponseType, TransactionType
)

logger = structlog.get_logger(__name__)


class DarajaClient:
    """Safaricom Daraja API Client"""
    
    def __init__(self, config: DarajaConfig):
        self.config = config
        self.urls = self._get_urls()
        self.access_token: Optional[str] = None
        self.token_expiry: Optional[datetime] = None
        
        # Configure HTTP client
        self.client = httpx.AsyncClient(
            timeout=30.0,
            headers={
                "Content-Type": "application/json",
                "User-Agent": "Safaricom-Daraja-MCP-Python/1.0.0"
            }
        )
        
        logger.info(
            "Daraja client initialized",
            environment=config.environment,
            business_short_code=config.business_short_code
        )
    
    def _get_urls(self) -> DarajaUrls:
        """Get API URLs based on environment"""
        base = (
            "https://api.safaricom.co.ke" 
            if self.config.environment == Environment.PRODUCTION 
            else "https://sandbox.safaricom.co.ke"
        )
        
        return DarajaUrls(
            base=base,
            oauth=f"{base}/oauth/v1/generate?grant_type=client_credentials",
            stk_push=f"{base}/mpesa/stkpush/v1/processrequest",
            stk_query=f"{base}/mpesa/stkpushquery/v1/query",
            c2b_register=f"{base}/mpesa/c2b/v1/registerurl",
            c2b_simulate=f"{base}/mpesa/c2b/v1/simulate",
            b2c=f"{base}/mpesa/b2c/v1/paymentrequest",
            b2b=f"{base}/mpesa/b2b/v1/paymentrequest",
            account_balance=f"{base}/mpesa/accountbalance/v1/query",
            transaction_status=f"{base}/mpesa/transactionstatus/v1/query",
            reversal=f"{base}/mpesa/reversal/v1/request",
            generate_qr=f"{base}/mpesa/qrcode/v1/generate"
        )
    
    def _generate_password(self, timestamp: str) -> str:
        """Generate password for STK Push"""
        data = f"{self.config.business_short_code}{self.config.pass_key}{timestamp}"
        return base64.b64encode(data.encode()).decode()
    
    def _generate_timestamp(self) -> str:
        """Generate timestamp in format YYYYMMDDHHMMSS"""
        return datetime.now().strftime("%Y%m%d%H%M%S")
    
    def _generate_basic_auth(self) -> str:
        """Generate basic auth header"""
        credentials = f"{self.config.consumer_key}:{self.config.consumer_secret}"
        return base64.b64encode(credentials.encode()).decode()
    
    async def _handle_api_error(self, response: httpx.Response) -> None:
        """Handle API errors"""
        try:
            error_data = response.json()
        except:
            error_data = {}
        
        raise DarajaError(
            message=error_data.get('errorMessage', response.text or 'Unknown API error'),
            code=error_data.get('errorCode', f'HTTP_{response.status_code}'),
            status=response.status_code,
            response=error_data
        )
    
    async def _ensure_token(self) -> None:
        """Ensure we have a valid access token"""
        if (self.access_token and self.token_expiry and 
            datetime.now() < self.token_expiry - timedelta(seconds=60)):
            return
        
        await self.generate_token()
    
    async def generate_token(self) -> TokenResponse:
        """Generate OAuth access token"""
        logger.info("Generating access token")
        
        try:
            response = await self.client.get(
                self.urls.oauth,
                headers={"Authorization": f"Basic {self._generate_basic_auth()}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            token_data = TokenResponse(**response.json())
            self.access_token = token_data.access_token
            self.token_expiry = datetime.now() + timedelta(seconds=int(token_data.expires_in))
            
            logger.info(
                "Access token generated successfully",
                expires_at=self.token_expiry.isoformat()
            )
            
            return token_data
            
        except httpx.RequestError as e:
            logger.error("Network error during token generation", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def stk_push(
        self,
        amount: int,
        phone_number: str,
        callback_url: str,
        account_reference: str,
        transaction_desc: str
    ) -> STKPushResponse:
        """Initiate STK Push payment"""
        await self._ensure_token()
        
        timestamp = self._generate_timestamp()
        password = self._generate_password(timestamp)
        
        # Normalize phone number
        if phone_number.startswith('0'):
            phone_number = '254' + phone_number[1:]
        elif phone_number.startswith('+'):
            phone_number = phone_number[1:]
        
        payload = STKPushRequest(
            BusinessShortCode=self.config.business_short_code,
            Password=password,
            Timestamp=timestamp,
            TransactionType=TransactionType.CUSTOMER_PAY_BILL_ONLINE,
            Amount=amount,
            PartyA=phone_number,
            PartyB=self.config.business_short_code,
            PhoneNumber=phone_number,
            CallBackURL=callback_url,
            AccountReference=account_reference,
            TransactionDesc=transaction_desc
        )
        
        logger.info(
            "Initiating STK Push",
            amount=amount,
            phone_number=phone_number,
            account_reference=account_reference
        )
        
        try:
            response = await self.client.post(
                self.urls.stk_push,
                json=payload.dict(),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = STKPushResponse(**response.json())
            
            logger.info(
                "STK Push successful",
                checkout_request_id=result.CheckoutRequestID,
                response_code=result.ResponseCode
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during STK Push", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def stk_query(self, checkout_request_id: str) -> STKQueryResponse:
        """Query STK Push status"""
        await self._ensure_token()
        
        timestamp = self._generate_timestamp()
        password = self._generate_password(timestamp)
        
        payload = STKQueryRequest(
            BusinessShortCode=self.config.business_short_code,
            Password=password,
            Timestamp=timestamp,
            CheckoutRequestID=checkout_request_id
        )
        
        logger.info("Querying STK Push status", checkout_request_id=checkout_request_id)
        
        try:
            response = await self.client.post(
                self.urls.stk_query,
                json=payload.dict(),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = STKQueryResponse(**response.json())
            
            logger.info(
                "STK Query complete",
                result_code=result.ResultCode,
                result_desc=result.ResultDesc
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during STK Query", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def c2b_register(
        self,
        confirmation_url: str,
        validation_url: str,
        response_type: ResponseType = ResponseType.COMPLETED
    ) -> DarajaResponse:
        """Register C2B URLs"""
        await self._ensure_token()
        
        payload = C2BRegisterRequest(
            ShortCode=self.config.business_short_code,
            ResponseType=response_type,
            ConfirmationURL=confirmation_url,
            ValidationURL=validation_url
        )
        
        logger.info(
            "Registering C2B URLs",
            confirmation_url=confirmation_url,
            validation_url=validation_url
        )
        
        try:
            response = await self.client.post(
                self.urls.c2b_register,
                json=payload.dict(),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = DarajaResponse(**response.json())
            
            logger.info(
                "C2B URLs registered successfully",
                response_code=result.ResponseCode
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during C2B registration", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def c2b_simulate(
        self,
        amount: int,
        msisdn: str,
        command_id: CommandID = CommandID.CUSTOMER_PAY_BILL_ONLINE,
        bill_ref_number: Optional[str] = None
    ) -> DarajaResponse:
        """Simulate C2B payment (Sandbox only)"""
        if self.config.environment == Environment.PRODUCTION:
            raise DarajaError("C2B simulation is only available in sandbox environment")
        
        await self._ensure_token()
        
        # Normalize phone number
        if msisdn.startswith('0'):
            msisdn = '254' + msisdn[1:]
        elif msisdn.startswith('+'):
            msisdn = msisdn[1:]
        
        payload = C2BSimulateRequest(
            ShortCode=self.config.business_short_code,
            CommandID=command_id,
            Amount=amount,
            Msisdn=msisdn,
            BillRefNumber=bill_ref_number
        )
        
        logger.info(
            "Simulating C2B payment",
            amount=amount,
            msisdn=msisdn,
            command_id=command_id
        )
        
        try:
            response = await self.client.post(
                self.urls.c2b_simulate,
                json=payload.dict(exclude_none=True),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = DarajaResponse(**response.json())
            
            logger.info(
                "C2B simulation successful",
                conversation_id=result.ConversationID
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during C2B simulation", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def b2c_payment(
        self,
        amount: int,
        party_b: str,
        command_id: CommandID = CommandID.BUSINESS_PAYMENT,
        remarks: str = "",
        queue_timeout_url: str = "",
        result_url: str = "",
        occasion: Optional[str] = None
    ) -> DarajaResponse:
        """B2C Payment Request"""
        if not self.config.initiator_name or not self.config.initiator_password:
            raise DarajaError("Initiator credentials are required for B2C operations")
        
        await self._ensure_token()
        
        # Normalize phone number
        if party_b.startswith('0'):
            party_b = '254' + party_b[1:]
        elif party_b.startswith('+'):
            party_b = party_b[1:]
        
        payload = B2CRequest(
            InitiatorName=self.config.initiator_name,
            SecurityCredential=self.config.initiator_password,  # Should be encrypted in production
            CommandID=command_id,
            Amount=amount,
            PartyA=self.config.business_short_code,
            PartyB=party_b,
            Remarks=remarks,
            QueueTimeOutURL=queue_timeout_url,
            ResultURL=result_url,
            Occasion=occasion
        )
        
        logger.info(
            "Initiating B2C payment",
            amount=amount,
            party_b=party_b,
            command_id=command_id
        )
        
        try:
            response = await self.client.post(
                self.urls.b2c,
                json=payload.dict(exclude_none=True),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = DarajaResponse(**response.json())
            
            logger.info(
                "B2C payment initiated",
                conversation_id=result.ConversationID
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during B2C payment", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def b2b_payment(
        self,
        amount: int,
        party_b: str,
        command_id: CommandID = CommandID.BUSINESS_PAY_BILL,
        remarks: str = "",
        queue_timeout_url: str = "",
        result_url: str = "",
        account_reference: str = ""
    ) -> DarajaResponse:
        """B2B Payment Request"""
        if not self.config.initiator_name or not self.config.initiator_password:
            raise DarajaError("Initiator credentials are required for B2B operations")
        
        await self._ensure_token()
        
        payload = B2BRequest(
            InitiatorName=self.config.initiator_name,
            SecurityCredential=self.config.initiator_password,  # Should be encrypted in production
            CommandID=command_id,
            Amount=amount,
            PartyA=self.config.business_short_code,
            PartyB=party_b,
            Remarks=remarks,
            QueueTimeOutURL=queue_timeout_url,
            ResultURL=result_url,
            AccountReference=account_reference
        )
        
        logger.info(
            "Initiating B2B payment",
            amount=amount,
            party_b=party_b,
            command_id=command_id
        )
        
        try:
            response = await self.client.post(
                self.urls.b2b,
                json=payload.dict(),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = DarajaResponse(**response.json())
            
            logger.info(
                "B2B payment initiated",
                conversation_id=result.ConversationID
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during B2B payment", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def account_balance(
        self,
        identifier_type: IdentifierType = IdentifierType.SHORTCODE,
        remarks: str = "",
        queue_timeout_url: str = "",
        result_url: str = ""
    ) -> DarajaResponse:
        """Query Account Balance"""
        if not self.config.initiator_name or not self.config.initiator_password:
            raise DarajaError("Initiator credentials are required for balance inquiry")
        
        await self._ensure_token()
        
        payload = AccountBalanceRequest(
            InitiatorName=self.config.initiator_name,
            SecurityCredential=self.config.initiator_password,  # Should be encrypted in production
            CommandID=CommandID.ACCOUNT_BALANCE,
            PartyA=self.config.business_short_code,
            IdentifierType=identifier_type,
            Remarks=remarks,
            QueueTimeOutURL=queue_timeout_url,
            ResultURL=result_url
        )
        
        logger.info("Querying account balance")
        
        try:
            response = await self.client.post(
                self.urls.account_balance,
                json=payload.dict(),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = DarajaResponse(**response.json())
            
            logger.info(
                "Account balance query initiated",
                conversation_id=result.ConversationID
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during balance query", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def transaction_status(
        self,
        transaction_id: str,
        identifier_type: IdentifierType = IdentifierType.SHORTCODE,
        result_url: str = "",
        queue_timeout_url: str = "",
        remarks: str = "",
        occasion: Optional[str] = None
    ) -> DarajaResponse:
        """Query Transaction Status"""
        if not self.config.initiator_name or not self.config.initiator_password:
            raise DarajaError("Initiator credentials are required for transaction status query")
        
        await self._ensure_token()
        
        payload = TransactionStatusRequest(
            InitiatorName=self.config.initiator_name,
            SecurityCredential=self.config.initiator_password,  # Should be encrypted in production
            CommandID=CommandID.TRANSACTION_STATUS_QUERY,
            TransactionID=transaction_id,
            PartyA=self.config.business_short_code,
            IdentifierType=identifier_type,
            ResultURL=result_url,
            QueueTimeOutURL=queue_timeout_url,
            Remarks=remarks,
            Occasion=occasion
        )
        
        logger.info("Querying transaction status", transaction_id=transaction_id)
        
        try:
            response = await self.client.post(
                self.urls.transaction_status,
                json=payload.dict(exclude_none=True),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = DarajaResponse(**response.json())
            
            logger.info(
                "Transaction status query initiated",
                conversation_id=result.ConversationID
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during transaction status query", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def reverse_transaction(
        self,
        transaction_id: str,
        amount: int,
        receiver_party: str,
        receiver_identifier_type: IdentifierType = IdentifierType.ORGANIZATION,
        result_url: str = "",
        queue_timeout_url: str = "",
        remarks: str = "",
        occasion: Optional[str] = None
    ) -> DarajaResponse:
        """Reverse Transaction"""
        if not self.config.initiator_name or not self.config.initiator_password:
            raise DarajaError("Initiator credentials are required for transaction reversal")
        
        await self._ensure_token()
        
        payload = ReversalRequest(
            InitiatorName=self.config.initiator_name,
            SecurityCredential=self.config.initiator_password,  # Should be encrypted in production
            CommandID=CommandID.TRANSACTION_REVERSAL,
            TransactionID=transaction_id,
            Amount=amount,
            ReceiverParty=receiver_party,
            RecieverIdentifierType=receiver_identifier_type,
            ResultURL=result_url,
            QueueTimeOutURL=queue_timeout_url,
            Remarks=remarks,
            Occasion=occasion
        )
        
        logger.info(
            "Reversing transaction",
            transaction_id=transaction_id,
            amount=amount
        )
        
        try:
            response = await self.client.post(
                self.urls.reversal,
                json=payload.dict(exclude_none=True),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = DarajaResponse(**response.json())
            
            logger.info(
                "Transaction reversal initiated",
                conversation_id=result.ConversationID
            )
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during transaction reversal", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def generate_qr(
        self,
        merchant_name: str,
        ref_no: str,
        amount: int,
        trx_code: str,
        cpi: str,
        size: str = "300"
    ) -> Dict[str, Any]:
        """Generate Dynamic QR Code"""
        await self._ensure_token()
        
        payload = QRCodeRequest(
            MerchantName=merchant_name,
            RefNo=ref_no,
            Amount=amount,
            TrxCode=trx_code,
            CPI=cpi,
            Size=size
        )
        
        logger.info(
            "Generating QR code",
            merchant_name=merchant_name,
            amount=amount
        )
        
        try:
            response = await self.client.post(
                self.urls.generate_qr,
                json=payload.dict(),
                headers={"Authorization": f"Bearer {self.access_token}"}
            )
            
            if response.status_code != 200:
                await self._handle_api_error(response)
            
            result = response.json()
            
            logger.info("QR code generated successfully")
            
            return result
            
        except httpx.RequestError as e:
            logger.error("Network error during QR generation", error=str(e))
            raise DarajaError(f"Network error: {e}")
    
    async def close(self) -> None:
        """Close the HTTP client"""
        await self.client.aclose()
        logger.info("Daraja client closed")