/**
 * Safaricom Daraja API Client
 * Author: Meshack Musyoka
 */

import axios, { AxiosInstance, AxiosError } from 'axios';
import * as crypto from 'crypto';
import {
  DarajaConfig,
  DarajaUrls,
  TokenResponse,
  STKPushRequest,
  STKPushResponse,
  STKQueryRequest,
  STKQueryResponse,
  C2BRegisterRequest,
  C2BSimulateRequest,
  B2CRequest,
  B2BRequest,
  AccountBalanceRequest,
  TransactionStatusRequest,
  ReversalRequest,
  QRCodeRequest,
  DarajaResponse,
  ApiError
} from './types.js';

export class DarajaClient {
  private config: DarajaConfig;
  private urls: DarajaUrls;
  private httpClient: AxiosInstance;
  private accessToken: string | null = null;
  private tokenExpiry: Date | null = null;

  constructor(config: DarajaConfig) {
    this.config = config;
    this.urls = this.getUrls();
    
    this.httpClient = axios.create({
      timeout: 30000,
      headers: {
        'Content-Type': 'application/json',
        'User-Agent': 'Safaricom-Daraja-MCP/1.0.0'
      }
    });

    // Request interceptor for authentication
    this.httpClient.interceptors.request.use(async (config) => {
      if (config.url?.includes('/oauth/') === false) {
        await this.ensureToken();
        config.headers.Authorization = `Bearer ${this.accessToken}`;
      }
      return config;
    });
  }

  private getUrls(): DarajaUrls {
    const base = this.config.environment === 'production' 
      ? 'https://api.safaricom.co.ke'
      : 'https://sandbox.safaricom.co.ke';

    return {
      base,
      oauth: `${base}/oauth/v1/generate?grant_type=client_credentials`,
      stk_push: `${base}/mpesa/stkpush/v1/processrequest`,
      stk_query: `${base}/mpesa/stkpushquery/v1/query`,
      c2b_register: `${base}/mpesa/c2b/v1/registerurl`,
      c2b_simulate: `${base}/mpesa/c2b/v1/simulate`,
      b2c: `${base}/mpesa/b2c/v1/paymentrequest`,
      b2b: `${base}/mpesa/b2b/v1/paymentrequest`,
      account_balance: `${base}/mpesa/accountbalance/v1/query`,
      transaction_status: `${base}/mpesa/transactionstatus/v1/query`,
      reversal: `${base}/mpesa/reversal/v1/request`,
      generate_qr: `${base}/mpesa/qrcode/v1/generate`
    };
  }

  private generatePassword(timestamp: string): string {
    const data = this.config.business_short_code + this.config.pass_key + timestamp;
    return Buffer.from(data).toString('base64');
  }

  private generateTimestamp(): string {
    return new Date().toISOString().replace(/[-:T.Z]/g, '').slice(0, 14);
  }

  private generateBasicAuth(): string {
    const credentials = `${this.config.consumer_key}:${this.config.consumer_secret}`;
    return Buffer.from(credentials).toString('base64');
  }

  private async handleApiError(error: AxiosError): Promise<never> {
    const apiError: ApiError = new Error('Daraja API Error');
    
    if (error.response) {
      apiError.status = error.response.status;
      const responseData = error.response.data as any;
      apiError.code = responseData?.errorCode || 'API_ERROR';
      apiError.message = responseData?.errorMessage || error.response.statusText;
      apiError.response = error.response.data;
    } else if (error.request) {
      apiError.code = 'NETWORK_ERROR';
      apiError.message = 'No response received from Daraja API';
    } else {
      apiError.code = 'REQUEST_ERROR';
      apiError.message = error.message;
    }

    throw apiError;
  }

  private async ensureToken(): Promise<void> {
    if (this.accessToken && this.tokenExpiry && new Date() < this.tokenExpiry) {
      return;
    }

    try {
      const response = await this.httpClient.get<TokenResponse>(this.urls.oauth, {
        headers: {
          Authorization: `Basic ${this.generateBasicAuth()}`
        }
      });

      this.accessToken = response.data.access_token;
      this.tokenExpiry = new Date(Date.now() + (parseInt(response.data.expires_in) * 1000));
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Generate OAuth access token
   */
  async generateToken(): Promise<TokenResponse> {
    try {
      const response = await this.httpClient.get<TokenResponse>(this.urls.oauth, {
        headers: {
          Authorization: `Basic ${this.generateBasicAuth()}`
        }
      });

      this.accessToken = response.data.access_token;
      this.tokenExpiry = new Date(Date.now() + (parseInt(response.data.expires_in) * 1000));
      
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Initiate STK Push (M-Pesa Express) payment
   */
  async stkPush(options: {
    amount: number;
    phone_number: string;
    callback_url: string;
    account_reference: string;
    transaction_desc: string;
  }): Promise<STKPushResponse> {
    const timestamp = this.generateTimestamp();
    const password = this.generatePassword(timestamp);

    const payload: STKPushRequest = {
      BusinessShortCode: this.config.business_short_code,
      Password: password,
      Timestamp: timestamp,
      TransactionType: 'CustomerPayBillOnline',
      Amount: options.amount,
      PartyA: options.phone_number,
      PartyB: this.config.business_short_code,
      PhoneNumber: options.phone_number,
      CallBackURL: options.callback_url,
      AccountReference: options.account_reference,
      TransactionDesc: options.transaction_desc
    };

    try {
      const response = await this.httpClient.post<STKPushResponse>(this.urls.stk_push, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Query STK Push transaction status
   */
  async stkQuery(checkout_request_id: string): Promise<STKQueryResponse> {
    const timestamp = this.generateTimestamp();
    const password = this.generatePassword(timestamp);

    const payload: STKQueryRequest = {
      BusinessShortCode: this.config.business_short_code,
      Password: password,
      Timestamp: timestamp,
      CheckoutRequestID: checkout_request_id
    };

    try {
      const response = await this.httpClient.post<STKQueryResponse>(this.urls.stk_query, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Register C2B URLs
   */
  async c2bRegister(options: {
    confirmation_url: string;
    validation_url: string;
    response_type?: 'Cancelled' | 'Completed';
  }): Promise<DarajaResponse> {
    const payload: C2BRegisterRequest = {
      ShortCode: this.config.business_short_code,
      ResponseType: options.response_type || 'Completed',
      ConfirmationURL: options.confirmation_url,
      ValidationURL: options.validation_url
    };

    try {
      const response = await this.httpClient.post<DarajaResponse>(this.urls.c2b_register, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Simulate C2B payment (Sandbox only)
   */
  async c2bSimulate(options: {
    amount: number;
    msisdn: string;
    command_id?: 'CustomerPayBillOnline' | 'CustomerBuyGoodsOnline';
    bill_ref_number?: string;
  }): Promise<DarajaResponse> {
    if (this.config.environment === 'production') {
      throw new Error('C2B simulation is only available in sandbox environment');
    }

    const payload: C2BSimulateRequest = {
      ShortCode: this.config.business_short_code,
      CommandID: options.command_id || 'CustomerPayBillOnline',
      Amount: options.amount,
      Msisdn: options.msisdn,
      BillRefNumber: options.bill_ref_number
    };

    try {
      const response = await this.httpClient.post<DarajaResponse>(this.urls.c2b_simulate, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * B2C Payment Request
   */
  async b2cPayment(options: {
    amount: number;
    party_b: string;
    command_id?: 'SalaryPayment' | 'BusinessPayment' | 'PromotionPayment';
    remarks: string;
    queue_timeout_url: string;
    result_url: string;
    occasion?: string;
  }): Promise<DarajaResponse> {
    if (!this.config.initiator_name || !this.config.initiator_password) {
      throw new Error('Initiator credentials are required for B2C operations');
    }

    const payload: B2CRequest = {
      InitiatorName: this.config.initiator_name,
      SecurityCredential: this.config.initiator_password, // Should be encrypted in production
      CommandID: options.command_id || 'BusinessPayment',
      Amount: options.amount,
      PartyA: this.config.business_short_code,
      PartyB: options.party_b,
      Remarks: options.remarks,
      QueueTimeOutURL: options.queue_timeout_url,
      ResultURL: options.result_url,
      Occasion: options.occasion
    };

    try {
      const response = await this.httpClient.post<DarajaResponse>(this.urls.b2c, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * B2B Payment Request
   */
  async b2bPayment(options: {
    amount: number;
    party_b: string;
    command_id?: 'BusinessPayBill' | 'BusinessBuyGoods' | 'DisburseFundsToBusiness' | 'BusinessToBusinessTransfer';
    remarks: string;
    queue_timeout_url: string;
    result_url: string;
    account_reference: string;
  }): Promise<DarajaResponse> {
    if (!this.config.initiator_name || !this.config.initiator_password) {
      throw new Error('Initiator credentials are required for B2B operations');
    }

    const payload: B2BRequest = {
      InitiatorName: this.config.initiator_name,
      SecurityCredential: this.config.initiator_password, // Should be encrypted in production
      CommandID: options.command_id || 'BusinessPayBill',
      Amount: options.amount,
      PartyA: this.config.business_short_code,
      PartyB: options.party_b,
      Remarks: options.remarks,
      QueueTimeOutURL: options.queue_timeout_url,
      ResultURL: options.result_url,
      AccountReference: options.account_reference
    };

    try {
      const response = await this.httpClient.post<DarajaResponse>(this.urls.b2b, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Query Account Balance
   */
  async accountBalance(options: {
    identifier_type?: '1' | '2' | '4';
    remarks: string;
    queue_timeout_url: string;
    result_url: string;
  }): Promise<DarajaResponse> {
    if (!this.config.initiator_name || !this.config.initiator_password) {
      throw new Error('Initiator credentials are required for balance inquiry');
    }

    const payload: AccountBalanceRequest = {
      InitiatorName: this.config.initiator_name,
      SecurityCredential: this.config.initiator_password, // Should be encrypted in production
      CommandID: 'AccountBalance',
      PartyA: this.config.business_short_code,
      IdentifierType: options.identifier_type || '4',
      Remarks: options.remarks,
      QueueTimeOutURL: options.queue_timeout_url,
      ResultURL: options.result_url
    };

    try {
      const response = await this.httpClient.post<DarajaResponse>(this.urls.account_balance, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Query Transaction Status
   */
  async transactionStatus(options: {
    transaction_id: string;
    identifier_type?: '1' | '2' | '4';
    result_url: string;
    queue_timeout_url: string;
    remarks: string;
    occasion?: string;
  }): Promise<DarajaResponse> {
    if (!this.config.initiator_name || !this.config.initiator_password) {
      throw new Error('Initiator credentials are required for transaction status query');
    }

    const payload: TransactionStatusRequest = {
      InitiatorName: this.config.initiator_name,
      SecurityCredential: this.config.initiator_password, // Should be encrypted in production
      CommandID: 'TransactionStatusQuery',
      TransactionID: options.transaction_id,
      PartyA: this.config.business_short_code,
      IdentifierType: options.identifier_type || '4',
      ResultURL: options.result_url,
      QueueTimeOutURL: options.queue_timeout_url,
      Remarks: options.remarks,
      Occasion: options.occasion
    };

    try {
      const response = await this.httpClient.post<DarajaResponse>(this.urls.transaction_status, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Reverse Transaction
   */
  async reverseTransaction(options: {
    transaction_id: string;
    amount: number;
    receiver_party: string;
    receiver_identifier_type?: '1' | '2' | '4' | '11';
    result_url: string;
    queue_timeout_url: string;
    remarks: string;
    occasion?: string;
  }): Promise<DarajaResponse> {
    if (!this.config.initiator_name || !this.config.initiator_password) {
      throw new Error('Initiator credentials are required for transaction reversal');
    }

    const payload: ReversalRequest = {
      InitiatorName: this.config.initiator_name,
      SecurityCredential: this.config.initiator_password, // Should be encrypted in production
      CommandID: 'TransactionReversal',
      TransactionID: options.transaction_id,
      Amount: options.amount,
      ReceiverParty: options.receiver_party,
      RecieverIdentifierType: options.receiver_identifier_type || '11',
      ResultURL: options.result_url,
      QueueTimeOutURL: options.queue_timeout_url,
      Remarks: options.remarks,
      Occasion: options.occasion
    };

    try {
      const response = await this.httpClient.post<DarajaResponse>(this.urls.reversal, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }

  /**
   * Generate Dynamic QR Code
   */
  async generateQR(options: {
    merchant_name: string;
    ref_no: string;
    amount: number;
    trx_code: 'BG' | 'WA' | 'PB' | 'SM';
    cpi: string;
    size?: '300';
  }): Promise<any> {
    const payload: QRCodeRequest = {
      MerchantName: options.merchant_name,
      RefNo: options.ref_no,
      Amount: options.amount,
      TrxCode: options.trx_code,
      CPI: options.cpi,
      Size: options.size || '300'
    };

    try {
      const response = await this.httpClient.post(this.urls.generate_qr, payload);
      return response.data;
    } catch (error) {
      return await this.handleApiError(error as AxiosError);
    }
  }
}