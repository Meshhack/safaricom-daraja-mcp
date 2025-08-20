#!/usr/bin/env python3
import subprocess
import json
import os
import time
import sys

def test_python_mcp():
    print("🧪 Testing Python MCP Server...")
    
    # Set environment variables for testing
    env = os.environ.copy()
    env.update({
        'DARAJA_CONSUMER_KEY': 'test_key',
        'DARAJA_CONSUMER_SECRET': 'test_secret', 
        'DARAJA_BUSINESS_SHORT_CODE': '174379',
        'DARAJA_PASS_KEY': 'test_pass_key',
        'DARAJA_ENVIRONMENT': 'sandbox'
    })
    
    try:
        # Start the server process
        process = subprocess.Popen(
            [sys.executable, '-m', 'mcp_daraja.server'],
            stdin=subprocess.PIPE,
            stdout=subprocess.PIPE,
            stderr=subprocess.PIPE,
            env=env,
            text=True,
            bufsize=0
        )
        
        # Wait for server to start
        time.sleep(2)
        
        # Send initialization request first
        init_request = {
            "jsonrpc": "2.0",
            "id": 1,
            "method": "initialize",
            "params": {
                "protocolVersion": "2024-11-05",
                "capabilities": {},
                "clientInfo": {
                    "name": "test-client",
                    "version": "1.0.0"
                }
            }
        }
        
        print("📨 Sending init request:", json.dumps(init_request))
        process.stdin.write(json.dumps(init_request) + '\n')
        process.stdin.flush()
        
        # Wait for init response
        time.sleep(1)
        
        # Send initialized notification
        init_notify = {
            "jsonrpc": "2.0",
            "method": "notifications/initialized"
        }
        
        print("📨 Sending initialized:", json.dumps(init_notify))
        process.stdin.write(json.dumps(init_notify) + '\n')
        process.stdin.flush()
        
        # Wait a bit
        time.sleep(1)
        
        # Send list tools request
        request = {
            "jsonrpc": "2.0",
            "id": 2,
            "method": "tools/list"
        }
        
        print("📨 Sending request:", json.dumps(request))
        process.stdin.write(json.dumps(request) + '\n')
        process.stdin.flush()
        
        # Read responses
        for _ in range(5):  # Try to read up to 5 responses
            response_line = process.stdout.readline()
            if response_line:
                print("📤 Server response:", response_line.strip())
                
                try:
                    response = json.loads(response_line)
                    if 'result' in response and 'tools' in response['result']:
                        tools = response['result']['tools']
                        print(f"✅ Python MCP Server working! Found {len(tools)} tools:")
                        for tool in tools:
                            print(f"   - {tool['name']}: {tool['description']}")
                        
                        process.terminate()
                        process.wait(timeout=5)
                        print("✅ Python MCP Server test PASSED")
                        return True
                except json.JSONDecodeError as e:
                    # Skip non-JSON responses (might be log messages)
                    pass
            else:
                break
        
        print("❌ No valid tools list response received")
        
        # Check for errors
        stderr_output = process.stderr.read()
        if stderr_output:
            print("📋 Server errors:", stderr_output)
            
        process.terminate()
        process.wait(timeout=5)
        return False
        
    except subprocess.TimeoutExpired:
        print("❌ Test timeout")
        process.kill()
        return False
    except Exception as e:
        print(f"❌ Test error: {e}")
        return False

if __name__ == "__main__":
    success = test_python_mcp()
    sys.exit(0 if success else 1)