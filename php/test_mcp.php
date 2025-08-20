#!/usr/bin/env php
<?php
/**
 * Test the PHP MCP Server
 * Author: Meshack Musyoka
 */

require_once __DIR__ . '/vendor/autoload.php';

use MeshackMusyoka\SafaricomDarajaMcp\Server\McpServer;
use MeshackMusyoka\SafaricomDarajaMcp\Types\DarajaConfig;

// Simple test to verify the PHP MCP server can start and list tools
echo "🧪 Testing PHP MCP Server...\n";

// Try to instantiate the server class
try {
    
    // Test configuration
    $config = new DarajaConfig(
        'test_key',
        'test_secret',
        '174379',
        'test_pass_key',
        'sandbox',
        'test_initiator',
        'test_password'
    );
    
    $server = new McpServer($config);
    
    // Get the tools list to verify the server structure
    $tools = $server->getTools();
    
    echo "✅ PHP MCP Server working! Found " . count($tools) . " tools:\n";
    foreach ($tools as $tool) {
        echo "   - {$tool['name']}: {$tool['description']}\n";
    }
    
    echo "✅ PHP MCP Server test PASSED\n";
    
} catch (Exception $e) {
    echo "❌ PHP MCP Server test failed: " . $e->getMessage() . "\n";
    exit(1);
}