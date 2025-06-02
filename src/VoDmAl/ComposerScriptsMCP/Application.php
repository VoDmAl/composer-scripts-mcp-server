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

use Symfony\Component\Console\Application as SymfonyApplication;
use VoDmAl\ComposerScriptsMCP\Command\ComposerServerCommand;
use VoDmAl\ComposerScriptsMCP\Command\InstallClaudeCommand;

/**
 * The Symfony CLI application for the ComposerScriptsMCP package.
 */
class Application extends SymfonyApplication
{
    /**
     * Create a new Application instance.
     */
    public function __construct()
    {
        // Set the application name and version
        parent::__construct('ComposerScriptsMCP', '1.0.0');

        // Add the commands
        $this->add(new ComposerServerCommand());
        $this->add(new InstallClaudeCommand());
    }
}
