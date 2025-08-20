/**
 * TypeScript definitions for Safaricom Daraja API MCP
 * Author: Meshack Musyoka
 */

export interface DarajaConfig {
  consumer_key: string;
  consumer_secret: string;
  business_short_code: string;
  pass_key: string;
  environment: 'sandbox' | 'production';
  initiator_name?: string;
  initiator_password?: string;
}

export interface DarajaUrls {
  base: string;
  oauth: string;
  stk_push: string;
  stk_query: string;
  c2b_register: string;
  c2b_simulate: string;
  b2c: string;
  b2b: string;
  account_balance: string;
  transaction_status: string;
  reversal: string;
  generate_qr: string;
}

export interface TokenResponse {
  access_token: string;
  expires_in: string;
}

export interface STKPushRequest {
  BusinessShortCode: string;
  Password: string;
  Timestamp: string;
  TransactionType: string;
  Amount: number;
  PartyA: string;
  PartyB: string;
  PhoneNumber: string;
  CallBackURL: string;
  AccountReference: string;
  TransactionDesc: string;
}

export interface STKPushResponse {
  MerchantRequestID: string;
  CheckoutRequestID: string;
  ResponseCode: string;
  ResponseDescription: string;
  CustomerMessage: string;
}

export interface STKQueryRequest {
  BusinessShortCode: string;
  Password: string;
  Timestamp: string;
  CheckoutRequestID: string;
}

export interface STKQueryResponse {
  ResponseCode: string;
  ResponseDescription: string;
  MerchantRequestID: string;
  CheckoutRequestID: string;
  ResultCode: string;
  ResultDesc: string;
}

export interface C2BRegisterRequest {
  ShortCode: string;
  ResponseType: 'Cancelled' | 'Completed';
  ConfirmationURL: string;
  ValidationURL: string;
}

export interface C2BSimulateRequest {
  ShortCode: string;
  CommandID: 'CustomerPayBillOnline' | 'CustomerBuyGoodsOnline';
  Amount: number;
  Msisdn: string;
  BillRefNumber?: string;
}

export interface B2CRequest {
  InitiatorName: string;
  SecurityCredential: string;
  CommandID: 'SalaryPayment' | 'BusinessPayment' | 'PromotionPayment';
  Amount: number;
  PartyA: string;
  PartyB: string;
  Remarks: string;
  QueueTimeOutURL: string;
  ResultURL: string;
  Occasion?: string;
}

export interface B2BRequest {
  InitiatorName: string;
  SecurityCredential: string;
  CommandID: 'BusinessPayBill' | 'BusinessBuyGoods' | 'DisburseFundsToBusiness' | 'BusinessToBusinessTransfer';
  Amount: number;
  PartyA: string;
  PartyB: string;
  Remarks: string;
  QueueTimeOutURL: string;
  ResultURL: string;
  AccountReference: string;
}

export interface AccountBalanceRequest {
  InitiatorName: string;
  SecurityCredential: string;
  CommandID: 'AccountBalance';
  PartyA: string;
  IdentifierType: '1' | '2' | '4';
  Remarks: string;
  QueueTimeOutURL: string;
  ResultURL: string;
}

export interface TransactionStatusRequest {
  InitiatorName: string;
  SecurityCredential: string;
  CommandID: 'TransactionStatusQuery';
  TransactionID: string;
  PartyA: string;
  IdentifierType: '1' | '2' | '4';
  ResultURL: string;
  QueueTimeOutURL: string;
  Remarks: string;
  Occasion?: string;
}

export interface ReversalRequest {
  InitiatorName: string;
  SecurityCredential: string;
  CommandID: 'TransactionReversal';
  TransactionID: string;
  Amount: number;
  ReceiverParty: string;
  RecieverIdentifierType: '1' | '2' | '4' | '11';
  ResultURL: string;
  QueueTimeOutURL: string;
  Remarks: string;
  Occasion?: string;
}

export interface QRCodeRequest {
  MerchantName: string;
  RefNo: string;
  Amount: number;
  TrxCode: 'BG' | 'WA' | 'PB' | 'SM';
  CPI: string;
  Size?: '300';
}

export interface DarajaResponse {
  ResponseCode?: string;
  ResponseDescription?: string;
  errorMessage?: string;
  errorCode?: string;
  ConversationID?: string;
  OriginatorConversationID?: string;
}

export interface ApiError extends Error {
  code?: string;
  status?: number;
  response?: any;
}