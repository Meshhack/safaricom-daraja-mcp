<?php

declare(strict_types=1);

namespace MeshackMusyoka\SafaricomDarajaMcp\Types;

/**
 * Response DTOs for Daraja API
 */

class TokenResponse
{
    public function __construct(
        public readonly string $access_token,
        public readonly string $expires_in
    ) {
    }
    
    public static function fromArray(array $data): self
    {
        return new self($data['access_token'] ?? '', $data['expires_in'] ?? '');
    }
}

class STKPushResponse
{
    public function __construct(
        public readonly string $MerchantRequestID,
        public readonly string $CheckoutRequestID,
        public readonly string $ResponseCode,
        public readonly string $ResponseDescription,
        public readonly string $CustomerMessage
    ) {
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['MerchantRequestID'] ?? '',
            $data['CheckoutRequestID'] ?? '',
            $data['ResponseCode'] ?? '',
            $data['ResponseDescription'] ?? '',
            $data['CustomerMessage'] ?? ''
        );
    }
}

class STKQueryResponse
{
    public function __construct(
        public readonly string $ResponseCode,
        public readonly string $ResponseDescription,
        public readonly string $MerchantRequestID,
        public readonly string $CheckoutRequestID,
        public readonly string $ResultCode,
        public readonly string $ResultDesc
    ) {
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['ResponseCode'] ?? '',
            $data['ResponseDescription'] ?? '',
            $data['MerchantRequestID'] ?? '',
            $data['CheckoutRequestID'] ?? '',
            $data['ResultCode'] ?? '',
            $data['ResultDesc'] ?? ''
        );
    }
}

class DarajaResponse
{
    public function __construct(
        public readonly ?string $ResponseCode = null,
        public readonly ?string $ResponseDescription = null,
        public readonly ?string $errorMessage = null,
        public readonly ?string $errorCode = null,
        public readonly ?string $ConversationID = null,
        public readonly ?string $OriginatorConversationID = null
    ) {
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            $data['ResponseCode'] ?? null,
            $data['ResponseDescription'] ?? null,
            $data['errorMessage'] ?? null,
            $data['errorCode'] ?? null,
            $data['ConversationID'] ?? null,
            $data['OriginatorConversationID'] ?? null
        );
    }
    
    public function isSuccess(): bool
    {
        return $this->ResponseCode === '0' || ($this->ResponseCode !== null && $this->errorCode === null);
    }
    
    public function getErrorMessage(): ?string
    {
        return $this->errorMessage ?? $this->ResponseDescription;
    }
}