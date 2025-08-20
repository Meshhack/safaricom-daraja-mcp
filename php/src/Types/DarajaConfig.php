<?php

declare(strict_types=1);

/**
 * Safaricom Daraja API MCP - PHP Implementation
 * Author: Meshack Musyoka
 */

namespace MeshackMusyoka\SafaricomDarajaMcp\Types;

/**
 * Configuration class for Daraja API client
 */
class DarajaConfig
{
    public function __construct(
        public readonly string $consumerKey,
        public readonly string $consumerSecret,
        public readonly string $businessShortCode,
        public readonly string $passKey,
        public readonly string $environment = 'sandbox',
        public readonly ?string $initiatorName = null,
        public readonly ?string $initiatorPassword = null
    ) {
        if (!in_array($environment, ['sandbox', 'production'])) {
            throw new \InvalidArgumentException('Environment must be either "sandbox" or "production"');
        }
        
        if (empty($consumerKey)) {
            throw new \InvalidArgumentException('Consumer key cannot be empty');
        }
        
        if (empty($consumerSecret)) {
            throw new \InvalidArgumentException('Consumer secret cannot be empty');
        }
        
        if (empty($businessShortCode)) {
            throw new \InvalidArgumentException('Business short code cannot be empty');
        }
        
        if (empty($passKey)) {
            throw new \InvalidArgumentException('Pass key cannot be empty');
        }
    }

    public function isProduction(): bool
    {
        return $this->environment === 'production';
    }

    public function isSandbox(): bool
    {
        return $this->environment === 'sandbox';
    }

    public function hasInitiatorCredentials(): bool
    {
        return $this->initiatorName !== null && $this->initiatorPassword !== null;
    }
}