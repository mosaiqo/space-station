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

/**
 * Class StopCommand
 * @package Mosaiqo\SpaceStation\Console
 * @author Boudy de Geer <boudydegeer@mosaiqo.com>
 */
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

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->header("Stopping SpaceStation: ");
		$this->loadEnv();
		$this->stopEnvironment($output);
	}

	/**
	 * @param $output
	 */
	protected function stopEnvironment($output)
	{
		$commands = [
			'docker-compose -f ./docker/docker-compose.yml down --remove-orphans'
		];

		array_map(function ($cmd) {
			$directory = $this->getEnvDirectory();
			$this->runCommand($cmd, $directory);
		}, $commands);
	}

}