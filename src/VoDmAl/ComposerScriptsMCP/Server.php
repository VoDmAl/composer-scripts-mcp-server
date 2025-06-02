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

namespace VoDmAl\ComposerScriptsMCP;

use PhpMcp\Server\Server as McpServer;
use PhpMcp\Server\Transports\StdioServerTransport;
use PhpMcp\Server\Transports\HttpServerTransport;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * MCP Server implementation using php-mcp/server.
 */
class Server
{
    /**
     * @var McpServer The MCP server instance
     */
    private McpServer $server;

    /**
     * @var LoggerInterface The logger
     */
    private LoggerInterface $logger;

    /**
     * Create a new MCP server.
     *
     * @param LoggerInterface|null $logger Optional logger
     */
    public function __construct(?LoggerInterface $logger = null)
    {
        $this->logger = $logger ?? new NullLogger();

        // Build the server
        $this->server = McpServer::make()
            ->withServerInfo('Composer Scripts MCP Server', '1.0.0')
            ->withLogger($this->logger)
            ->build();

        // Register the src directory for discovery
        $this->server->discover(dirname(dirname(dirname(__DIR__))) . '/src', ['VoDmAl']);
    }

    /**
     * Register a class with MCP attributes.
     *
     * This method is not supported in the current implementation.
     * Classes should be registered during server construction.
     *
     * @param object|string $class The class instance or class name
     * @return $this
     * @deprecated
     */
    public function registerClass(object|string $class): self
    {
        $this->logger->warning('registerClass() is not supported in the current implementation. Classes should be registered during server construction.');
        return $this;
    }

    /**
     * Register a directory of classes with MCP attributes.
     *
     * This method is not supported in the current implementation.
     * Classes should be registered during server construction.
     *
     * @param string $directory The directory path
     * @param string $namespace The namespace for the classes
     * @return $this
     * @deprecated
     */
    public function registerDirectory(string $directory, string $namespace): self
    {
        $this->logger->warning('registerDirectory() is not supported in the current implementation. Classes should be registered during server construction.');
        return $this;
    }

    /**
     * Start the server using stdio transport.
     */
    public function startWithStdio(): void
    {
        $transport = new StdioServerTransport();
        $this->server->listen($transport);
    }

    /**
     * Start the server using HTTP transport.
     *
     * @param string $host The host to bind to
     * @param int $port The port to bind to
     */
    public function startWithHttp(string $host = '127.0.0.1', int $port = 8088): void
    {
        $transport = new HttpServerTransport($host, $port);
        $this->logger->info("MCP Server starting on http://{$host}:{$port}");
        $this->server->listen($transport);
    }

    /**
     * Register Composer scripts from the project's composer.json file.
     *
     * This method is not needed anymore as the ComposerScriptsTool is registered
     * during server construction.
     *
     * @return $this
     * @deprecated
     */
    public function registerComposerScripts(): self
    {
        $this->logger->warning('registerComposerScripts() is not needed anymore as the ComposerScriptsTool is registered during server construction.');
        return $this;
    }

    /**
     * Get the underlying McpServer instance.
     *
     * @return McpServer
     */
    public function getServer(): McpServer
    {
        return $this->server;
    }
}
