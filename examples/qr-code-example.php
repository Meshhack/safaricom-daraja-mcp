<?php

declare(strict_types=1);

/**
 * QR Code Payment Example - PHP
 * Author: Meshack Musyoka
 * 
 * This example demonstrates how to generate dynamic QR codes
 * for M-Pesa payments using the Daraja MCP server.
 */

require_once __DIR__ . '/../php/vendor/autoload.php';

use MeshackMusyoka\SafaricomDarajaMcp\Types\DarajaConfig;
use MeshackMusyoka\SafaricomDarajaMcp\Client\DarajaClient;
use MeshackMusyoka\SafaricomDarajaMcp\Exceptions\DarajaException;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;
use Dotenv\Dotenv;

class QRCodePaymentExample
{
    private Logger $logger;
    private array $qrCodes = []; // In production, use a database
    
    public function __construct()
    {
        // Load environment variables
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../php');
        $dotenv->load();
        
        // Setup logger
        $this->logger = new Logger('qr-example');
        $this->logger->pushHandler(new StreamHandler('php://stdout', Logger::INFO));
        
        $this->setupRoutes();
    }

    private function setupRoutes(): void
    {
        $this->logger->info('🚀 QR Code Payment Example Server starting');
        $this->logger->info('📋 Available endpoints:');
        $this->logger->info('   POST /generate-qr - Generate payment QR code');
        $this->logger->info('   GET /qr/{id} - Get QR code details');
        $this->logger->info('   GET /qr-codes - List all QR codes');
        $this->logger->info('   GET /health - Health check');
        
        $this->handleRequest();
    }

    private function handleRequest(): void
    {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $segments = explode('/', trim($uri, '/'));
        
        try {
            switch ($method) {
                case 'POST':
                    if ($segments[0] === 'generate-qr') {
                        $this->generateQRCode();
                    } else {
                        $this->sendError(404, 'Endpoint not found');
                    }
                    break;
                
                case 'GET':
                    if ($segments[0] === 'qr' && isset($segments[1])) {
                        $this->getQRCode($segments[1]);
                    } elseif ($segments[0] === 'qr-codes') {
                        $this->listQRCodes();
                    } elseif ($segments[0] === 'health') {
                        $this->healthCheck();
                    } else {
                        $this->sendError(404, 'Endpoint not found');
                    }
                    break;
                
                default:
                    $this->sendError(405, 'Method not allowed');
            }
        } catch (Exception $e) {
            $this->logger->error('Request handling error', ['error' => $e->getMessage()]);
            $this->sendError(500, 'Internal server error');
        }
    }

    private function generateQRCode(): void
    {
        try {
            // Parse request body
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (!$input) {
                $this->sendError(400, 'Invalid JSON input');
                return;
            }
            
            // Validate required fields
            $requiredFields = ['merchant_name', 'amount', 'trx_code', 'ref_no'];
            foreach ($requiredFields as $field) {
                if (!isset($input[$field]) || empty($input[$field])) {
                    $this->sendError(400, "Missing required field: {$field}");
                    return;
                }
            }
            
            // Generate unique QR ID
            $qrId = $this->generateQRId();
            
            // Create QR code record
            $qrCodeData = [
                'qr_id' => $qrId,
                'merchant_name' => $input['merchant_name'],
                'amount' => (int) $input['amount'],
                'trx_code' => $input['trx_code'],
                'ref_no' => $input['ref_no'],
                'cpi' => $input['cpi'] ?? '373132', // Default CPI
                'size' => $input['size'] ?? '300',
                'created_at' => new DateTime(),
                'status' => 'pending',
                'qr_code_data' => null
            ];
            
            // Store QR code
            $this->qrCodes[$qrId] = $qrCodeData;
            
            // Generate QR code using MCP
            $this->processQRGeneration($qrId);
            
            $this->logger->info("📱 QR code generation initiated: {$qrId}");
            
            $this->sendResponse([
                'success' => true,
                'qr_id' => $qrId,
                'message' => 'QR code generation initiated',
                'merchant_name' => $input['merchant_name'],
                'amount' => $input['amount'],
                'trx_code' => $input['trx_code'],
                'view_url' => "/qr/{$qrId}"
            ]);
            
        } catch (Exception $e) {
            $this->logger->error('QR generation failed', ['error' => $e->getMessage()]);
            $this->sendError(500, 'QR code generation failed: ' . $e->getMessage());
        }
    }

    private function processQRGeneration(string $qrId): void
    {
        try {
            $qrData = $this->qrCodes[$qrId];
            
            // Simulate MCP call (in real implementation, use proper MCP client)
            $result = $this->callDarajaMCP('daraja_generate_qr', [
                'merchant_name' => $qrData['merchant_name'],
                'ref_no' => $qrData['ref_no'],
                'amount' => $qrData['amount'],
                'trx_code' => $qrData['trx_code'],
                'cpi' => $qrData['cpi'],
                'size' => $qrData['size']
            ]);
            
            // Update QR code with result
            if ($result && isset($result['QRCode'])) {
                $this->qrCodes[$qrId]['qr_code_data'] = $result['QRCode'];
                $this->qrCodes[$qrId]['status'] = 'generated';
                $this->qrCodes[$qrId]['response_code'] = $result['ResponseCode'] ?? '0';
                $this->qrCodes[$qrId]['response_desc'] = $result['ResponseDescription'] ?? 'Success';
                
                $this->logger->info("✅ QR code generated successfully: {$qrId}");
            } else {
                $this->qrCodes[$qrId]['status'] = 'failed';
                $this->logger->error("❌ QR code generation failed: {$qrId}");
            }
            
        } catch (Exception $e) {
            $this->qrCodes[$qrId]['status'] = 'failed';
            $this->qrCodes[$qrId]['error_message'] = $e->getMessage();
            $this->logger->error("❌ QR processing failed: {$qrId} - {$e->getMessage()}");
        }
    }

    private function callDarajaMCP(string $toolName, array $arguments): array
    {
        // In a real implementation, this would use the MCP protocol
        // For this example, we'll simulate calling the Daraja client directly
        
        try {
            $config = new DarajaConfig(
                consumerKey: $_ENV['DARAJA_CONSUMER_KEY'] ?? '',
                consumerSecret: $_ENV['DARAJA_CONSUMER_SECRET'] ?? '',
                businessShortCode: $_ENV['DARAJA_BUSINESS_SHORT_CODE'] ?? '',
                passKey: $_ENV['DARAJA_PASS_KEY'] ?? '',
                environment: $_ENV['DARAJA_ENVIRONMENT'] ?? 'sandbox'
            );
            
            $client = new DarajaClient($config, $this->logger);
            
            if ($toolName === 'daraja_generate_qr') {
                $result = $client->generateQr(
                    merchantName: $arguments['merchant_name'],
                    refNo: $arguments['ref_no'],
                    amount: $arguments['amount'],
                    trxCode: $arguments['trx_code'],
                    cpi: $arguments['cpi'],
                    size: $arguments['size']
                );
                
                return $result;
            }
            
            throw new Exception("Unknown tool: {$toolName}");
            
        } catch (DarajaException $e) {
            $this->logger->error('Daraja API error', [
                'tool' => $toolName,
                'error' => $e->getMessage(),
                'code' => $e->getDarajaCode()
            ]);
            throw $e;
        }
    }

    private function getQRCode(string $qrId): void
    {
        if (!isset($this->qrCodes[$qrId])) {
            $this->sendError(404, 'QR code not found');
            return;
        }
        
        $qrData = $this->qrCodes[$qrId];
        
        // Convert DateTime to string for JSON
        $qrData['created_at'] = $qrData['created_at']->format('c');
        
        // Add usage instructions
        $qrData['usage_instructions'] = $this->getUsageInstructions($qrData['trx_code']);
        
        // Add QR code display if available
        if ($qrData['status'] === 'generated' && $qrData['qr_code_data']) {
            $qrData['qr_display'] = $this->formatQRCodeForDisplay($qrData['qr_code_data']);
        }
        
        $this->sendResponse($qrData);
    }

    private function listQRCodes(): void
    {
        $qrList = [];
        
        foreach ($this->qrCodes as $qrId => $qrData) {
            $qrList[] = [
                'qr_id' => $qrId,
                'merchant_name' => $qrData['merchant_name'],
                'amount' => $qrData['amount'],
                'trx_code' => $qrData['trx_code'],
                'status' => $qrData['status'],
                'created_at' => $qrData['created_at']->format('c'),
                'view_url' => "/qr/{$qrId}"
            ];
        }
        
        $this->sendResponse([
            'qr_codes' => $qrList,
            'total_count' => count($qrList),
            'status_summary' => $this->getStatusSummary()
        ]);
    }

    private function healthCheck(): void
    {
        $this->sendResponse([
            'status' => 'healthy',
            'timestamp' => (new DateTime())->format('c'),
            'total_qr_codes' => count($this->qrCodes),
            'environment' => $_ENV['DARAJA_ENVIRONMENT'] ?? 'sandbox',
            'version' => '1.0.0'
        ]);
    }

    private function generateQRId(): string
    {
        return 'QR_' . date('Ymd_His') . '_' . substr(uniqid(), -8);
    }

    private function getUsageInstructions(string $trxCode): array
    {
        $instructions = [
            'BG' => [
                'type' => 'Buy Goods',
                'description' => 'Customer scans QR code to pay for goods/services',
                'steps' => [
                    'Customer opens M-Pesa app',
                    'Selects "Lipa Na M-Pesa"',
                    'Selects "Scan QR"',
                    'Scans the QR code',
                    'Enters M-Pesa PIN to confirm payment'
                ]
            ],
            'PB' => [
                'type' => 'Pay Bill',
                'description' => 'Customer scans QR code to pay bill',
                'steps' => [
                    'Customer opens M-Pesa app',
                    'Selects "Lipa Na M-Pesa"',
                    'Selects "Scan QR"',
                    'Scans the QR code',
                    'Enters account number if required',
                    'Enters M-Pesa PIN to confirm payment'
                ]
            ],
            'WA' => [
                'type' => 'Withdraw Agent',
                'description' => 'Customer uses QR to withdraw cash from agent',
                'steps' => [
                    'Customer goes to M-Pesa agent',
                    'Agent scans QR code',
                    'Customer enters M-Pesa PIN',
                    'Agent dispenses cash'
                ]
            ],
            'SM' => [
                'type' => 'Send Money',
                'description' => 'Customer uses QR to send money',
                'steps' => [
                    'Customer opens M-Pesa app',
                    'Selects "Send Money"',
                    'Selects "Scan QR"',
                    'Scans the QR code',
                    'Enters M-Pesa PIN to confirm'
                ]
            ]
        ];
        
        return $instructions[$trxCode] ?? [
            'type' => 'Unknown',
            'description' => 'Unknown transaction type',
            'steps' => []
        ];
    }

    private function formatQRCodeForDisplay(string $qrCodeData): array
    {
        return [
            'raw_data' => $qrCodeData,
            'display_format' => 'text',
            'instructions' => 'Use this QR code data with a QR code generator library to create a scannable image',
            'suggested_libraries' => [
                'PHP' => 'endroid/qr-code',
                'JavaScript' => 'qrcode.js',
                'Python' => 'qrcode'
            ]
        ];
    }

    private function getStatusSummary(): array
    {
        $summary = [
            'pending' => 0,
            'generated' => 0,
            'failed' => 0
        ];
        
        foreach ($this->qrCodes as $qrData) {
            $status = $qrData['status'];
            if (isset($summary[$status])) {
                $summary[$status]++;
            }
        }
        
        return $summary;
    }

    private function sendResponse(array $data): void
    {
        header('Content-Type: application/json');
        echo json_encode($data, JSON_PRETTY_PRINT);
        exit;
    }

    private function sendError(int $statusCode, string $message): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode([
            'error' => true,
            'message' => $message,
            'timestamp' => (new DateTime())->format('c')
        ], JSON_PRETTY_PRINT);
        exit;
    }

    public function run(): void
    {
        $this->logger->info("\n💡 Example QR code generation request:");
        $this->logger->info('curl -X POST http://localhost:8080/generate-qr \\');
        $this->logger->info('  -H "Content-Type: application/json" \\');
        $this->logger->info('  -d \'{');
        $this->logger->info('    "merchant_name": "Test Store",');
        $this->logger->info('    "amount": 1000,');
        $this->logger->info('    "trx_code": "BG",');
        $this->logger->info('    "ref_no": "ORDER123",');
        $this->logger->info('    "cpi": "373132"');
        $this->logger->info('  }\'');
        $this->logger->info('');
    }
}

// Run the example
if (php_sapi_name() === 'cli-server' || !defined('PHPUNIT_RUNNING')) {
    $example = new QRCodePaymentExample();
    $example->run();
} else {
    // Return class for testing
    return QRCodePaymentExample::class;
}

// To run this example:
// cd examples
// php -S localhost:8080 qr-code-example.php