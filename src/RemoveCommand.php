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

class RemoveCommand extends BaseCommand {
	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('remove')
			->setDescription('Removes all the containers for "Space Station"!');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->header("Removing Mosaiqo SpaceStation: ");
		$this->loadEnv();
		$this->removeEnvironment();
		$this->header("Mosaiqo SpaceStation is removed from the system!");
	}

	/**
	 * @return int
	 */
	protected function removeEnvironment()
	{
		$prefix = getenv('CONTAINER_PREFIX');

		$commands = [
			'docker-compose -f ./docker/docker-compose.yml down',
			"docker network rm $(docker network ls --filter=name=$prefix)",
			"docker image rm $(docker image ls --filter=name=$prefix)",
			"docker volume rm $(docker volume ls -qf dangling=true)",
			"docker system prune -f",
			"docker ps --filter=name=$prefix",
			"docker network ls --filter=name=$prefix",
		];

		array_map(function ($cmd) {
			$directory = $this->getEnvDirectory();
			$this->runCommand($cmd, $directory);
		}, $commands);
	}

}