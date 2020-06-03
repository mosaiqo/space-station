<?php

namespace Mosaiqo\SpaceStation\Console;

use Symfony\Component\Dotenv\Dotenv;
use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Class BaseCommand
 * @package Mosaiqo\SpaceStation\Console
 */
class BaseCommand extends Command
{
	/**
	 * @var
	 */
	protected $output;

	/**
	 * @var
	 */
	protected $progressBar;

	/**
	 * @var
	 */
	protected $logger;

	/**
	 * @var
	 */
	protected $fileSystem;

	/**
	 * @var
	 */
	protected $input;

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 */
	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		$this->logger = new ConsoleLogger($output);
		$this->output = $output;
		$this->input = $input;
		$this->fileSystem = new Filesystem();
	}

	/**
	 *
	 */
	protected function loadEnv()
	{
		$dotenv = new Dotenv();
		$dotenv->load($this->getEnvFile());
	}

	/**
	 * @return mixed
	 */
	protected function envFileExists()
	{
		return $this->fileSystem->exists($this->getEnvFile());
	}

	/**
	 * @param string $message
	 * @param bool $newLine
	 */
	protected function info ($message = "", $newLine = true) {
		$this->write("<info>$message</info>", $newLine);
	}

	/**
	 * @param string $message
	 * @param bool $newLine
	 */
	protected function text ($message = "", $newLine = false) {
		$this->write("$message", $newLine);
	}

	/**
	 * @param string $message
	 * @param bool $newLine
	 */
	protected function error ($message = "", $newLine = true) {
		$this->write("<error>$message</error>", $newLine);
	}

	/**
	 * @param string $message
	 * @param bool $newLine
	 */
	protected function comment ($message = "", $newLine = true) {
		$this->write("<comment>$message</comment>", $newLine);
	}

	/**
	 * @param string $message
	 * @param bool $newLine
	 */
	protected function question ($message = "", $newLine = true) {
		$this->write("<question>$message</question>", $newLine);
	}

	/**
	 * @param string $message
	 * @param bool $newLine
	 */
	protected function header ($message = "", $newLine = true) {
		$this->write("<fg=blue>$message</>", $newLine);
	}

	/**
	 * @param string $message
	 * @param bool $newLine
	 */
	protected function write ($message = "", $newLine = false) {
		if ($newLine) {
			$this->output->writeln($message);
		} else {
			$this->output->write($message);
		}
	}

	/**
	 * @param string $cmd
	 * @param null $directory
	 * @param null $env
	 * @param null $input
	 * @param null $timeout
	 * @return string
	 */
	protected function runCommand($cmd = '', $directory = null, $env = null, $input = null, $timeout = null) {
		$process = new Process($cmd, $directory, $env, $input, $timeout);
		if ('\\' !== DIRECTORY_SEPARATOR && file_exists('/dev/tty') && is_readable('/dev/tty')) {
			$process->setTty(true);
		}

		$process->run(function ($type, $line) {
			$this->text($line);
		});

		$this->text("\n");

		return $process->getOutput();
	}

	/**
	 * @return array|false|null|string
	 */
	protected function getHomeDirectory() {
		// Cannot use $_SERVER superglobal since that's empty during UnitUnishTestCase
		// getenv('HOME') isn't set on Windows and generates a Notice.
		$home = getenv('HOME');
		if (!empty($home)) {
			// home should never end with a trailing slash.
			$home = rtrim($home, '/');
		}
		elseif (!empty($_SERVER['HOMEDRIVE']) && !empty($_SERVER['HOMEPATH'])) {
			// home on windows
			$home = $_SERVER['HOMEDRIVE'] . $_SERVER['HOMEPATH'];
			// If HOMEPATH is a root directory the path can end with a slash. Make sure
			// that doesn't happen.
			$home = rtrim($home, '\\/');
		}

		return empty($home) ? NULL : $home;
	}

	/**
	 * @return string
	 */
	protected function getEnvDirectory () {
		return $this->getHomeDirectory() . DIRECTORY_SEPARATOR . '.space-station';
	}

	/**
	 * @return string
	 */
	protected function getEnvFile () {
		$directory = $this->getEnvDirectory();
		return $directory. DIRECTORY_SEPARATOR . '.env';
	}

	/**
	 * @param $name
	 */
	protected function restartService ($name)
	{
		$this->info("Restarting service $name ...");
		$this->loadEnv();
		$prefix = getenv('CONTAINER_PREFIX');

		$cmd = "docker restart {$prefix}-{$name}";
		$this->runCommand($cmd);
		sleep(30);
		$this->info("Restarted service $name");
	}
}
