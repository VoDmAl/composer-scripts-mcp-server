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

namespace VoDmAl\ComposerScriptsMCP\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use VoDmAl\ComposerScriptsMCP\Server;
use Psr\Log\LoggerInterface;

/**
 * Symfony Command to start the MCP server.
 */
class ComposerServerCommand extends Command
{
    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this
            ->setName('start-server')
            ->setDescription('Start the MCP server for Composer scripts')
            ->setHelp('This command starts the MCP server for Composer scripts')
            ->addOption('http', null, InputOption::VALUE_NONE, 'Use HTTP transport instead of stdio')
            ->addOption('host', null, InputOption::VALUE_OPTIONAL, 'The host to bind to (only for HTTP transport)', '127.0.0.1')
            ->addOption('port', null, InputOption::VALUE_OPTIONAL, 'The port to bind to (only for HTTP transport)', 8088);
    }

    /**
     * Execute the command.
     *
     * @param InputInterface $input The input interface
     * @param OutputInterface $output The output interface
     * @return int The exit code
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Create a logger that uses Symfony's output
        $logger = $this->createLogger($output);

        // Parse command line options
        $useHttp = $input->getOption('http');
        $host = $input->getOption('host');
        $port = (int)$input->getOption('port');

        // Create the MCP server
        $server = new Server($logger);

        // Start the server
        if ($useHttp) {
            $output->writeln("<info>Starting MCP server with HTTP transport on $host:$port</info>");
            $output->writeln("<info>Press Ctrl+C to stop the server</info>");
            $server->startWithHttp($host, $port);
        } else {
            $output->writeln("<info>Starting MCP server with stdio transport</info>");
            $server->startWithStdio();
        }

        return Command::SUCCESS;
    }

    /**
     * Create a simple logger that uses Symfony's output.
     *
     * @param OutputInterface $output The output interface
     * @return LoggerInterface The logger
     */
    private function createLogger(OutputInterface $output): LoggerInterface
    {
        return new class($output) implements LoggerInterface {
            private OutputInterface $output;

            public function __construct(OutputInterface $output)
            {
                $this->output = $output;
            }

            public function emergency($message, array $context = []): void { $this->output->writeln("<error>$message</error>"); }
            public function alert($message, array $context = []): void { $this->output->writeln("<error>$message</error>"); }
            public function critical($message, array $context = []): void { $this->output->writeln("<error>$message</error>"); }
            public function error($message, array $context = []): void { $this->output->writeln("<error>$message</error>"); }
            public function warning($message, array $context = []): void { $this->output->writeln("<comment>$message</comment>"); }
            public function notice($message, array $context = []): void { $this->output->writeln("<info>$message</info>"); }
            public function info($message, array $context = []): void { $this->output->writeln("<info>$message</info>"); }
            public function debug($message, array $context = []): void { $this->output->writeln($message); }

            public function log($level, $message, array $context = []): void
            {
                switch (strtoupper($level)) {
                    case 'EMERGENCY':
                    case 'ALERT':
                    case 'CRITICAL':
                    case 'ERROR':
                        $this->emergency($message);
                        break;
                    case 'WARNING':
                        $this->warning($message);
                        break;
                    case 'NOTICE':
                    case 'INFO':
                        $this->info($message);
                        break;
                    case 'DEBUG':
                    default:
                        $this->debug($message);
                        break;
                }
            }
        };
    }
}
