<?php

declare(strict_types=1);

namespace MeshackMusyoka\SafaricomDarajaMcp\Client;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Exception\RequestException;
use Monolog\Logger;
use MeshackMusyoka\SafaricomDarajaMcp\Types\{
    DarajaConfig,
    ApiEndpoints,
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
    DarajaResponse
};
use MeshackMusyoka\SafaricomDarajaMcp\Exceptions\DarajaException;

/**
 * Safaricom Daraja API Client
 */
class DarajaClient
{
    private Client $httpClient;
    private ApiEndpoints $endpoints;
    private ?string $accessToken = null;
    private ?\DateTime $tokenExpiry = null;
    
    public function __construct(
        private readonly DarajaConfig $config,
        private readonly Logger $logger
    ) {
        $this->endpoints = new ApiEndpoints($config->environment);
        $this->httpClient = new Client([
            'timeout' => 30.0,
            'headers' => [
                'Content-Type' => 'application/json',
                'User-Agent' => 'Safaricom-Daraja-MCP-PHP/1.0.0'
            ]
        ]);
        
        $this->logger->info('Daraja client initialized', [
            'environment' => $config->environment,
            'business_short_code' => $config->businessShortCode
        ]);
    }

    private function generatePassword(string $timestamp): string
    {
        $data = $this->config->businessShortCode . $this->config->passKey . $timestamp;
        return base64_encode($data);
    }

    private function generateTimestamp(): string
    {
        return (new \DateTime())->format('YmdHis');
    }

    private function generateBasicAuth(): string
    {
        $credentials = $this->config->consumerKey . ':' . $this->config->consumerSecret;
        return base64_encode($credentials);
    }

    private function normalizePhoneNumber(string $phoneNumber): string
    {
        // Remove spaces and special characters
        $phoneNumber = preg_replace('/[^0-9+]/', '', $phoneNumber);
        
        // Handle different formats
        if (str_starts_with($phoneNumber, '0')) {
            return '254' . substr($phoneNumber, 1);
        }
        
        if (str_starts_with($phoneNumber, '+254')) {
            return substr($phoneNumber, 1);
        }
        
        if (str_starts_with($phoneNumber, '254')) {
            return $phoneNumber;
        }
        
        throw DarajaException::validationError('Invalid phone number format: ' . $phoneNumber);
    }

    private function validatePhoneNumber(string $phoneNumber): void
    {
        $pattern = '/^254[17]\d{8}$/';
        if (!preg_match($pattern, $phoneNumber)) {
            throw DarajaException::validationError('Invalid phone number format: ' . $phoneNumber);
        }
    }

    /**
     * @throws DarajaException
     */
    private function handleApiError(\Throwable $e): never
    {
        if ($e instanceof RequestException && $e->hasResponse()) {
            $response = $e->getResponse();
            $statusCode = $response->getStatusCode();
            
            try {
                $responseData = json_decode($response->getBody()->getContents(), true);
            } catch (\Throwable) {
                $responseData = [];
            }
            
            throw DarajaException::fromApiResponse($responseData, $statusCode);
        }
        
        throw DarajaException::networkError('Network error: ' . $e->getMessage(), $e);
    }

    /**
     * @throws DarajaException
     */
    private function ensureToken(): void
    {
        if ($this->accessToken && $this->tokenExpiry && new \DateTime() < $this->tokenExpiry->modify('-60 seconds')) {
            return;
        }
        
        $this->generateToken();
    }

    /**
     * Generate OAuth access token
     * 
     * @throws DarajaException
     */
    public function generateToken(): TokenResponse
    {
        $this->logger->info('Generating access token');
        
        try {
            $response = $this->httpClient->get($this->endpoints->getOAuthUrl(), [
                'headers' => [
                    'Authorization' => 'Basic ' . $this->generateBasicAuth()
                ]
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $tokenResponse = TokenResponse::fromArray($data);
            
            $this->accessToken = $tokenResponse->access_token;
            $this->tokenExpiry = (new \DateTime())->add(new \DateInterval('PT' . $tokenResponse->expires_in . 'S'));
            
            $this->logger->info('Access token generated successfully', [
                'expires_at' => $this->tokenExpiry->format(\DateTime::ISO8601)
            ]);
            
            return $tokenResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('Token generation failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Initiate STK Push payment
     * 
     * @throws DarajaException
     */
    public function stkPush(
        int $amount,
        string $phoneNumber,
        string $callbackUrl,
        string $accountReference,
        string $transactionDesc
    ): STKPushResponse {
        $this->ensureToken();
        
        $timestamp = $this->generateTimestamp();
        $password = $this->generatePassword($timestamp);
        $normalizedPhone = $this->normalizePhoneNumber($phoneNumber);
        $this->validatePhoneNumber($normalizedPhone);
        
        $request = new STKPushRequest(
            BusinessShortCode: $this->config->businessShortCode,
            Password: $password,
            Timestamp: $timestamp,
            TransactionType: 'CustomerPayBillOnline',
            Amount: $amount,
            PartyA: $normalizedPhone,
            PartyB: $this->config->businessShortCode,
            PhoneNumber: $normalizedPhone,
            CallBackURL: $callbackUrl,
            AccountReference: $accountReference,
            TransactionDesc: $transactionDesc
        );
        
        $this->logger->info('Initiating STK Push', [
            'amount' => $amount,
            'phone_number' => $normalizedPhone,
            'account_reference' => $accountReference
        ]);
        
        try {
            $response = $this->httpClient->post($this->endpoints->getStkPushUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => (array) $request
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $stkResponse = STKPushResponse::fromArray($data);
            
            $this->logger->info('STK Push successful', [
                'checkout_request_id' => $stkResponse->CheckoutRequestID,
                'response_code' => $stkResponse->ResponseCode
            ]);
            
            return $stkResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('STK Push failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Query STK Push status
     * 
     * @throws DarajaException
     */
    public function stkQuery(string $checkoutRequestId): STKQueryResponse
    {
        $this->ensureToken();
        
        $timestamp = $this->generateTimestamp();
        $password = $this->generatePassword($timestamp);
        
        $request = new STKQueryRequest(
            BusinessShortCode: $this->config->businessShortCode,
            Password: $password,
            Timestamp: $timestamp,
            CheckoutRequestID: $checkoutRequestId
        );
        
        $this->logger->info('Querying STK Push status', ['checkout_request_id' => $checkoutRequestId]);
        
        try {
            $response = $this->httpClient->post($this->endpoints->getStkQueryUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => (array) $request
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $queryResponse = STKQueryResponse::fromArray($data);
            
            $this->logger->info('STK Query complete', [
                'result_code' => $queryResponse->ResultCode,
                'result_desc' => $queryResponse->ResultDesc
            ]);
            
            return $queryResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('STK Query failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Register C2B URLs
     * 
     * @throws DarajaException
     */
    public function c2bRegister(
        string $confirmationUrl,
        string $validationUrl,
        string $responseType = 'Completed'
    ): DarajaResponse {
        $this->ensureToken();
        
        $request = new C2BRegisterRequest(
            ShortCode: $this->config->businessShortCode,
            ResponseType: $responseType,
            ConfirmationURL: $confirmationUrl,
            ValidationURL: $validationUrl
        );
        
        $this->logger->info('Registering C2B URLs', [
            'confirmation_url' => $confirmationUrl,
            'validation_url' => $validationUrl
        ]);
        
        try {
            $response = $this->httpClient->post($this->endpoints->getC2bRegisterUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => (array) $request
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $darajaResponse = DarajaResponse::fromArray($data);
            
            $this->logger->info('C2B URLs registered successfully', [
                'response_code' => $darajaResponse->ResponseCode
            ]);
            
            return $darajaResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('C2B registration failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Simulate C2B payment (Sandbox only)
     * 
     * @throws DarajaException
     */
    public function c2bSimulate(
        int $amount,
        string $msisdn,
        string $commandId = 'CustomerPayBillOnline',
        ?string $billRefNumber = null
    ): DarajaResponse {
        if ($this->config->isProduction()) {
            throw DarajaException::configurationError('C2B simulation is only available in sandbox environment');
        }
        
        $this->ensureToken();
        
        $normalizedMsisdn = $this->normalizePhoneNumber($msisdn);
        $this->validatePhoneNumber($normalizedMsisdn);
        
        $request = new C2BSimulateRequest(
            ShortCode: $this->config->businessShortCode,
            CommandID: $commandId,
            Amount: $amount,
            Msisdn: $normalizedMsisdn,
            BillRefNumber: $billRefNumber
        );
        
        $this->logger->info('Simulating C2B payment', [
            'amount' => $amount,
            'msisdn' => $normalizedMsisdn,
            'command_id' => $commandId
        ]);
        
        try {
            $requestData = (array) $request;
            if ($billRefNumber === null) {
                unset($requestData['BillRefNumber']);
            }
            
            $response = $this->httpClient->post($this->endpoints->getC2bSimulateUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => $requestData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $darajaResponse = DarajaResponse::fromArray($data);
            
            $this->logger->info('C2B simulation successful', [
                'conversation_id' => $darajaResponse->ConversationID
            ]);
            
            return $darajaResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('C2B simulation failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * B2C Payment Request
     * 
     * @throws DarajaException
     */
    public function b2cPayment(
        int $amount,
        string $partyB,
        string $commandId = 'BusinessPayment',
        string $remarks = '',
        string $queueTimeoutUrl = '',
        string $resultUrl = '',
        ?string $occasion = null
    ): DarajaResponse {
        if (!$this->config->hasInitiatorCredentials()) {
            throw DarajaException::configurationError('Initiator credentials are required for B2C operations');
        }
        
        $this->ensureToken();
        
        $normalizedPartyB = $this->normalizePhoneNumber($partyB);
        $this->validatePhoneNumber($normalizedPartyB);
        
        $request = new B2CRequest(
            InitiatorName: $this->config->initiatorName,
            SecurityCredential: $this->config->initiatorPassword, // Should be encrypted in production
            CommandID: $commandId,
            Amount: $amount,
            PartyA: $this->config->businessShortCode,
            PartyB: $normalizedPartyB,
            Remarks: $remarks,
            QueueTimeOutURL: $queueTimeoutUrl,
            ResultURL: $resultUrl,
            Occasion: $occasion
        );
        
        $this->logger->info('Initiating B2C payment', [
            'amount' => $amount,
            'party_b' => $normalizedPartyB,
            'command_id' => $commandId
        ]);
        
        try {
            $requestData = (array) $request;
            if ($occasion === null) {
                unset($requestData['Occasion']);
            }
            
            $response = $this->httpClient->post($this->endpoints->getB2cUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => $requestData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $darajaResponse = DarajaResponse::fromArray($data);
            
            $this->logger->info('B2C payment initiated', [
                'conversation_id' => $darajaResponse->ConversationID
            ]);
            
            return $darajaResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('B2C payment failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * B2B Payment Request
     * 
     * @throws DarajaException
     */
    public function b2bPayment(
        int $amount,
        string $partyB,
        string $commandId = 'BusinessPayBill',
        string $remarks = '',
        string $queueTimeoutUrl = '',
        string $resultUrl = '',
        string $accountReference = ''
    ): DarajaResponse {
        if (!$this->config->hasInitiatorCredentials()) {
            throw DarajaException::configurationError('Initiator credentials are required for B2B operations');
        }
        
        $this->ensureToken();
        
        $request = new B2BRequest(
            InitiatorName: $this->config->initiatorName,
            SecurityCredential: $this->config->initiatorPassword, // Should be encrypted in production
            CommandID: $commandId,
            Amount: $amount,
            PartyA: $this->config->businessShortCode,
            PartyB: $partyB,
            Remarks: $remarks,
            QueueTimeOutURL: $queueTimeoutUrl,
            ResultURL: $resultUrl,
            AccountReference: $accountReference
        );
        
        $this->logger->info('Initiating B2B payment', [
            'amount' => $amount,
            'party_b' => $partyB,
            'command_id' => $commandId
        ]);
        
        try {
            $response = $this->httpClient->post($this->endpoints->getB2bUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => (array) $request
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $darajaResponse = DarajaResponse::fromArray($data);
            
            $this->logger->info('B2B payment initiated', [
                'conversation_id' => $darajaResponse->ConversationID
            ]);
            
            return $darajaResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('B2B payment failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Query Account Balance
     * 
     * @throws DarajaException
     */
    public function accountBalance(
        string $identifierType = '4',
        string $remarks = '',
        string $queueTimeoutUrl = '',
        string $resultUrl = ''
    ): DarajaResponse {
        if (!$this->config->hasInitiatorCredentials()) {
            throw DarajaException::configurationError('Initiator credentials are required for balance inquiry');
        }
        
        $this->ensureToken();
        
        $request = new AccountBalanceRequest(
            InitiatorName: $this->config->initiatorName,
            SecurityCredential: $this->config->initiatorPassword, // Should be encrypted in production
            CommandID: 'AccountBalance',
            PartyA: $this->config->businessShortCode,
            IdentifierType: $identifierType,
            Remarks: $remarks,
            QueueTimeOutURL: $queueTimeoutUrl,
            ResultURL: $resultUrl
        );
        
        $this->logger->info('Querying account balance');
        
        try {
            $response = $this->httpClient->post($this->endpoints->getAccountBalanceUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => (array) $request
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $darajaResponse = DarajaResponse::fromArray($data);
            
            $this->logger->info('Account balance query initiated', [
                'conversation_id' => $darajaResponse->ConversationID
            ]);
            
            return $darajaResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('Account balance query failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Query Transaction Status
     * 
     * @throws DarajaException
     */
    public function transactionStatus(
        string $transactionId,
        string $identifierType = '4',
        string $resultUrl = '',
        string $queueTimeoutUrl = '',
        string $remarks = '',
        ?string $occasion = null
    ): DarajaResponse {
        if (!$this->config->hasInitiatorCredentials()) {
            throw DarajaException::configurationError('Initiator credentials are required for transaction status query');
        }
        
        $this->ensureToken();
        
        $request = new TransactionStatusRequest(
            InitiatorName: $this->config->initiatorName,
            SecurityCredential: $this->config->initiatorPassword, // Should be encrypted in production
            CommandID: 'TransactionStatusQuery',
            TransactionID: $transactionId,
            PartyA: $this->config->businessShortCode,
            IdentifierType: $identifierType,
            ResultURL: $resultUrl,
            QueueTimeOutURL: $queueTimeoutUrl,
            Remarks: $remarks,
            Occasion: $occasion
        );
        
        $this->logger->info('Querying transaction status', ['transaction_id' => $transactionId]);
        
        try {
            $requestData = (array) $request;
            if ($occasion === null) {
                unset($requestData['Occasion']);
            }
            
            $response = $this->httpClient->post($this->endpoints->getTransactionStatusUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => $requestData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $darajaResponse = DarajaResponse::fromArray($data);
            
            $this->logger->info('Transaction status query initiated', [
                'conversation_id' => $darajaResponse->ConversationID
            ]);
            
            return $darajaResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('Transaction status query failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Reverse Transaction
     * 
     * @throws DarajaException
     */
    public function reverseTransaction(
        string $transactionId,
        int $amount,
        string $receiverParty,
        string $receiverIdentifierType = '11',
        string $resultUrl = '',
        string $queueTimeoutUrl = '',
        string $remarks = '',
        ?string $occasion = null
    ): DarajaResponse {
        if (!$this->config->hasInitiatorCredentials()) {
            throw DarajaException::configurationError('Initiator credentials are required for transaction reversal');
        }
        
        $this->ensureToken();
        
        $request = new ReversalRequest(
            InitiatorName: $this->config->initiatorName,
            SecurityCredential: $this->config->initiatorPassword, // Should be encrypted in production
            CommandID: 'TransactionReversal',
            TransactionID: $transactionId,
            Amount: $amount,
            ReceiverParty: $receiverParty,
            RecieverIdentifierType: $receiverIdentifierType,
            ResultURL: $resultUrl,
            QueueTimeOutURL: $queueTimeoutUrl,
            Remarks: $remarks,
            Occasion: $occasion
        );
        
        $this->logger->info('Reversing transaction', [
            'transaction_id' => $transactionId,
            'amount' => $amount
        ]);
        
        try {
            $requestData = (array) $request;
            if ($occasion === null) {
                unset($requestData['Occasion']);
            }
            
            $response = $this->httpClient->post($this->endpoints->getReversalUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => $requestData
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            $darajaResponse = DarajaResponse::fromArray($data);
            
            $this->logger->info('Transaction reversal initiated', [
                'conversation_id' => $darajaResponse->ConversationID
            ]);
            
            return $darajaResponse;
            
        } catch (\Throwable $e) {
            $this->logger->error('Transaction reversal failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }

    /**
     * Generate Dynamic QR Code
     * 
     * @throws DarajaException
     */
    public function generateQr(
        string $merchantName,
        string $refNo,
        int $amount,
        string $trxCode,
        string $cpi,
        string $size = '300'
    ): array {
        $this->ensureToken();
        
        $request = new QRCodeRequest(
            MerchantName: $merchantName,
            RefNo: $refNo,
            Amount: $amount,
            TrxCode: $trxCode,
            CPI: $cpi,
            Size: $size
        );
        
        $this->logger->info('Generating QR code', [
            'merchant_name' => $merchantName,
            'amount' => $amount
        ]);
        
        try {
            $response = $this->httpClient->post($this->endpoints->getGenerateQrUrl(), [
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->accessToken
                ],
                'json' => (array) $request
            ]);
            
            $data = json_decode($response->getBody()->getContents(), true);
            
            $this->logger->info('QR code generated successfully');
            
            return $data;
            
        } catch (\Throwable $e) {
            $this->logger->error('QR generation failed', ['error' => $e->getMessage()]);
            $this->handleApiError($e);
        }
    }
}