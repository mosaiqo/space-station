<?php
namespace Mosaiqo\SpaceStation\Console;

use Symfony\Component\Console\Question\ConfirmationQuestion;
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
			->setDescription('Removes all the containers for "Space Station"!')
			->addOption('force', 'f', InputOption::VALUE_NONE, 'Forces removing')
			->addOption('all', 'a', InputOption::VALUE_NONE, 'Removes all');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$helper = $this->getHelper('question');
		if ($this->input->getOption('force')) {
			$remove = true;
		} else {
			$all = $this->input->getOption('all');
			$msg = "This is going to remove all your containers for Space Station.";

			if ($all) {
				$msg = "This is going to remove all your containers, volumes, networks, images and config for Space Station.";
			}

			$this->comment($msg);

			$remove = $helper->ask($this->input, $this->output, new ConfirmationQuestion(
				'Are you sure? (no): ',
				false,
				'/^(y|j)/i'
			));
		}

		if (!$remove) { return 0; }


		$this->header("Removing SpaceStation: ");
		$this->loadEnv();
		$this->removeEnvironment();
		$this->header("SpaceStation is removed from the system!");

		return 0;
	}

	/**
	 * @return int
	 */
	protected function removeEnvironment()
	{
		$prefix = getenv('CONTAINER_PREFIX');
		$all = $this->input->getOption('all');
		$commands = [
			'docker-compose -f ./docker/docker-compose.yml down',
		];

		if ($all) {
			$commands = array_merge($commands, [
				"docker network rm $(docker network ls --filter name={$prefix})",
				"docker image rm $(docker image ls --filter name={$prefix})",
				"docker volume rm $(docker volume ls -qf name={$prefix})",
				"docker volume rm $(docker volume ls -qf dangling=true)",
//				"docker image rm -f $(docker image ls -a | grep space-station)",
				"docker system prune -f"
			]);
		}

		array_map(function ($cmd) {
			$directory = $this->getEnvDirectory();
			$this->runCommand($cmd, $directory);
		}, $commands);

		if ($all) {
			$this->fileSystem->remove($this->getEnvDirectory());
		}
	}

}
