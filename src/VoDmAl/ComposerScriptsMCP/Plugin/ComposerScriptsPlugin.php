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
        // Nothing to do here
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

        // Add our scripts
        $scriptsAdded = false;

        if (!isset($composerConfig['scripts']['start'])) {
            $composerConfig['scripts']['start'] = 'vendor/bin/start-server';
            $scriptsAdded = true;
        }

        if (!isset($composerConfig['scripts']['start:http'])) {
            $composerConfig['scripts']['start:http'] = 'vendor/bin/start-server --http';
            $scriptsAdded = true;
        }

        if (!isset($composerConfig['scripts']['install-claude'])) {
            $composerConfig['scripts']['install-claude'] = 'vendor/bin/install-claude';
            $scriptsAdded = true;
        }

        // Write the updated composer.json file
        if ($scriptsAdded) {
            file_put_contents(
                $composerJsonPath,
                json_encode($composerConfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            );
            $this->io->write('<info>Scripts added successfully</info>');
        } else {
            $this->io->write('<info>All scripts already exist in composer.json</info>');
        }
    }
}
