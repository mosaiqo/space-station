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
 * Class ClearCacheCommand
 * @package Mosaiqo\SpaceStation\Console
 * @author Boudy de Geer <boudydegeer@mosaiqo.com>
 */
class ClearCacheCommand extends BaseCommand {
	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('cache:clear')
			->setDescription('Clears cache for your machine "Space Station"!');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->header("Clearing cache SpaceStation: ");
		$this->loadEnv();
		$this->clearCache($output);
	}

	/**
	 * @param $output
	 */
	protected function clearCache($output)
	{
		$commands = [
			'sudo killall -HUP mDNSResponder;sudo killall mDNSResponderHelper;sudo dscacheutil -flushcache'
		];

		array_map(function ($cmd) {
			$directory = $this->getEnvDirectory();
			$this->runCommand($cmd, $directory);
		}, $commands);
	}

}