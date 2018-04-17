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
			->addArgument('domain', InputArgument::REQUIRED);
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
		$this->createCertificate();

		$this->info("Self signed SSL cert for $this->domain created");
	}

	/**
	 * @return int
	 */
	protected function createCertificate()
	{
		$directory = getcwd()."/docker";
		$country = getenv('CERT_COUNTRY');
		$state = getenv('CERT_STATE');
		$city = getenv('CERT_CITY');
		$company = getenv('CERT_COMPANY');
		$department = getenv('CERT_DEPARTMENT');


		$fileSystem = new Filesystem();
		$fileSystem->remove("$directory/ssl/openssl.cnf");
		$fileSystem->copy("/etc/ssl/openssl.cnf", "$directory/ssl/openssl.cnf");

		$fileSystem->appendToFile(
			"$directory/ssl/openssl.cnf",
			"[SAN]\nsubjectAltName=DNS:hostname,IP:127.0.0.1"
		);

		$cmd = "openssl req -x509 -nodes -days 365 -newkey rsa:2048 ".
			"-keyout $directory/nginx/certs/$this->domain.key ".
			"-out $directory/nginx/certs/$this->domain.crt ".
			"-subj '/C=$country/ST=$state/L=$city/O=$company/OU=$department/CN=$this->domain' ".
			"-reqexts SAN ".
			"-extensions SAN ".
			"-config $directory/ssl/openssl.cnf";

		$this->runCommand($cmd);
	}
}