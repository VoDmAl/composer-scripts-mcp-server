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

namespace VoDmAl\ComposerScriptsMCP\Plugin;

use Composer\Composer;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\Script\ScriptEvents;

/**
 * Composer plugin that adds our scripts to the host project's composer.json.
 */
class ComposerScriptsPlugin implements PluginInterface, EventSubscriberInterface
{
    /**
     * @var Composer The composer instance
     */
    private Composer $composer;

    /**
     * @var IOInterface The IO interface
     */
    protected IOInterface $io;

    /**
     * Apply the plugin.
     *
     * @param Composer $composer The composer instance
     * @param IOInterface $io The IO interface
     */
    public function activate(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;
    }

    /**
     * Remove any hooks from Composer.
     *
     * @param Composer $composer The composer instance
     * @param IOInterface $io The IO interface
     */
    public function deactivate(Composer $composer, IOInterface $io): void
    {
        // Nothing to do here
    }

    /**
     * Prepare the plugin to be uninstalled.
     *
     * @param Composer $composer The composer instance
     * @param IOInterface $io The IO interface
     */
    public function uninstall(Composer $composer, IOInterface $io): void
    {
        $this->composer = $composer;
        $this->io = $io;

        $this->io->write('<info>Removing VoDmAl Composer Scripts MCP Server scripts from your composer.json</info>');

        // Get the path to the host project's composer.json
        $composerJsonPath = $this->getCwd() . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            $this->io->writeError('<error>Could not find composer.json in the current directory</error>');
            return;
        }

        // Read the composer.json file
        $composerJson = file_get_contents($composerJsonPath);
        $composerConfig = json_decode($composerJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->io->writeError('<error>Could not parse composer.json: ' . json_last_error_msg() . '</error>');
            return;
        }

        // Check if scripts section exists
        if (!isset($composerConfig['scripts'])) {
            $this->io->write('<info>No scripts section found in composer.json</info>');
            return;
        }

        // Remove both old and new script signatures
        $scriptsRemoved = false;

        // List of scripts to remove (both old and new signatures)
        $scriptsToRemove = [
            // Old signatures @since 1.0.0
            'start' => 'vendor/bin/start-server',
            'start:http' => 'vendor/bin/start-server --http',
            'install-claude' => 'vendor/bin/install-claude',

            // New signatures @since 1.0.2
            'mcp:server:start' => 'vendor/bin/mcp-server-start',
            'mcp:server:install' => 'vendor/bin/mcp-server-install'
        ];

        foreach ($scriptsToRemove as $script => $command) {
            if (isset($composerConfig['scripts'][$script]) && $composerConfig['scripts'][$script] === $command) {
                unset($composerConfig['scripts'][$script]);
                $scriptsRemoved = true;
                $this->io->write("<info>Removed script: {$script}</info>");
            }
        }

        // Write the updated composer.json file
        if ($scriptsRemoved) {
            file_put_contents(
                $composerJsonPath,
                json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->io->write('<info>Scripts removed successfully</info>');
        } else {
            $this->io->write('<info>No scripts were removed from composer.json</info>');
        }
    }

    /**
     * Get the events to which this plugin subscribes.
     *
     * @return array The events
     */
    public static function getSubscribedEvents(): array
    {
        return [
            ScriptEvents::POST_INSTALL_CMD => 'addScripts',
            ScriptEvents::POST_UPDATE_CMD => 'addScripts',
        ];
    }

    /**
     * Get the current working directory.
     *
     * @return string The current working directory
     */
    protected function getCwd(): string
    {
        return getcwd();
    }

    /**
     * Add our scripts to the host project's composer.json.
     */
    public function addScripts(): void
    {
        $this->io->write('<info>Adding VoDmAl Composer Scripts MCP Server to your composer.json</info>');

        // Get the path to the host project's composer.json
        $composerJsonPath = $this->getCwd() . '/composer.json';

        if (!file_exists($composerJsonPath)) {
            $this->io->writeError('<error>Could not find composer.json in the current directory</error>');
            return;
        }

        // Read the composer.json file
        $composerJson = file_get_contents($composerJsonPath);
        $composerConfig = json_decode($composerJson, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->io->writeError('<error>Could not parse composer.json: ' . json_last_error_msg() . '</error>');
            return;
        }

        // Initialize the scripts section if it doesn't exist
        if (!isset($composerConfig['scripts'])) {
            $composerConfig['scripts'] = [];
        }

        // Remove old script signatures if they exist
        $scriptsChanged = false;

        // Check and remove old script signatures @since 1.0.0
        $oldScripts = ['start', 'start:http', 'install-claude'];
        foreach ($oldScripts as $script) {
            if (isset($composerConfig['scripts'][$script])) {
                // Only remove if it matches our expected values
                $expectedValues = [
                    'start' => 'vendor/bin/start-server',
                    'start:http' => 'vendor/bin/start-server --http',
                    'install-claude' => 'vendor/bin/install-claude'
                ];

                if ($composerConfig['scripts'][$script] === $expectedValues[$script]) {
                    unset($composerConfig['scripts'][$script]);
                    $scriptsChanged = true;
                    $this->io->write("<info>Removed old script: {$script}</info>");
                }
            }
        }

        // Add our scripts with new names @since 1.0.2
        if (!isset($composerConfig['scripts']['mcp:server:start'])) {
            $composerConfig['scripts']['mcp:server:start'] = 'vendor/bin/mcp-server-start';
            $scriptsChanged = true;
        }

        if (!isset($composerConfig['scripts']['mcp:server:install'])) {
            $composerConfig['scripts']['mcp:server:install'] = 'vendor/bin/mcp-server-install';
            $scriptsChanged = true;
        }

        // Write the updated composer.json file
        if ($scriptsChanged) {
            file_put_contents(
                $composerJsonPath,
                json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->io->write('<info>Scripts updated successfully</info>');
        } else {
            $this->io->write('<info>All scripts are already up to date in composer.json</info>');
        }
    }
}
