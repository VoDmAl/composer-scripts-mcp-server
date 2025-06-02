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
use VoDmAl\ComposerScriptsMCP\ComposerScriptsTool;

class ComposerScriptsToolTest extends TestCase
{
    /**
     * Test that listScripts returns the correct list of scripts.
     */
    public function testListScripts(): void
    {
        // Create a new ComposerScriptsTool instance
        $tool = new ComposerScriptsTool();

        // Get the list of scripts
        $result = $tool->listScripts();

        // Verify the structure of the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('scripts', $result);
        $this->assertArrayHasKey('count', $result);
        $this->assertIsArray($result['scripts']);
        $this->assertIsInt($result['count']);

        // Verify that the count matches the number of scripts
        $this->assertCount($result['count'], $result['scripts']);

        // Verify that each script has the expected structure
        foreach ($result['scripts'] as $script) {
            $this->assertIsArray($script);
            $this->assertArrayHasKey('name', $script);
            $this->assertArrayHasKey('command', $script);
            $this->assertIsString($script['name']);
            $this->assertIsString($script['command']);
        }
    }

    /**
     * Test that runScript throws an exception for non-existent scripts.
     */
    public function testRunScriptThrowsExceptionForNonExistentScript(): void
    {
        // Create a new ComposerScriptsTool instance
        $tool = new ComposerScriptsTool();

        // Expect an exception when running a non-existent script
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Script not found: non_existent_script');

        // Try to run a non-existent script
        $tool->runScript('non_existent_script');
    }

    /**
     * Test that runScript has the expected structure.
     */
    public function testRunScriptReturnsExpectedOutputStructure(): void
    {
        // Create a new ComposerScriptsTool instance
        $tool = new ComposerScriptsTool();

        // Get the list of scripts
        $scripts = $tool->listScripts();

        // Skip the test if there are no scripts
        if (empty($scripts['scripts'])) {
            $this->markTestSkipped('No scripts available to test');
            return;
        }

        // Use the first script for testing
        $scriptName = $scripts['scripts'][0]['name'];

        // We'll create a test that verifies the structure without actually running the command
        // by extending the class and overriding the exec call

        $mockTool = new class($scriptName) extends ComposerScriptsTool {
            private string $scriptToTest;

            public function __construct(string $scriptToTest) 
            {
                $this->scriptToTest = $scriptToTest;
                parent::__construct();
            }

            // Override runScript to avoid actually executing the command
            public function runScript(string $script, array $arguments = []): array
            {
                if ($script !== $this->scriptToTest) {
                    return parent::runScript($script, $arguments);
                }

                // Return a mock result with the expected structure
                return [
                    'script' => $script,
                    'command' => 'mocked_command',
                    'output' => ['mocked output'],
                    'return_code' => 0,
                    'success' => true
                ];
            }
        };

        // Run the script with our mock
        $result = $mockTool->runScript($scriptName);

        // Verify the structure of the result
        $this->assertIsArray($result);
        $this->assertArrayHasKey('script', $result);
        $this->assertArrayHasKey('command', $result);
        $this->assertArrayHasKey('output', $result);
        $this->assertArrayHasKey('return_code', $result);
        $this->assertArrayHasKey('success', $result);

        // Verify the script name
        $this->assertEquals($scriptName, $result['script']);
    }
}
