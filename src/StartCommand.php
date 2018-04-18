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
			->setDescription('Starts all the containers for "Space Station"!')
			->addOption('default', 'd', InputOption::VALUE_NONE, 'Use default values for config')
			->addOption('force', 'f', InputOption::VALUE_NONE, 'Overrides the files');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		if (!$this->envFileExists())
		{
			$this->text("Space Station is not configured! \nYou need to run first: ");
			$this->comment("space-station init");
			return 1;
		}

		$this->header("Starting SpaceStation: ");
		$this->loadEnv();
		$this->startEnvironment();
	}

	/**
	 * @return int
	 */
	protected function startEnvironment()
	{

		$prefix = getenv('CONTAINER_PREFIX');
		$dbUser = getenv('DB_USER');

		$commands = [
			"docker-compose -f ./docker/docker-compose.yml up --build -d",
			"docker network ls --filter=name=$prefix",
			"docker ps --filter=name=$prefix"
		];

		array_map(function ($cmd) {
			$directory = $this->getEnvDirectory();
			$this->runCommand($cmd, $directory);
			}, $commands);
	}
}