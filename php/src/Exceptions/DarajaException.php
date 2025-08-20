<?php

declare(strict_types=1);

namespace MeshackMusyoka\SafaricomDarajaMcp\Exceptions;

/**
 * Custom exception for Daraja API errors
 */
class DarajaException extends \Exception
{
    public function __construct(
        string $message,
        private readonly ?string $darajaCode = null,
        private readonly ?int $httpStatus = null,
        private readonly ?array $response = null,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getDarajaCode(): ?string
    {
        return $this->darajaCode;
    }

    public function getHttpStatus(): ?int
    {
        return $this->httpStatus;
    }

    public function getResponse(): ?array
    {
        return $this->response;
    }

    public static function fromApiResponse(array $response, int $httpStatus): self
    {
        $message = $response['errorMessage'] ?? $response['ResponseDescription'] ?? 'Unknown API error';
        $code = $response['errorCode'] ?? $response['ResponseCode'] ?? 'UNKNOWN';
        
        return new self(
            $message,
            $code,
            $httpStatus,
            $response
        );
    }

    public static function networkError(string $message, ?\Throwable $previous = null): self
    {
        return new self($message, 'NETWORK_ERROR', null, null, 0, $previous);
    }

    public static function validationError(string $message): self
    {
        return new self($message, 'VALIDATION_ERROR');
    }

    public static function configurationError(string $message): self
    {
        return new self($message, 'CONFIGURATION_ERROR');
    }
}