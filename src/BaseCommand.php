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

	protected function initialize(InputInterface $input, OutputInterface $output)
	{
		parent::initialize($input, $output);
		$this->logger = new ConsoleLogger($output);
		$this->output = $output;
	}

	/**
	 *
	 */
	protected function loadEnv()
	{
		$dotenv = new Dotenv();
		$dotenv->load('.env');
	}


	protected function info ($message = "", $newLine = true) {
		$this->write("<info>$message</info>", $newLine);
	}

	protected function text ($message = "", $newLine = false) {
		$this->write("$message", $newLine);
	}

	protected function error ($message = "", $newLine = true) {
		$this->write("<error>$message</error>", $newLine);
	}

	protected function comment ($message = "", $newLine = true) {
		$this->write("<comment>$message</comment>", $newLine);
	}

	protected function question ($message = "", $newLine = true) {
		$this->write("<question>$message</question>", $newLine);
	}

	protected function header ($message = "", $newLine = true) {
		$this->write("<fg=blue>$message</>", $newLine);
	}

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




}