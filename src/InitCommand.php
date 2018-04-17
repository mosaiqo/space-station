<?php

namespace Mosaiqo\SpaceStation\Console;

use Symfony\Component\Dotenv\Dotenv;
use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

class InitCommand extends BaseCommand
{
	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('init')
			->setDescription('Initializes "Space Station"!');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$this->info('Init Mosaiqo SpaceStation...');

		// If its not configured we need to configure the environment
		if ($this->isItConfigured()) {
			$this->runConfigCommand();
		};

		$this->loadEnv();
		$this->createNetwork();

		$this->runStartCommand();
	}

	/**
	 * @return bool
	 */
	protected function isItConfigured()
	{
		$fileSystem = new Filesystem();
		$file = getcwd() . '/.env';
		return !$fileSystem->exists($file);
	}

	/**
	 *
	 */
	protected function runConfigCommand()
	{
		$command = $this->getApplication()->find('config');
		try {
			$returnCode = $command->run(new ArrayInput(['-q' => true]), $this->output);
		} catch (\Exception $e) {
			$this->logger->log('error', [$e]);
		}

//		$this->output->writeln($returnCode);
	}

	/**
	 * @param $output
	 * @throws \Exception
	 */
	protected function runStartCommand()
	{
		$this->info("Start Mosaiqo SpaceStation...");
		$command = $this->getApplication()->find('start');
		$returnCode = $command->run(new ArrayInput([]), $this->output);
		if ($returnCode === 0) {
			$this->info("Mosaiqo SpaceStation is up and running!");
		}
	}

	/**
	 *
	 */
	protected function createNetwork()
	{
		$networkName = getenv('NETWORK_NAME');
		$process = new Process("docker network ls -q --filter=name=$networkName");
		$process->run();

		$networkExists =  $process->getOutput();

		if (!$networkExists) {
			$this->info("Creating network: $networkName");
			$process = new Process("docker network create $networkName");
			$process->run();
			$this->info("Network $networkName created!");
		} else {
			$this->info("Network $networkName already exists!");
		}
	}
}