<?php
namespace Mosaiqo\SpaceStation\Console;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Dotenv\Dotenv;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class StartCommand extends BaseCommand {
	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('start')
			->setDescription('Starts all the containers for "Space Station"!');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->header("Starting Mosaiqo SpaceStation: ");
		$this->loadEnv();
		$this->startEnvironment();
	}

	/**
	 * @return int
	 */
	protected function startEnvironment()
	{
		$directory = getcwd();
		$prefix = getenv('CONTAINER_PREFIX');

		$commands = [
			'docker-compose -f ./docker/docker-compose.yml up --build -d',
			"docker network ls --filter=name=$prefix",
			"docker ps --filter=name=$prefix"
		];

		array_map(function ($cmd) { $this->runCommand($cmd); }, $commands);
	}
}