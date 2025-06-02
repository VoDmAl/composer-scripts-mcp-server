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

use PhpMcp\Server\Attributes\McpTool;

/**
 * A tool that exposes Composer scripts as MCP tools.
 */
class ComposerScriptsTool
{
    /**
     * @var array The Composer scripts
     */
    private array $scripts = [];

    /**
     * @var string The path to the composer.json file
     */
    private string $composerJsonPath;

    /**
     * Create a new ComposerScriptsTool.
     */
    public function __construct()
    {
        // Always use the composer.json in the project root
        $this->composerJsonPath = dirname(dirname(dirname(__DIR__))) . '/composer.json';
        $this->loadScripts();
    }

    /**
     * Load scripts from the composer.json file.
     */
    private function loadScripts(): void
    {
        if (!file_exists($this->composerJsonPath)) {
            throw new \RuntimeException("Composer file not found: {$this->composerJsonPath}");
        }

        $composerJson = file_get_contents($this->composerJsonPath);
        $composer = json_decode($composerJson, true);

        if (!isset($composer['scripts']) || !is_array($composer['scripts'])) {
            return;
        }

        $this->scripts = $composer['scripts'];
    }

    /**
     * Run a Composer script.
     *
     * @param string $script The name of the script to run
     * @param array $arguments Additional arguments to pass to the script
     * @return array The output of the script
     */
    #[McpTool(name: 'composer_run', description: 'Run a Composer script')]
    public function runScript(string $script, array $arguments = []): array
    {
        if (!isset($this->scripts[$script])) {
            throw new \InvalidArgumentException("Script not found: {$script}");
        }

        $scriptCommand = $this->scripts[$script];
        $command = $this->buildCommand($scriptCommand, $arguments);

        $output = [];
        $returnCode = 0;
        exec($command, $output, $returnCode);

        return [
            'script' => $script,
            'command' => $command,
            'output' => $output,
            'return_code' => $returnCode,
            'success' => $returnCode === 0
        ];
    }

    /**
     * List all available Composer scripts.
     *
     * @return array The list of available scripts
     */
    #[McpTool(name: 'composer_list', description: 'List all available Composer scripts')]
    public function listScripts(): array
    {
        $scriptList = [];
        foreach ($this->scripts as $name => $script) {
            $scriptList[] = [
                'name' => $name,
                'command' => is_array($script) ? implode(' && ', $script) : $script
            ];
        }

        return [
            'scripts' => $scriptList,
            'count' => count($scriptList)
        ];
    }

    /**
     * Build the command to run a script.
     *
     * @param string|array $script The script to run
     * @param array $arguments Additional arguments to pass to the script
     * @return string The command to run
     */
    private function buildCommand(string|array $script, array $arguments = []): string
    {
        $composerDir = dirname($this->composerJsonPath);
        $cd = "cd " . escapeshellarg($composerDir) . " && ";

        if (is_array($script)) {
            $commands = [];
            foreach ($script as $cmd) {
                $commands[] = $this->appendArguments($cmd, $arguments);
            }
            return $cd . implode(' && ', $commands);
        }

        return $cd . $this->appendArguments($script, $arguments);
    }

    /**
     * Append arguments to a command.
     *
     * @param string $command The command
     * @param array $arguments The arguments to append
     * @return string The command with arguments
     */
    private function appendArguments(string $command, array $arguments = []): string
    {
        if (empty($arguments)) {
            return $command;
        }

        $escapedArgs = array_map('escapeshellarg', $arguments);
        return $command . ' ' . implode(' ', $escapedArgs);
    }
}
