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
			->setDescription('Initializes "Space Station"!')
			->addOption('default', 'd', InputOption::VALUE_NONE, 'Use default values for config')
			->addOption('force', 'f', InputOption::VALUE_NONE, 'Overrides the files');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 * @throws \Exception
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{
		$this->info('Initializing SpaceStation...');

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
		return !$this->fileSystem->exists($this->getEnvFile());
	}

	/**
	 *
	 */
	protected function runConfigCommand()
	{
		$command = $this->getApplication()->find('config');
		try {
			$returnCode = $command->run(
				new ArrayInput([
					'-f' => $this->input->getOption('force'),
					'-d' => $this->input->getOption('default'),
					'-q' => true
				]), $this->output);
		} catch (\Exception $e) {
			$this->logger->log('error', [$e]);
		}
	}

	/**
	 * @param $output
	 * @throws \Exception
	 */
	protected function runStartCommand()
	{
		$this->info("Start SpaceStation...");
		$command = $this->getApplication()->find('start');
		$returnCode = $command->run(new ArrayInput([]), $this->output);
		if ($returnCode === 0) {
			$this->info("SpaceStation is up and running!");
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