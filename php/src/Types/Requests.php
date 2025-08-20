<?php

declare(strict_types=1);

namespace MeshackMusyoka\SafaricomDarajaMcp\Types;

/**
 * Request DTOs for Daraja API
 */

class STKPushRequest
{
    public function __construct(
        public readonly string $BusinessShortCode,
        public readonly string $Password,
        public readonly string $Timestamp,
        public readonly string $TransactionType,
        public readonly int $Amount,
        public readonly string $PartyA,
        public readonly string $PartyB,
        public readonly string $PhoneNumber,
        public readonly string $CallBackURL,
        public readonly string $AccountReference,
        public readonly string $TransactionDesc
    ) {
        if ($Amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if (strlen($AccountReference) > 12) {
            throw new \InvalidArgumentException('Account reference must be 12 characters or less');
        }
        
        if (strlen($TransactionDesc) > 13) {
            throw new \InvalidArgumentException('Transaction description must be 13 characters or less');
        }
    }
}

class STKQueryRequest
{
    public function __construct(
        public readonly string $BusinessShortCode,
        public readonly string $Password,
        public readonly string $Timestamp,
        public readonly string $CheckoutRequestID
    ) {
    }
}

class C2BRegisterRequest
{
    public function __construct(
        public readonly string $ShortCode,
        public readonly string $ResponseType,
        public readonly string $ConfirmationURL,
        public readonly string $ValidationURL
    ) {
        if (!in_array($ResponseType, ['Cancelled', 'Completed'])) {
            throw new \InvalidArgumentException('ResponseType must be either "Cancelled" or "Completed"');
        }
    }
}

class C2BSimulateRequest
{
    public function __construct(
        public readonly string $ShortCode,
        public readonly string $CommandID,
        public readonly int $Amount,
        public readonly string $Msisdn,
        public readonly ?string $BillRefNumber = null
    ) {
        if ($Amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if (!in_array($CommandID, ['CustomerPayBillOnline', 'CustomerBuyGoodsOnline'])) {
            throw new \InvalidArgumentException('Invalid CommandID');
        }
    }
}

class B2CRequest
{
    public function __construct(
        public readonly string $InitiatorName,
        public readonly string $SecurityCredential,
        public readonly string $CommandID,
        public readonly int $Amount,
        public readonly string $PartyA,
        public readonly string $PartyB,
        public readonly string $Remarks,
        public readonly string $QueueTimeOutURL,
        public readonly string $ResultURL,
        public readonly ?string $Occasion = null
    ) {
        if ($Amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if (strlen($Remarks) > 100) {
            throw new \InvalidArgumentException('Remarks must be 100 characters or less');
        }
        
        if ($Occasion && strlen($Occasion) > 100) {
            throw new \InvalidArgumentException('Occasion must be 100 characters or less');
        }
    }
}

class B2BRequest
{
    public function __construct(
        public readonly string $InitiatorName,
        public readonly string $SecurityCredential,
        public readonly string $CommandID,
        public readonly int $Amount,
        public readonly string $PartyA,
        public readonly string $PartyB,
        public readonly string $Remarks,
        public readonly string $QueueTimeOutURL,
        public readonly string $ResultURL,
        public readonly string $AccountReference
    ) {
        if ($Amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if (strlen($Remarks) > 100) {
            throw new \InvalidArgumentException('Remarks must be 100 characters or less');
        }
        
        if (strlen($AccountReference) > 12) {
            throw new \InvalidArgumentException('Account reference must be 12 characters or less');
        }
    }
}

class AccountBalanceRequest
{
    public function __construct(
        public readonly string $InitiatorName,
        public readonly string $SecurityCredential,
        public readonly string $CommandID,
        public readonly string $PartyA,
        public readonly string $IdentifierType,
        public readonly string $Remarks,
        public readonly string $QueueTimeOutURL,
        public readonly string $ResultURL
    ) {
        if (strlen($Remarks) > 100) {
            throw new \InvalidArgumentException('Remarks must be 100 characters or less');
        }
        
        if (!in_array($IdentifierType, ['1', '2', '4'])) {
            throw new \InvalidArgumentException('IdentifierType must be 1, 2, or 4');
        }
    }
}

class TransactionStatusRequest
{
    public function __construct(
        public readonly string $InitiatorName,
        public readonly string $SecurityCredential,
        public readonly string $CommandID,
        public readonly string $TransactionID,
        public readonly string $PartyA,
        public readonly string $IdentifierType,
        public readonly string $ResultURL,
        public readonly string $QueueTimeOutURL,
        public readonly string $Remarks,
        public readonly ?string $Occasion = null
    ) {
        if (strlen($Remarks) > 100) {
            throw new \InvalidArgumentException('Remarks must be 100 characters or less');
        }
        
        if ($Occasion && strlen($Occasion) > 100) {
            throw new \InvalidArgumentException('Occasion must be 100 characters or less');
        }
        
        if (!in_array($IdentifierType, ['1', '2', '4'])) {
            throw new \InvalidArgumentException('IdentifierType must be 1, 2, or 4');
        }
    }
}

class ReversalRequest
{
    public function __construct(
        public readonly string $InitiatorName,
        public readonly string $SecurityCredential,
        public readonly string $CommandID,
        public readonly string $TransactionID,
        public readonly int $Amount,
        public readonly string $ReceiverParty,
        public readonly string $RecieverIdentifierType,
        public readonly string $ResultURL,
        public readonly string $QueueTimeOutURL,
        public readonly string $Remarks,
        public readonly ?string $Occasion = null
    ) {
        if ($Amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if (strlen($Remarks) > 100) {
            throw new \InvalidArgumentException('Remarks must be 100 characters or less');
        }
        
        if ($Occasion && strlen($Occasion) > 100) {
            throw new \InvalidArgumentException('Occasion must be 100 characters or less');
        }
        
        if (!in_array($RecieverIdentifierType, ['1', '2', '4', '11'])) {
            throw new \InvalidArgumentException('RecieverIdentifierType must be 1, 2, 4, or 11');
        }
    }
}

class QRCodeRequest
{
    public function __construct(
        public readonly string $MerchantName,
        public readonly string $RefNo,
        public readonly int $Amount,
        public readonly string $TrxCode,
        public readonly string $CPI,
        public readonly string $Size = '300'
    ) {
        if ($Amount <= 0) {
            throw new \InvalidArgumentException('Amount must be positive');
        }
        
        if (strlen($MerchantName) > 22) {
            throw new \InvalidArgumentException('Merchant name must be 22 characters or less');
        }
        
        if (strlen($RefNo) > 12) {
            throw new \InvalidArgumentException('Reference number must be 12 characters or less');
        }
        
        if (!in_array($TrxCode, ['BG', 'WA', 'PB', 'SM'])) {
            throw new \InvalidArgumentException('TrxCode must be BG, WA, PB, or SM');
        }
        
        if ($Size !== '300') {
            throw new \InvalidArgumentException('Size must be "300"');
        }
    }
}