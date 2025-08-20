const { spawn } = require('child_process');

// Test the MCP server
async function testMCPServer() {
    console.log('🧪 Testing Node.js MCP Server...');
    
    // Set environment variables for testing
    const env = {
        ...process.env,
        DARAJA_CONSUMER_KEY: 'test_key',
        DARAJA_CONSUMER_SECRET: 'test_secret',
        DARAJA_BUSINESS_SHORT_CODE: '174379',
        DARAJA_PASS_KEY: 'test_pass_key',
        DARAJA_ENVIRONMENT: 'sandbox'
    };
    
    const server = spawn('node', ['dist/index.js'], { 
        env,
        stdio: ['pipe', 'pipe', 'pipe']
    });
    
    let responseReceived = false;
    let timeout;
    
    // Set timeout for the test
    timeout = setTimeout(() => {
        if (!responseReceived) {
            console.log('❌ Test timeout - server did not respond');
            server.kill();
            process.exit(1);
        }
    }, 10000);
    
    server.stdout.on('data', (data) => {
        const response = data.toString();
        console.log('📤 Server response:', response);
        
        try {
            const parsed = JSON.parse(response);
            if (parsed.result && parsed.result.tools) {
                console.log(`✅ MCP Server working! Found ${parsed.result.tools.length} tools:`);
                parsed.result.tools.forEach(tool => {
                    console.log(`   - ${tool.name}: ${tool.description}`);
                });
                responseReceived = true;
                clearTimeout(timeout);
                server.kill();
                console.log('✅ Node.js MCP Server test PASSED');
                process.exit(0);
            }
        } catch (e) {
            // Response might not be JSON, continue
        }
    });
    
    server.stderr.on('data', (data) => {
        const message = data.toString();
        if (message.includes('Safaricom Daraja MCP Server running')) {
            console.log('✅ Server started successfully');
            
            // Send list tools request
            const request = {
                jsonrpc: '2.0',
                id: 1,
                method: 'tools/list'
            };
            
            console.log('📨 Sending request:', JSON.stringify(request));
            server.stdin.write(JSON.stringify(request) + '\n');
        } else {
            console.log('📋 Server log:', message.trim());
        }
    });
    
    server.on('error', (error) => {
        console.log('❌ Server error:', error.message);
        clearTimeout(timeout);
        process.exit(1);
    });
    
    server.on('close', (code) => {
        clearTimeout(timeout);
        if (!responseReceived) {
            console.log(`❌ Server closed with code ${code} before responding`);
            process.exit(1);
        }
    });
}

testMCPServer();