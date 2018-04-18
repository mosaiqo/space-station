<?php
namespace Mosaiqo\SpaceStation\Console;

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
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class StopCommand extends BaseCommand {
	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('stop')
			->setDescription('Stops all the containers for "Space Station"!');
	}

	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->header("Stopping Mosaiqo SpaceStation: ");
		$this->loadEnv();
		$this->stopEnvironment($output);
	}

	protected function stopEnvironment($output)
	{
		$commands = [
			'docker-compose -f ./docker/docker-compose.yml down'
		];

		array_map(function ($cmd) {
			$directory = $this->getEnvDirectory();
			$this->runCommand($cmd, $directory);
		}, $commands);
	}

}