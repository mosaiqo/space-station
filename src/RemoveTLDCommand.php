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

class RemoveTLDCommand extends BaseCommand {

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
			->setName('remove:tld')
			->setDescription('Removes an existing tld')
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

		if ($this->checkTldDoesExist() ) {
			$this->header("Removing TLD '$this->tld' ...");
			$this->removeTld();
			$this->info("TLD '$this->tld' removed");

			$this->restartService('dns');
			return;
		}

		$this->info("TLD $this->tld exists already.");
	}

	/**
	 *
	 */
	protected function removeTld()
	{
		$directory = $this->getEnvDirectory();
		$file = new \SplFileObject("$directory/docker/dns/dnsmasq.conf");
		$temp = "";
		$skip = false;

		while (!$file->eof()) {
			$line = $file->fgets();
			if ($line === "### start {$this->tld} ###\n") {
				$skip = true;
			}

			if (!$skip) {
				$temp .= $line;
			}

			if ( $line === "### end {$this->tld} ###\n") {
				$skip = false;
			}
		}

		$this->fileSystem->dumpFile(
			"$directory/docker/dns/dnsmasq.conf",
			$temp
		);
	}

	protected function checkTldDoesExist()
	{
		$directory = $this->getEnvDirectory();
		$file = new \SplFileObject("$directory/docker/dns/dnsmasq.conf");

		$doesExists = false;

		while (!$file->eof()) {
			if ("### start {$this->tld} ###\n" === $file->fgets()) {
				$doesExists = true;
			}
		}

		return $doesExists;
	}
}
