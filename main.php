#!/usr/bin/env php
<?php
/**
 * Part of sms-phpstorm-config-installer project.
 *
 * @copyright  Copyright (C) 2011 - 2015 SMS Taiwan, Inc. All rights reserved.
 * @license    GNU General Public License version 2 or later; see LICENSE
 */

validateCommands();
installConfigFiles();

/**
 * installConfigFiles
 *
 * @return void
 */
function installConfigFiles()
{
	$sourceDir = __DIR__ . '/res/config';
	$targetDir = getTargetDir();

	if (!is_dir($sourceDir))
	{
		error(sprintf('"%s" Source directory is not exists.', $sourceDir));
	}

	if (!is_dir($targetDir))
	{
		error(sprintf('"%s" Target directory is not exists.', $targetDir));
	}

	foreach (loadConfigFromGitHub() as $target => $config)
	{
		$targetFile = $targetDir . '/' . $target;
		$dir = dirname($targetFile);

		if (! is_dir($dir))
		{
			mkdir($dir, 0755, true);
		}

		file_put_contents($targetFile, $config);

		printf("%s => %s\n", $target, $targetFile);
	}
}

/**
 * Load config from GitHub repository
 *
 * @return  array
 */
function loadConfigFromGitHub()
{
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
function getTargetDir()
{
	$options = getOptions();

	$folderName = 'WebIde' . str_pad($options['ide-version'], 2, 0);
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
		$configPath = '~/Library/Preferences/' . $folderName;
	}

	return $configPath;
}

/**
 * Validate command arguments
 *
 * @return void
 */
function validateCommands()
{
	if (!isset($_SERVER['argv'][1])
		|| 'install' !== $_SERVER['argv'][1])
	{
		echo usage();
		exit;
	}
}

/**
 * Get options
 *
 * @return array
 */
function getOptions()
{
	$argv = $_SERVER['argv'];
	$options = array();
	$max = count($argv);

	$options['ide-version'] = 8;

	for ($i = 0; $i < $max; ++$i)
	{
		$arg = $argv[$i];

		if (preg_match('/^\-\-ide\-version=/i', $arg))
		{
			list(, $value) = explode('=', $arg, 2);

			$options['ide-version'] = $value;
		}

		if ('-i' === $arg)
		{
			++$i;
			$value = $argv[$i];

			$options['ide-version'] = $value;
		}
	}

	return $options;
}

/**
 * Show command usage
 *
 * @return string
 */
function usage()
{
	return <<<EOF
Usage: phpstorm-config-installer install [--ide-version|-i]

Install all PhpStorm config files.

Options:
  --ide-version        -i PHPStorm version (Default is 8)
EOF;
}

/**
 * Show error messages
 *
 * @param string $message
 *
 * @return void
 */
function error($message)
{
	echo $message . "\n";

	exit;
}
