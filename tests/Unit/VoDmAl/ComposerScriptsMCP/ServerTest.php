<?php
/*
 * This file is part of VoDmAl Composer Scripts MCP Server
 *
 * (c) Dmitry Vorobyev <dmitry@vorobyev.org>
 *
 * This source file is subject to dual licensing:
 * - GPL-3.0-or-later for open source use
 * - Commercial license for proprietary use
 * 
 * For commercial licensing, contact: dmitry@vorobyev.org
 */

namespace VoDmAl\Tests\Unit\ComposerScriptsMCP;

use PHPUnit\Framework\TestCase;
use PhpMcp\Server\Server as McpServer;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use VoDmAl\ComposerScriptsMCP\Server;

class ServerTest extends TestCase
{
    /**
     * Test that the constructor initializes the server and logger correctly.
     */
    public function testConstructor(): void
    {
        // Create a server with default logger
        $server = new Server();
        
        // Test that the server instance is initialized
        $this->assertInstanceOf(McpServer::class, $server->getServer());
    }
    
    /**
     * Test that the constructor accepts a custom logger.
     */
    public function testConstructorWithCustomLogger(): void
    {
        // Create a mock logger
        $logger = $this->createMock(LoggerInterface::class);
        
        // Create a server with the mock logger
        $server = new Server($logger);
        
        // Test that the server instance is initialized
        $this->assertInstanceOf(McpServer::class, $server->getServer());
    }
    
    /**
     * Test that the registerClass method logs a warning.
     */
    public function testRegisterClassLogsWarning(): void
    {
        // Create a mock logger
        $logger = $this->createMock(LoggerInterface::class);
        
        // The logger should receive a warning
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('registerClass() is not supported'));
        
        // Create a server with the mock logger
        $server = new Server($logger);
        
        // Call the method that should log a warning
        $server->registerClass('SomeClass');
    }
    
    /**
     * Test that the registerDirectory method logs a warning.
     */
    public function testRegisterDirectoryLogsWarning(): void
    {
        // Create a mock logger
        $logger = $this->createMock(LoggerInterface::class);
        
        // The logger should receive a warning
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('registerDirectory() is not supported'));
        
        // Create a server with the mock logger
        $server = new Server($logger);
        
        // Call the method that should log a warning
        $server->registerDirectory('/some/directory', 'Some\\Namespace');
    }
    
    /**
     * Test that the registerComposerScripts method logs a warning.
     */
    public function testRegisterComposerScriptsLogsWarning(): void
    {
        // Create a mock logger
        $logger = $this->createMock(LoggerInterface::class);
        
        // The logger should receive a warning
        $logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('registerComposerScripts() is not needed'));
        
        // Create a server with the mock logger
        $server = new Server($logger);
        
        // Call the method that should log a warning
        $server->registerComposerScripts();
    }
}