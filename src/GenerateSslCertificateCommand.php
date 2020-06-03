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

class GenerateSslCertificateCommand extends BaseCommand {

	/**
	 * @var
	 */
	protected $domain;

	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('ssl-certificate')
			->setDescription('Starts all the containers for "Space Station"!')
			->addArgument('domain', InputArgument::REQUIRED)
			->addOption('wildcard', 'w', InputOption::VALUE_NONE, 'Creates a wildcard certificate');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null|void
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		$this->domain = $input->getArgument('domain');
		$this->header("Creating a self signed SSL cert for $this->domain");
		$this->loadEnv();
		try {
			$this->createCertificate();
		} catch (\Exception $e) {
			return 1;
		}

		$this->info("Self signed SSL cert for $this->domain created");
		$this->restartService('proxy');
		return 0;
	}

	/**
	 * @return int
	 */
	protected function createCertificate()
	{
		$directory = $this->getEnvDirectory()."/docker";

		$wildcard = $this->input->getOption('wildcard');
		$domain = $this->domain;

		if ($wildcard) {
			$domain .= " *.$this->domain";
		}

		$cmd = "mkcert ".
					 "-cert-file {$directory}/proxy/certs/{$this->domain}.crt ".
					 "-key-file {$directory}/proxy/certs/{$this->domain}.key ".
			     "$domain";

		$this->runCommand($cmd);
	}
}
