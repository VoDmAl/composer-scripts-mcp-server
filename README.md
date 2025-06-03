# VoDmAl Composer Scripts MCP Server

A PHP library that exposes your project's Composer scripts as MCP (Model Context Protocol) tools. Once installed, AI assistants (or any MCP client) can discover and execute your Composer scripts automatically—no need to explain commands each time.

This works with **any PHP project**, and you can connect **multiple projects** without limits. Each project's MCP tool name is derived automatically from its `composer.json` (e.g., the `"name"` field).

## Why Use Composer Scripts?

Composer scripts let you standardize and automate common tasks (tests, linting, code generation, etc.) without adding extra dependencies. Key benefits include:

- **Zero extra dependencies**: Scripts work immediately after `composer install`.
- **Consistent interface**: Every team member uses the same commands, regardless of OS or local setup.
- **Self-documenting workflows**: Your `composer.json` doubles as documentation for available commands.
- **Cross-platform**: Scripts run uniformly on macOS, Linux, and Windows.

While this approach has many advantages, there are some limitations:
- You need to remember the specific script names for each project
- Complex script chains can be harder to debug than standalone tools
- Performance may be slightly slower than native binaries

Despite these minor drawbacks, the convenience of having commands available immediately after `composer install` without additional external dependencies is a significant advantage. This is where this tool comes in - it allows AI assistants to discover and execute your Composer scripts without you having to explain the commands each time.

### Real-World Example

Here's an example of how powerful Composer scripts can be in a real project:

```
"scripts": {
    "check:before:commit": [
        "@test",
        "@check:code:style",
        "@check:code:static",
        "@check:code:dependency"
    ],
    "check:before:push": [
        "@check:config",
        "@check:before:commit",
        "@check:code:security"
    ],
    "check:code:static": [
        "@check:code:lint",
        "@rector",
        "@psalm",
        "@phpstan",
        "@classleak",
        "@check:code:comments"
    ],
    "test": [
        "@test:unit:phpunit"
    ],
    "test:unit:phpunit": [
        "@phpunit --testsuite Unit"
    ],
    "phpunit": "./vendor/bin/phpunit"
}
```

With these scripts, developers can run comprehensive checks before committing or pushing code with simple commands like `composer check:before:commit`. The MCP server exposes these scripts to AI assistants, allowing them to understand and execute the appropriate commands without requiring you to explain your project's specific testing or validation procedures each time.

One of the biggest advantages of this approach is that you don't need to repeatedly explain to each LLM how to run tests, check code style, or perform other common development tasks. Instead, the LLM can discover the available commands through the MCP server and suggest the appropriate command for the task at hand. This saves time and reduces friction when working with AI assistants on your PHP projects.

## Features

By installing **VoDmAl Composer Scripts MCP Server**, you get two major advantages:

1. **Automatic discovery** of all Composer scripts in your project as MCP tools.
2. **One-step integration** with AI assistants (e.g., Claude Desktop), so they can list and run any script without manual setup.

- Automatically reads Composer scripts from composer.json
- Exposes scripts as MCP tools that can be discovered and executed
- Support for both stdio and HTTP transports
- Simple integration with any PHP project
- Automatically adds scripts to your project's composer.json when installed

## Requirements

- PHP 8.1 or higher
- Composer
- php-mcp/server ^2.1

## Simple Installation & Automatic Setup

To get started, run:

```bash
composer require vodmal/composer-scripts-mcp-server
composer install
```

As soon as Composer finishes, your project's composer.json will include two new scripts:
```
"scripts": {
    "mcp:server:start": "vendor/bin/start-server",
    "mcp:server:install": "vendor/bin/install-claude"
}
```

You now have everything you need. You do not need to run composer mcp:server:start immediately. Instead, if you want to integrate with Claude Desktop (or any MCP-compatible LLM), run:
```
composer mcp:server:install
```

If Claude Desktop is installed and its configuration file is detected, this command merges the MCP configuration automatically.

If it doesn't find Claude's config, it prints out a JSON snippet. Paste that snippet into your claude_config.json under "tools" and restart Claude.

**Connect to any number of projects**
You can run composer mcp:server:install in each PHP project folder without limit. Each project's tool name is pulled from its own composer.json "name" field, so multiple projects appear distinctly in Claude's tool list.

If you're not using Claude
Running:
```
composer mcp:server:install
```

will output a JSON block showing exactly how to launch the MCP server. Copy that into whatever LLM or MCP client configuration you need. Once that's configured, your AI assistant can call the composer_list and composer_run MCP methods without further setup.



## Advanced Manual Configuration & Startup

### With stdio Transport

To start the server with stdio transport (for integration with AI assistants):

```bash
vendor/bin/start-server
```

### With HTTP Transport

To start the server with HTTP transport (for testing with the client):

```bash
vendor/bin/start-server --http [--host=<host>] [--port=<port>]
```

By default, the server will listen on `127.0.0.1:8088`.

## Integration with Claude Desktop

To integrate this MCP server with Claude Desktop, you can use the provided `install-claude` command:

```bash
vendor/bin/install-claude
```

This command will automatically detect your operating system and search for the Claude Desktop configuration file. If found, it will update it to include the MCP server configuration. If not found, it will output the configuration that you need to manually merge with your Claude Desktop configuration file. The server will automatically expose all Composer scripts in your project as MCP tools.

### Command Options

- `--output=<file>`: Specify a custom output file (by default, the command will try to find the Claude Desktop configuration file for your OS)
- `--name=<name>`: Specify the server name (default: the project name from composer.json)

### Multiple Servers

If you have multiple PHP projects with this library installed, you can add each of them to the same configuration file:

```bash
# In project1
vendor/bin/install-claude --name=project1

# In project2
vendor/bin/install-claude --name=project2 --output=/path/to/claude_desktop_config.json
```

## Using the Configuration

1. **Restart Claude Desktop completely**
2. After restarting, you should see a slider icon in the bottom left corner of the input box
3. Click on the slider icon to see the available tools (your Composer scripts)
4. You can now ask Claude to run any of your Composer scripts

## Troubleshooting

If the server isn't being picked up by Claude Desktop:

1. Make sure Claude Desktop is on the latest version
2. Check that the configuration file syntax is correct
3. Verify that the file paths in the configuration are valid and absolute (not relative)
4. Look at logs to see why the server is not connecting:
   - macOS: `~/Library/Logs/Claude`
   - Windows: `%APPDATA%\Claude\logs`
5. Try manually running the server to see if you get any errors:
   ```bash
   vendor/bin/start-server
   ```

> **Note:** When using stdio transport with LLM applications, all log messages are written to stderr instead of stdout. This prevents log messages from interfering with the JSON communication between the server and the LLM application, which could cause JSON parsing errors.

## Available MCP Tools

The library provides the following MCP tools:

### composer_list

Lists all available Composer scripts.

**Parameters:** None

**Returns:**
- `scripts`: Array of script information (name and command)
- `count`: Number of available scripts

### composer_run

Runs a Composer script.

**Parameters:**
- `script` (string, required): The name of the script to run
- `arguments` (array, optional): Additional arguments to pass to the script

**Returns:**
- `script`: The name of the script that was run
- `command`: The command that was executed
- `output`: The output of the command
- `return_code`: The return code of the command
- `success`: Whether the command was successful (return code 0)

## License

This project is dual-licensed:

### Open Source License
This project is licensed under the GNU General Public License v3.0 (GPL-3.0) for open source use. See the [LICENSE-GPL](LICENSE-GPL) file for details.

### Commercial License
For commercial use in proprietary software, a separate commercial license is available. Contact [ваш email] for commercial licensing terms.

### License Summary
- **GPL-3.0**: Free for open source projects that are also GPL-3.0 licensed
- **Commercial**: Paid license for proprietary/commercial use without GPL restrictions

If you're unsure which license applies to your use case, please contact us.