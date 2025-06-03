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

/**
 * Symfony Command to install the MCP server configuration for Claude.
 */
class InstallClaudeCommand extends Command
{
    /**
     * @var string The name of the server in the configuration
     */
    private string $serverName;

    /**
     * @var string|null The path to the output file
     */
    private ?string $outputFile;

    /**
     * @var string The path to the mcp-server-start script
     */
    private string $serverPath;

    /**
     * @var bool Whether the Claude configuration file was found
     */
    private bool $configFileFound = false;

    /**
     * Configure the command.
     */
    protected function configure(): void
    {
        $this
            ->setName('install-claude')
            ->setDescription('Install the MCP server configuration for Claude Desktop')
            ->setHelp('This command installs the MCP server configuration for Claude Desktop')
            ->addOption('output', null, InputOption::VALUE_OPTIONAL, 'The path to the output file')
            ->addOption('name', null, InputOption::VALUE_OPTIONAL, 'The name of the server in the configuration');
    }

    /**
     * Create a new InstallCommand instance.
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the project name from composer.json.
     *
     * @return string The project name or 'composer' if not found
     */
    private function getProjectName(): string
    {
        // Find the composer.json file
        $composerJsonPaths = [
            __DIR__ . '/../../../../composer.json',
            __DIR__ . '/../../../../../../../composer.json',
        ];

        foreach ($composerJsonPaths as $path) {
            if (file_exists($path)) {
                $composerJson = file_get_contents($path);
                $composer = json_decode($composerJson, true);

                if (isset($composer['name'])) {
                    // Extract the package name from the full name (vendor/package)
                    $parts = explode('/', $composer['name']);
                    return end($parts);
                }

                break;
            }
        }

        // Default to 'composer' if no name is found
        return 'composer';
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

        // Initialize properties from input options
        $this->serverName = $input->getOption('name') ?? $this->getProjectName();
        $this->outputFile = $input->getOption('output');
        $this->serverPath = $this->findServerPath();

        // If an output file is specified, check if it exists
        if ($this->outputFile !== null && file_exists($this->outputFile)) {
            $this->configFileFound = true;
        }

        // Check if the server path was found
        if (!$this->serverPath) {
            $io->error("Could not find mcp-server-start script. Please ensure the package is installed correctly.");
            return Command::FAILURE;
        }

        // If no output file is specified, try to find the Claude configuration file
        if ($this->outputFile === null) {
            $this->outputFile = $this->findClaudeConfigFile($io);
        }

        // Update the configuration file if it exists
        if ($this->outputFile !== null && file_exists($this->outputFile)) {
            if (!$this->updateConfigFile($io)) {
                return Command::FAILURE;
            }
        } else {
            // If the file doesn't exist, output the configuration and exit
            $io->text("\nCould not find the Claude configuration file at: {$this->outputFile}");
            $io->text("\nPlease manually merge these lines with your Claude configuration file:");
            $config = [
                'mcpServers' => [
                    $this->serverName => [
                        'command' => $this->serverPath,
                        'args' => []
                    ]
                ]
            ];
            $io->text(json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            return Command::SUCCESS;
        }

        // Output success message
        $io->success("Successfully added MCP server '{$this->serverName}' to Claude Desktop configuration.");
        $io->text("Configuration file: {$this->outputFile}");

        // Provide instructions for restarting Claude Desktop
        $io->text("\nTo use this configuration with Claude Desktop:");
        $io->text("1. Restart Claude Desktop completely");
        $io->text("2. After restarting, you should see a slider icon in the bottom left corner of the input box");
        $io->text("3. Click on the slider icon to see the available tools");

        // Troubleshooting tips
        $io->text("\nIf the server isn't being picked up by Claude Desktop:");
        $io->text("1. Make sure Claude Desktop is on the latest version");
        $io->text("2. Check the configuration file syntax");
        $io->text("3. Look at logs in:");
        if (stripos(PHP_OS, 'DARWIN') !== false) {
            $io->text("   - macOS: ~/Library/Logs/Claude");
        } elseif (stripos(PHP_OS, 'WIN') !== false) {
            $io->text("   - Windows: %APPDATA%\\Claude\\logs");
        }

        return Command::SUCCESS;
    }

    /**
     * Find the path to the mcp-server-start script.
     *
     * @return string|null The path to the mcp-server-start script, or null if not found
     */
    private function findServerPath(): ?string
    {
        $paths = [
                __DIR__ . '/../../../../bin/mcp-server-start',
                __DIR__ . '/../../../../../../../bin/mcp-server-start',
        ];

        foreach ($paths as $path) {
            $realPath = realpath($path);
            if ($realPath) {
                return $realPath;
            }
        }

        return null;
    }

    /**
     * Find the Claude configuration file based on the OS.
     *
     * @param SymfonyStyle $io The Symfony style
     * @return ?string The path to the Claude configuration file
     */
    private function findClaudeConfigFile(SymfonyStyle $io): ?string
    {
        $os = PHP_OS;
        $homeDir = $this->getHomeDirectory();
        $searchPaths = [];

        if (stripos($os, 'DARWIN') !== false) {
            // macOS
            // Claude Desktop configuration file path for macOS
            $searchPaths[] = $homeDir . '/Library/Application Support/Claude/claude_desktop_config.json';
            // Fallback paths
            $searchPaths[] = $homeDir . '/.config/Claude/claude_desktop_config.json';
        } elseif (stripos($os, 'WIN') !== false) {
            // Windows
            $appDataPath = getenv('APPDATA');
            if ($appDataPath) {
                // Claude Desktop configuration file path for Windows
                $searchPaths[] = $appDataPath . '\\Claude\\claude_desktop_config.json';
            }
        } elseif (stripos($os, 'LINUX') !== false) {
            // Linux (Claude Desktop doesn't officially support Linux yet)
            $searchPaths[] = $homeDir . '/.config/Claude/claude_desktop_config.json';
        }

        // Check if any of the paths exist
        foreach ($searchPaths as $path) {
            if (file_exists($path)) {
                $this->configFileFound = true;
                return $path;
            }
        }

        // If no file was found, output the search locations
        $io->text("Claude configuration file not found. Searched in the following locations:");
        foreach ($searchPaths as $path) {
            $io->text("- $path");
        }

        return null;
    }

    /**
     * Get the user's home directory.
     *
     * @return string The home directory
     */
    private function getHomeDirectory(): string
    {
        // Try to get the home directory from environment variables
        $home = getenv('HOME');
        if (!$home) {
            // On Windows, try USERPROFILE
            $home = getenv('USERPROFILE');
            if (!$home) {
                // If all else fails, use the current directory
                $home = getcwd();
            }
        }

        return $home;
    }

    /**
     * Update an existing configuration file.
     *
     * @param SymfonyStyle $io The Symfony style
     * @return bool True if the file was updated successfully, false otherwise
     */
    private function updateConfigFile(SymfonyStyle $io): bool
    {
        // Read the existing configuration
        $config = json_decode(file_get_contents($this->outputFile), true);
        if (!$config) {
            $io->error("Error: Could not parse existing configuration file: {$this->outputFile}");
            return false;
        }

        // Add or update our server configuration
        if (!isset($config['mcpServers'])) {
            $config['mcpServers'] = [];
        }

        $config['mcpServers'][$this->serverName] = [
            'command' => $this->serverPath,
            'args' => []
        ];

        $io->text("Updating existing configuration file: {$this->outputFile}");

        // Write the configuration to the output file
        if (file_put_contents($this->outputFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            $io->error("Error: Could not write to file: {$this->outputFile}");
            return false;
        }

        return true;
    }

    /**
     * Create a new configuration file.
     *
     * @param SymfonyStyle $io The Symfony style
     * @return bool True if the file was created successfully, false otherwise
     */
    private function createConfigFile(SymfonyStyle $io): bool
    {
        // Create a new configuration
        $config = [
            'mcpServers' => [
                $this->serverName => [
                    'command' => $this->serverPath,
                    'args' => []
                ]
            ]
        ];

        $io->text("Creating new configuration file: {$this->outputFile}");

        // Write the configuration to the output file
        if (file_put_contents($this->outputFile, json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) === false) {
            $io->error("Error: Could not write to file: {$this->outputFile}");
            return false;
        }

        return true;
    }
}
