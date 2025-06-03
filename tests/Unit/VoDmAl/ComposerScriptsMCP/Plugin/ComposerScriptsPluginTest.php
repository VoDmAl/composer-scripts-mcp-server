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

namespace VoDmAl\Tests\Unit\ComposerScriptsMCP\Plugin;

use Composer\Composer;
use Composer\Config;
use Composer\IO\IOInterface;
use Composer\Script\ScriptEvents;
use PHPUnit\Framework\TestCase;
use VoDmAl\ComposerScriptsMCP\Plugin\ComposerScriptsPlugin;

class ComposerScriptsPluginTest extends TestCase
{
    /**
     * Test that the plugin subscribes to the correct events.
     */
    public function testGetSubscribedEvents(): void
    {
        $events = ComposerScriptsPlugin::getSubscribedEvents();

        $this->assertArrayHasKey(ScriptEvents::POST_INSTALL_CMD, $events);
        $this->assertArrayHasKey(ScriptEvents::POST_UPDATE_CMD, $events);
        $this->assertEquals('addScripts', $events[ScriptEvents::POST_INSTALL_CMD]);
        $this->assertEquals('addScripts', $events[ScriptEvents::POST_UPDATE_CMD]);
    }

    /**
     * Test that the plugin activates correctly.
     */
    public function testActivate(): void
    {
        $composer = $this->createMock(Composer::class);
        $io = $this->createMock(IOInterface::class);

        $plugin = new ComposerScriptsPlugin();
        $plugin->activate($composer, $io);

        // No assertions needed as we're just testing that the method doesn't throw an exception
        $this->assertTrue(true);
    }

    /**
     * Test that the plugin adds scripts to composer.json.
     */
    public function testAddScripts(): void
    {
        // Create a temporary composer.json file
        $tempDir = sys_get_temp_dir() . '/composer-scripts-plugin-test-' . uniqid('', true);
        mkdir($tempDir);
        $composerJsonPath = $tempDir . '/composer.json';

        // Create a basic composer.json file
        $composerJson = [
            'name' => 'test/test',
            'require' => [
                'php' => '>=8.1'
            ]
        ];

        file_put_contents($composerJsonPath, json_encode($composerJson));

        // Mock the IO interface
        $io = $this->createMock(IOInterface::class);

        // Create a mock Composer instance
        $composer = $this->createMock(Composer::class);

        // Create the plugin
        $plugin = new class($tempDir, $io, $composer) extends ComposerScriptsPlugin {
            private string $tempDir;

            public function __construct(string $tempDir, IOInterface $io, Composer $composer)
            {
                $this->tempDir = $tempDir;

                // Initialize the parent class
                $this->activate($composer, $io);
            }

            // Override getcwd() to return our temporary directory
            protected function getCwd(): string
            {
                return $this->tempDir;
            }
        };

        // Call the addScripts method
        $plugin->addScripts();

        // Read the updated composer.json file
        $updatedComposerJson = json_decode(file_get_contents($composerJsonPath), true);

        // Verify that the scripts were added
        $this->assertArrayHasKey('scripts', $updatedComposerJson);
        $this->assertArrayHasKey('mcp:server:start', $updatedComposerJson['scripts']);
        $this->assertArrayHasKey('mcp:server:install', $updatedComposerJson['scripts']);

        $this->assertEquals('vendor/bin/mcp-server-start', $updatedComposerJson['scripts']['mcp:server:start']);
        $this->assertEquals('vendor/bin/mcp-server-install', $updatedComposerJson['scripts']['mcp:server:install']);

        // Clean up
        unlink($composerJsonPath);
        rmdir($tempDir);
    }
}
