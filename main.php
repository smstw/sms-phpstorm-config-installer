#!/usr/bin/env php
<?php
/**
 * Part of sms-phpstorm-config-installer project.
 *
 * @copyright  Copyright (C) 2011 - 2015 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

// Force strict mode
error_reporting(32767);

$app = new Application;

$app->execute();

/**
 * The Application class.
 */
class Application
{
	/**
	 * Property calledScript.
	 *
	 * @var string
	 */
	protected $calledScript;

	/**
	 * Property args.
	 *
	 * @var  array
	 */
	protected $args = array();

	/**
	 * Property options.
	 *
	 * @var  array
	 */
	protected $options = array();

	/**
	 * Execute this application.
	 *
	 * @return  void
	 */
	public function execute()
	{
		// Prepare
		$this->registerException();
		$this->parseArguments();

		// Start logic
		$this->validateCommands();
		$this->installConfigFiles();
	}

	/**
	 * Install Config Files.
	 *
	 * @return void
	 */
	protected function installConfigFiles()
	{
		$sourceDir = __DIR__ . '/res/config';
		$targetDir = $this->getTargetDir();

		if (!is_dir($sourceDir))
		{
			throw new \RuntimeException(sprintf('"%s" Source directory is not exists.', $sourceDir));
		}

		if (!is_dir($targetDir))
		{
			throw new \RuntimeException(sprintf('"%s" Target directory is not exists.', $targetDir));
		}

		foreach ($this->loadConfigFromGitHub() as $target => $config)
		{
			$targetFile = $targetDir . '/' . $target;
			$dir = dirname($targetFile);

			if (! is_dir($dir))
			{
				mkdir($dir, 0755, true);
			}

			file_put_contents($targetFile, $config);

			$this->out(sprintf("%s => %s", $target, $targetFile));
		}
	}

	/**
	 * Load config from GitHub repository
	 *
	 * @return  array
	 */
	protected function loadConfigFromGitHub()
	{
		$this->out('Downloading config files...');

		$urlPrefix = 'https://raw.githubusercontent.com/smstw/sms-phpstorm-config-installer/master/res/';
		$files = file($urlPrefix . 'config-file-list.txt');
		$configs = [];

		foreach ($files as $file)
		{
			$file = str_replace("\n", '', $file);
			$file = trim($file);

			$configs[$file] = file_get_contents($urlPrefix . $file);
		}

		return $configs;
	}

	/**
	 * Get target directory
	 *
	 * @return string|bool Return false when the target directory is not exists.
	 */
	protected function getTargetDir()
	{
		$version = $this->getOption('ide-version', $this->getOption('i', 8));

		$folderName = 'WebIde' . str_pad($version, 2, 0);
		$os = php_uname();

		if (preg_match('#^Windows#i', $os))
		{
			// Windows path
			$configPath = getenv('HOMEDRIVE') . getenv("HOMEPATH") . '\\.' . $folderName;
		}
		elseif (preg_match('#^Linux#i', $os))
		{
			// Linux path
			$configPath = '~/.' . $folderName;
		}
		else
		{
			// Mac OSX path
			$configPath = $_SERVER['HOME'] . '/Library/Preferences/' . $folderName;
		}

		return $configPath;
	}

	/**
	 * Validate command arguments
	 *
	 * @return void
	 */
	protected function validateCommands()
	{
		if ('install' != $this->getArgument(0))
		{
			$this->out($this->usage());

			exit;
		}
	}

	/**
	 * getOption
	 *
	 * @param string $key
	 * @param mixed  $default
	 *
	 * @return  mixed
	 */
	public function getOption($key, $default = null)
	{
		if (isset($this->options[$key]))
		{
			return $this->options[$key];
		}

		return $default;
	}

	/**
	 * getArgument
	 *
	 * @param string $offset
	 * @param mixed  $default
	 *
	 * @return  mixed
	 */
	public function getArgument($offset, $default = null)
	{
		if (isset($this->args[$offset]))
		{
			return $this->args[$offset];
		}

		return $default;
	}

	/**
	 * Show command usage
	 *
	 * @return string
	 */
	protected function usage()
	{
		return <<<EOF
Usage: phpstorm-config-installer install [--ide-version|-i]

Install all PhpStorm config files.

Options:
  -i|--ide-version    PHPStorm version (Default is 8)
  -v                  More details to debug

EOF;
	}

	/**
	 * Show error messages
	 *
	 * @param \Exception|string $exception
	 *
	 * @return void
	 */
	public function error($exception)
	{
		if ($exception instanceof \Exception)
		{
			fputs(STDERR, $exception->getMessage());

			if ($this->getOption('v'))
			{
				fputs(STDERR, $exception->getTraceAsString());
			}
		}
		else
		{
			fputs(STDERR, $exception);
		}

		exit;
	}

	/**
	 * out
	 *
	 * @param string $string
	 * @param bool   $nl
	 *
	 * @return  $this
	 */
	public function out($string = '', $nl = true)
	{
		$br = $nl ? "\n" : null;

		fputs(STDOUT, $string . $br);

		return $this;
	}

	/**
	 * registerException
	 *
	 * @return  void
	 */
	protected function registerException()
	{
		restore_exception_handler();
		set_exception_handler(array($this, 'error'));
	}

	/**
	 * Initialise the options and arguments
	 *
	 * @return  void
	 *
	 * @see     https://github.com/ventoviro/windwalker-io/blob/master/Cli/Input/CliInput.php#L159
	 */
	protected function parseArguments()
	{
		$argv = $_SERVER['argv'];
		$this->calledScript = array_shift($argv);
		$out = array();

		for ($i = 0, $j = count($argv); $i < $j; $i++)
		{
			$arg = $argv[$i];

			// --foo --bar=baz
			if (substr($arg, 0, 2) === '--')
			{
				$eqPos = strpos($arg, '=');

				// --foo
				if ($eqPos === false)
				{
					$key = substr($arg, 2);

					// --foo value
					if ($i + 1 < $j && $argv[$i + 1][0] !== '-')
					{
						$value = $argv[$i + 1];
						$i++;
					}
					else
					{
						$value = isset($out[$key]) ? $out[$key] : true;
					}

					$out[$key] = $value;
				}

				// --bar=baz
				else
				{
					$key       = substr($arg, 2, $eqPos - 2);
					$value     = substr($arg, $eqPos + 1);
					$out[$key] = $value;
				}
			}

			// -k=value -abc
			else
			{
				if (substr($arg, 0, 1) === '-')
				{
					// -k=value
					if (substr($arg, 2, 1) === '=')
					{
						$key       = substr($arg, 1, 1);
						$value     = substr($arg, 3);
						$out[$key] = $value;
					}

					// -abc
					else
					{
						$chars = str_split(substr($arg, 1));

						foreach ($chars as $char)
						{
							$key       = $char;
							$value     = isset($out[$key]) ? $out[$key] : true;
							$out[$key] = $value;
						}

						// -a a-value
						if ((count($chars) === 1) && ($i + 1 < $j) && ($argv[$i + 1][0] !== '-'))
						{
							$out[$key] = $argv[$i + 1];
							$i++;
						}
					}
				}
				// plain-arg
				else
				{
					$this->args[] = $arg;
				}
			}
		}

		$this->options = $out;
	}
}
