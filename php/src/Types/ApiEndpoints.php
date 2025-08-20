<?php

declare(strict_types=1);

namespace MeshackMusyoka\SafaricomDarajaMcp\Types;

/**
 * API endpoints for different environments
 */
class ApiEndpoints
{
    private const SANDBOX_BASE = 'https://sandbox.safaricom.co.ke';
    private const PRODUCTION_BASE = 'https://api.safaricom.co.ke';

    public function __construct(
        private readonly string $environment
    ) {
    }

    public function getBaseUrl(): string
    {
        return $this->environment === 'production' ? self::PRODUCTION_BASE : self::SANDBOX_BASE;
    }

    public function getOAuthUrl(): string
    {
        return $this->getBaseUrl() . '/oauth/v1/generate?grant_type=client_credentials';
    }

    public function getStkPushUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/stkpush/v1/processrequest';
    }

    public function getStkQueryUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/stkpushquery/v1/query';
    }

    public function getC2bRegisterUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/c2b/v1/registerurl';
    }

    public function getC2bSimulateUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/c2b/v1/simulate';
    }

    public function getB2cUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/b2c/v1/paymentrequest';
    }

    public function getB2bUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/b2b/v1/paymentrequest';
    }

    public function getAccountBalanceUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/accountbalance/v1/query';
    }

    public function getTransactionStatusUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/transactionstatus/v1/query';
    }

    public function getReversalUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/reversal/v1/request';
    }

    public function getGenerateQrUrl(): string
    {
        return $this->getBaseUrl() . '/mpesa/qrcode/v1/generate';
    }
}