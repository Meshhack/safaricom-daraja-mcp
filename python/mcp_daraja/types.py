"""
Type definitions for Safaricom Daraja API MCP
Author: Meshack Musyoka
"""

from datetime import datetime
from enum import Enum
from typing import Optional, Literal
from pydantic import BaseModel, Field, validator


class Environment(str, Enum):
    SANDBOX = "sandbox"
    PRODUCTION = "production"


class TransactionType(str, Enum):
    CUSTOMER_PAY_BILL_ONLINE = "CustomerPayBillOnline"


class ResponseType(str, Enum):
    CANCELLED = "Cancelled"
    COMPLETED = "Completed"


class CommandID(str, Enum):
    # C2B Commands
    CUSTOMER_PAY_BILL_ONLINE = "CustomerPayBillOnline"
    CUSTOMER_BUY_GOODS_ONLINE = "CustomerBuyGoodsOnline"
    
    # B2C Commands
    SALARY_PAYMENT = "SalaryPayment"
    BUSINESS_PAYMENT = "BusinessPayment"
    PROMOTION_PAYMENT = "PromotionPayment"
    
    # B2B Commands
    BUSINESS_PAY_BILL = "BusinessPayBill"
    BUSINESS_BUY_GOODS = "BusinessBuyGoods"
    DISBURSE_FUNDS_TO_BUSINESS = "DisburseFundsToBusiness"
    BUSINESS_TO_BUSINESS_TRANSFER = "BusinessToBusinessTransfer"
    
    # Utility Commands
    ACCOUNT_BALANCE = "AccountBalance"
    TRANSACTION_STATUS_QUERY = "TransactionStatusQuery"
    TRANSACTION_REVERSAL = "TransactionReversal"


class IdentifierType(str, Enum):
    MSISDN = "1"
    TILL = "2"
    SHORTCODE = "4"
    ORGANIZATION = "11"


class TrxCode(str, Enum):
    BUY_GOODS = "BG"
    WITHDRAW_AGENT = "WA"
    PAY_BILL = "PB"
    SEND_MONEY = "SM"


class DarajaConfig(BaseModel):
    """Configuration for Daraja API client"""
    consumer_key: str = Field(..., description="Consumer key from Daraja portal")
    consumer_secret: str = Field(..., description="Consumer secret from Daraja portal")
    business_short_code: str = Field(..., description="Business shortcode")
    pass_key: str = Field(..., description="Pass key for STK Push")
    environment: Environment = Field(Environment.SANDBOX, description="API environment")
    initiator_name: Optional[str] = Field(None, description="Initiator name for B2C/B2B")
    initiator_password: Optional[str] = Field(None, description="Initiator password")


class DarajaUrls(BaseModel):
    """API URLs for different environments"""
    base: str
    oauth: str
    stk_push: str
    stk_query: str
    c2b_register: str
    c2b_simulate: str
    b2c: str
    b2b: str
    account_balance: str
    transaction_status: str
    reversal: str
    generate_qr: str


class TokenResponse(BaseModel):
    """OAuth token response"""
    access_token: str = Field(..., description="Access token")
    expires_in: str = Field(..., description="Token expiration time in seconds")


class STKPushRequest(BaseModel):
    """STK Push request payload"""
    BusinessShortCode: str
    Password: str
    Timestamp: str
    TransactionType: str
    Amount: int
    PartyA: str
    PartyB: str
    PhoneNumber: str
    CallBackURL: str
    AccountReference: str = Field(..., max_length=12)
    TransactionDesc: str = Field(..., max_length=13)


class STKPushResponse(BaseModel):
    """STK Push response"""
    MerchantRequestID: str
    CheckoutRequestID: str
    ResponseCode: str
    ResponseDescription: str
    CustomerMessage: str


class STKQueryRequest(BaseModel):
    """STK Query request payload"""
    BusinessShortCode: str
    Password: str
    Timestamp: str
    CheckoutRequestID: str


class STKQueryResponse(BaseModel):
    """STK Query response"""
    ResponseCode: str
    ResponseDescription: str
    MerchantRequestID: str
    CheckoutRequestID: str
    ResultCode: str
    ResultDesc: str


class C2BRegisterRequest(BaseModel):
    """C2B URL registration request"""
    ShortCode: str
    ResponseType: ResponseType
    ConfirmationURL: str = Field(..., description="URL for payment confirmations")
    ValidationURL: str = Field(..., description="URL for payment validation")


class C2BSimulateRequest(BaseModel):
    """C2B simulation request"""
    ShortCode: str
    CommandID: CommandID
    Amount: int
    Msisdn: str
    BillRefNumber: Optional[str] = None


class B2CRequest(BaseModel):
    """B2C payment request"""
    InitiatorName: str
    SecurityCredential: str
    CommandID: CommandID
    Amount: int
    PartyA: str
    PartyB: str
    Remarks: str = Field(..., max_length=100)
    QueueTimeOutURL: str
    ResultURL: str
    Occasion: Optional[str] = Field(None, max_length=100)


class B2BRequest(BaseModel):
    """B2B payment request"""
    InitiatorName: str
    SecurityCredential: str
    CommandID: CommandID
    Amount: int
    PartyA: str
    PartyB: str
    Remarks: str = Field(..., max_length=100)
    QueueTimeOutURL: str
    ResultURL: str
    AccountReference: str = Field(..., max_length=12)


class AccountBalanceRequest(BaseModel):
    """Account balance request"""
    InitiatorName: str
    SecurityCredential: str
    CommandID: CommandID
    PartyA: str
    IdentifierType: IdentifierType
    Remarks: str = Field(..., max_length=100)
    QueueTimeOutURL: str
    ResultURL: str


class TransactionStatusRequest(BaseModel):
    """Transaction status request"""
    InitiatorName: str
    SecurityCredential: str
    CommandID: CommandID
    TransactionID: str
    PartyA: str
    IdentifierType: IdentifierType
    ResultURL: str
    QueueTimeOutURL: str
    Remarks: str = Field(..., max_length=100)
    Occasion: Optional[str] = Field(None, max_length=100)


class ReversalRequest(BaseModel):
    """Transaction reversal request"""
    InitiatorName: str
    SecurityCredential: str
    CommandID: CommandID
    TransactionID: str
    Amount: int
    ReceiverParty: str
    RecieverIdentifierType: IdentifierType
    ResultURL: str
    QueueTimeOutURL: str
    Remarks: str = Field(..., max_length=100)
    Occasion: Optional[str] = Field(None, max_length=100)


class QRCodeRequest(BaseModel):
    """QR Code generation request"""
    MerchantName: str = Field(..., max_length=22)
    RefNo: str = Field(..., max_length=12)
    Amount: int
    TrxCode: TrxCode
    CPI: str
    Size: Literal["300"] = "300"


class DarajaResponse(BaseModel):
    """Generic Daraja API response"""
    ResponseCode: Optional[str] = None
    ResponseDescription: Optional[str] = None
    errorMessage: Optional[str] = None
    errorCode: Optional[str] = None
    ConversationID: Optional[str] = None
    OriginatorConversationID: Optional[str] = None


class DarajaError(Exception):
    """Custom exception for Daraja API errors"""
    
    def __init__(
        self,
        message: str,
        code: Optional[str] = None,
        status: Optional[int] = None,
        response: Optional[dict] = None
    ):
        super().__init__(message)
        self.code = code
        self.status = status
        self.response = response


# Input validation models for MCP tools
class PhoneNumber(str):
    """Kenyan phone number validator"""
    
    @classmethod
    def __get_validators__(cls):
        yield cls.validate
    
    @classmethod
    def validate(cls, v):
        if not isinstance(v, str):
            raise ValueError('Phone number must be a string')
        
        import re
        pattern = r'^(?:254|\+254|0)?([17]\d{8})$'
        if not re.match(pattern, v):
            raise ValueError('Invalid phone number format. Use: 254XXXXXXXX or 07XXXXXXXX')
        return v


class STKPushInput(BaseModel):
    """Input model for STK Push"""
    amount: int = Field(..., gt=0, le=70000, description="Payment amount (1-70000)")
    phone_number: str = Field(..., description="Customer phone number")
    callback_url: str = Field(..., description="HTTPS callback URL")
    account_reference: str = Field(..., max_length=12, description="Account reference")
    transaction_desc: str = Field(..., max_length=13, description="Transaction description")

    @validator('phone_number')
    def validate_phone_number(cls, v):
        return PhoneNumber.validate(v)


class STKQueryInput(BaseModel):
    """Input model for STK Query"""
    checkout_request_id: str = Field(..., description="Checkout request ID")


class C2BRegisterInput(BaseModel):
    """Input model for C2B Registration"""
    confirmation_url: str = Field(..., description="HTTPS confirmation URL")
    validation_url: str = Field(..., description="HTTPS validation URL")
    response_type: ResponseType = Field(ResponseType.COMPLETED, description="Response type")


class C2BSimulateInput(BaseModel):
    """Input model for C2B Simulation"""
    amount: int = Field(..., gt=0, description="Payment amount")
    msisdn: str = Field(..., description="Customer phone number")
    command_id: CommandID = Field(CommandID.CUSTOMER_PAY_BILL_ONLINE, description="Command ID")
    bill_ref_number: Optional[str] = Field(None, description="Bill reference number")

    @validator('msisdn')
    def validate_msisdn(cls, v):
        return PhoneNumber.validate(v)


class B2CPaymentInput(BaseModel):
    """Input model for B2C Payment"""
    amount: int = Field(..., gt=0, description="Payment amount")
    party_b: str = Field(..., description="Recipient phone number")
    command_id: CommandID = Field(CommandID.BUSINESS_PAYMENT, description="Command ID")
    remarks: str = Field(..., max_length=100, description="Payment remarks")
    queue_timeout_url: str = Field(..., description="Timeout URL")
    result_url: str = Field(..., description="Result URL")
    occasion: Optional[str] = Field(None, max_length=100, description="Payment occasion")

    @validator('party_b')
    def validate_party_b(cls, v):
        return PhoneNumber.validate(v)


class B2BPaymentInput(BaseModel):
    """Input model for B2B Payment"""
    amount: int = Field(..., gt=0, description="Transfer amount")
    party_b: str = Field(..., description="Recipient business code")
    command_id: CommandID = Field(CommandID.BUSINESS_PAY_BILL, description="Command ID")
    remarks: str = Field(..., max_length=100, description="Transfer remarks")
    queue_timeout_url: str = Field(..., description="Timeout URL")
    result_url: str = Field(..., description="Result URL")
    account_reference: str = Field(..., max_length=12, description="Account reference")


class AccountBalanceInput(BaseModel):
    """Input model for Account Balance"""
    identifier_type: IdentifierType = Field(IdentifierType.SHORTCODE, description="Identifier type")
    remarks: str = Field(..., max_length=100, description="Query remarks")
    queue_timeout_url: str = Field(..., description="Timeout URL")
    result_url: str = Field(..., description="Result URL")


class TransactionStatusInput(BaseModel):
    """Input model for Transaction Status"""
    transaction_id: str = Field(..., description="Transaction ID")
    identifier_type: IdentifierType = Field(IdentifierType.SHORTCODE, description="Identifier type")
    result_url: str = Field(..., description="Result URL")
    queue_timeout_url: str = Field(..., description="Timeout URL")
    remarks: str = Field(..., max_length=100, description="Query remarks")
    occasion: Optional[str] = Field(None, max_length=100, description="Query occasion")


class ReversalInput(BaseModel):
    """Input model for Transaction Reversal"""
    transaction_id: str = Field(..., description="Transaction ID")
    amount: int = Field(..., gt=0, description="Reversal amount")
    receiver_party: str = Field(..., description="Receiver party")
    receiver_identifier_type: IdentifierType = Field(IdentifierType.ORGANIZATION, description="Receiver type")
    result_url: str = Field(..., description="Result URL")
    queue_timeout_url: str = Field(..., description="Timeout URL")
    remarks: str = Field(..., max_length=100, description="Reversal remarks")
    occasion: Optional[str] = Field(None, max_length=100, description="Reversal occasion")


class GenerateQRInput(BaseModel):
    """Input model for QR Generation"""
    merchant_name: str = Field(..., max_length=22, description="Merchant name")
    ref_no: str = Field(..., max_length=12, description="Reference number")
    amount: int = Field(..., gt=0, description="Payment amount")
    trx_code: TrxCode = Field(..., description="Transaction code")
    cpi: str = Field(..., description="CPI identifier")
    size: Literal["300"] = Field("300", description="QR code size")