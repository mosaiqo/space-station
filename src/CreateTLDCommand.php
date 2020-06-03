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

class CreateTLDCommand extends BaseCommand {

	/**
	 * @var
	 */
	protected $tld;

	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('make:tld')
			->setDescription('Creates a new tld')
			->addArgument('tld', InputArgument::REQUIRED);
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->tld = $input->getArgument('tld');
		$this->loadEnv();

		if ($this->checkTldDoesNotExist() ) {
			$this->header("Creating a new TLD '$this->tld' ...");
			$this->createTld();
			$this->info("TLD '$this->tld' created");

			$this->restartService('dns');
			return;
		}

		$this->info("TLD $this->tld exists already.");
	}

	/**
	 * @return int
	 */
	protected function createTld()
	{
		$directory = $this->getEnvDirectory();

		$this->fileSystem->appendToFile(
			"$directory/docker/dns/dnsmasq.conf",
			"\n### start {$this->tld} ###\nlocal=/{$this->tld}/\nserver=/{$this->tld}/127.0.0.1\naddress=/{$this->tld}/127.0.0.1\n### end {$this->tld} ###"
		);
	}

	protected function checkTldDoesNotExist()
	{
		$directory = $this->getEnvDirectory();
		$file = new \SplFileObject("$directory/docker/dns/dnsmasq.conf");

		$doesNotExists = true;

		while (!$file->eof()) {
			if ("### start {$this->tld} ###\n" === $file->fgets()) {
				$doesNotExists = false;
			}
		}

		return $doesNotExists;
	}
}
