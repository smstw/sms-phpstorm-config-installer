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
			$configPath = getenv('HOME') . '/' . $folderName;
		}
		else
		{
			// Mac OSX path
			$configPath = getenv('HOME') . '/Library/Preferences/' . $folderName;
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
	    $this->calledScript = $argv[0];
	    $out = array();
	
	    for ($i = 1, $max = count($argv); $i < $max; $i++)
	    {
	        $arg = $argv[$i];
	        $nextArg = isset($argv[$i + 1]) ? $argv[$i + 1] : false;
	        $nextArg = (false !== $nextArg && '-' !== $nextArg[0]) ? $nextArg : false;
	        $type = '';
	        $type = ('-' === substr($arg, 0, 1)) ? '-' : $type;
	        $type = ('--' === substr($arg, 0, 2)) ? '--' : $type;
	        $type = ('-' === $type && strpos($arg, '=')) ? '=' : $type;
	        $type = ('--' === $type && strpos($arg, '=')) ? '==' : $type;
	
	        if (in_array($type, ['-', '--', '=', '==']))
	        {
	            $key = substr($arg, strlen($type));
	            $type = empty($key) ? '' : $type;
	
	            // Case: "-abc" and "-f bar"
	            if ('-' === $type)
	            {
	                if (1 === strlen($key))
	                {
	                    $out[$key] = (false === $nextArg) ?: $nextArg;
	                    $i += (false === $nextArg ? 0 : 1);
	                }
	                else
	                {
	                    foreach (str_split($key) as $char)
	                    {
	                        $out[$char] = (false === isset($out[$char])) ?: $out[$char];
	                    }
	                }
	            }
	            // Case: "--foo" and "--foo bar"
	            elseif ('--' === $type)
	            {
	                $out[$key] = (false === $nextArg) ?: $nextArg;
	                $i += (false === $nextArg ? 0 : 1);
	            }
	            // Case: "-f=bar" ("-foo=bar" is the same as "-f=bar") and "--foo=bar"
	            else
	            {
	                list($key, $value) = explode('=', $key, 2);
	
	                if (!empty($key))
	                {
	                    $key = ('=' === $type) ? $key[0] : $key;
	
	                    $out[$key] = $value;
	                }
	            }
	        }
	        else
	        {
	            $this->args[] = $arg;
	        }
	    }
	
	    $this->options = $out;
	}
}
