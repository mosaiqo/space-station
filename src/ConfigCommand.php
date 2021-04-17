<?php

namespace Mosaiqo\SpaceStation\Console;

use ZipArchive;
use RuntimeException;
use GuzzleHttp\Client;
use Symfony\Component\Process\Process;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;

/**
 * Class ConfigCommand
 * @package Mosaiqo\SpaceStation\Console
 */
class ConfigCommand extends BaseCommand
{
	/**
	 * @var array
	 */
	private $defaults = [
		'HTTP_PORT' => [
			'text' => 'Please enter the HTTP port: (80): ',
			'default' => 80
		],
		'HTTPS_PORT' =>  [
			'text' => 'Please enter the HTTPS port: (443): ',
			'default' => 443
		],
		'NETWORK_NAME' =>  [
			'text' => 'Please enter the network name: (space-station-network): ',
			'default' => 'space-station-network'
		],
		'REDIS_PORT' =>  [
			'text' => 'Please enter the redis port: (6379): ',
			'default' => 6379
		],
		'MONGODB_PORT' =>  [
			'text' => 'Please enter the mongo db port: (27017): ',
			'default' => 27017
		],
		'DB_PORT' =>  [
			'text' => 'Please enter the mysql port: (3306): ',
			'default' => 3306
		],
		'MAILHOG_SMTP_PORT' =>  [
			'text' => 'Please enter the mailhog port: (1025): ',
			'default' => 1025
		],
		'DB_ROOT_PASS' =>  [
			'text' => 'Please enter the mysql root password: (secret): ',
			'default' => 'secret'
		],
		'CONTAINER_PREFIX' =>  [
			'text' => 'Please enter the prefix for your containers: (space-station): ',
			'default' => 'space-station'
		],
		'TLD' =>  [
			'text' => 'Which TLD would you like to be configured for your dev env (local): ',
			'default' => 'local'
		],
	];

	/**
	 * @var array
	 */
	private $configs = [
		'env' => [],
		'services' => []
	];


	/**
	 * @var array
	 */
	private $services = [
		'proxy', 'dns', 'mysql', 'redis', 'mongo', 'mailhog', 'whoami'
	];
	/**
	 * Configure the command options.
	 *
	 * @return void
	 */
	protected function configure()
	{
		$this
			->setName('config')
			->setDescription('Configures "Space Station"!')
			->addOption('default', 'd', InputOption::VALUE_NONE, 'Use default values for config')
			->addOption('force', 'f', InputOption::VALUE_NONE, 'Overrides the files');
	}

	/**
	 * @param InputInterface $input
	 * @param OutputInterface $output
	 * @return int|null
	 */
	public function execute(InputInterface $input, OutputInterface $output)
	{
		if ($this->envFileExists()) {
			$helper = $this->getHelper('question');
			$force = $input->getOption('force');
			$override = $force?:$helper->ask($input, $output, new ConfirmationQuestion(
				'There is already a config file, do you want to override it? [yes/no] (no) ',
				false,
				'/^(y|j)/i'
			));

			if (!$override) return 0;

			$this->removeEnvFile();
		}

		if ($input->getOption('default')) {
			$this->createConfigFileByDefault();
		} else {
			$this->createConfigFileOnUserInput($input, $output);
		}

		$this->createEnvFile();
		$this->createDockerFile();

		return 0;
	}

	/**
	 *
	 */
	protected function createEnvFile () {
		$envFile = $this->getEnvFile();

		$this->fileSystem->mkdir($this->getEnvDirectory());
		$this->fileSystem->touch($envFile);

		foreach ($this->configs['env'] as $KEY => $value) {
			$this->fileSystem->appendToFile($envFile, "$KEY=$value\n");
		}

		$this->info("Saved .env file");
	}

	/**
	 *
	 */
	protected function removeEnvFile () {
		if ($this->envFileExists()) {
			$this->fileSystem->remove($this->getEnvFile());
		}
	}

	/**
	 * @param $output
	 */
	protected function createConfigFileOnUserInput($input, $output)
	{
		$helper = $this->getHelper('question');

		$serviceQuestion = new ChoiceQuestion(
			'Please select the services you would like to install ('. implode(', ', $this->services).')',
			$this->services,
			implode(', ', array_keys($this->services))
		);

		$serviceQuestion->setMultiselect(true);

		$this->configs['services'] = $helper->ask($input, $output, $serviceQuestion);

		foreach ($this->defaults as $key => $value) {
			$inputVal = $helper->ask($input, $output, new Question($value['text'], $value['default']));
			if (strstr($inputVal, " ")) { $inputVal = "'$inputVal'";}
			$this->configs['env'][$key] = $inputVal;
		}

		$this->header("This are your selected services: \n");
		$this->info(implode(', ', $this->configs['services']) . " \n");

		$this->header("This is the config:");
		foreach ($this->configs['env'] as $KEY => $config) {
			$this->info("$KEY=$config");
		}
		$this->text("\n");

		$confirmationQuestion = new ConfirmationQuestion(
			'Does this look ok? [yes|no] (yes): ',
			true,
			'/^(y|j)/i'
		);
		$this->header("Saving .env file ...");
		if (!$helper->ask($input, $output, $confirmationQuestion)) {
			$this->header("Your config is not applied please run the command again");
			return;
		}
	}

	/**
	 *
	 */
	protected function createConfigFileByDefault()
	{
		$this->header("Generating default .env file");
		foreach ($this->defaults as $key => $value) {
			$config = $value['default'];
			if (strstr($config, " ")) {
				$config = "'$config'";
			}
			$this->configs['env'][$key] = $config;
		}
		$this->configs['services'] = $this->services;
	}

	/**
	 *
	 */
	protected function createDockerFile()
	{
		$directory = __DIR__ . DIRECTORY_SEPARATOR . "/..";
		$envDirectory = $this->getEnvDirectory();
		$helper = $this->getHelper('question');
		$dnsService = array_intersect($this->configs['services'], ['dns']);
		$mysqlServices = array_intersect($this->configs['services'], ['mysql']);

		if ($this->fileSystem->exists($envDirectory . DIRECTORY_SEPARATOR . 'docker-compose.yml' )) {
			$force = $this->input->getOption('force');
			$override = $force?:$helper->ask($this->input, $this->output, new ConfirmationQuestion(
				'The file docker-compose.yml already exists, do you want to override it? [yes/no] (no) ',
				false,
				'/^(y|j)/i'
			));

			if (!$override) { return; }
		}

		if ($dnsService && $this->fileSystem->exists("$envDirectory/docker/dns/") ) {
			$this->fileSystem->remove("$envDirectory/docker/dns/");
		}

		if ($mysqlServices && $this->fileSystem->exists("$envDirectory/docker/mysql/") ) {
			$this->fileSystem->remove("$envDirectory/docker/mysql/");
		}

		$this->fileSystem->mirror("$directory/docker", "$envDirectory/docker");
		$this->fileSystem->mirror("$directory/logs", "$envDirectory/logs");

		$this->header("Generating docker-compose.yml file");
		$this->fileSystem->remove("$directory/docker/docker-compose.yml");
		$this->fileSystem->copy("$directory/services/base.yml", "$envDirectory/docker/docker-compose.yml");

		foreach ($this->configs['services'] as $service) {
			$content = file_get_contents("$directory/services/$service.yml");
			$this->fileSystem->appendToFile(
				"$envDirectory/docker/docker-compose.yml",
				"\n$content"
			);
		}

		$this->configs['services'][] = 'network';


		if ($dnsService) {
			$tld = $this->configs['env']['TLD'];
			$this->fileSystem->appendToFile(
				"$envDirectory/docker/dns/dnsmasq.conf",
				"\n### start {$tld} ###\nlocal=/{$tld}/\nserver=/{$tld}/127.0.0.1\naddress=/{$tld}/127.0.0.1\n### end {$tld} ###"
			);
		}

		if ($mysqlServices) {
			$pwd = $this->configs['env']['DB_ROOT_PASS'];
			$this->fileSystem->appendToFile(
				"$envDirectory/docker/mysql/.my.cnf",
				"\npassword=\"{$pwd}\""
			);
		}

		$networkServices = array_intersect($this->configs['services'], ['network']);

		if ($networkServices) {
			$this->fileSystem->appendToFile("$envDirectory/docker/docker-compose.yml", "\nnetworks:");
			$name =  "{$this->configs['env']['NETWORK_NAME']}";

			$this->fileSystem->appendToFile(
				"$envDirectory/docker/docker-compose.yml",
				"\n {$name}:\n  external:\n   name: \"\${NETWORK_NAME}\""
			);
		}


		$volumeServices = array_intersect($this->configs['services'], ['mysql', 'redis', 'mongo']);

		if ($volumeServices) {
			$this->fileSystem->appendToFile("$envDirectory/docker/docker-compose.yml", "\nvolumes:");
			foreach ($volumeServices as $service) {
				$name =  "{$this->configs['env']['CONTAINER_PREFIX']}-{$service}-volume";

				$this->fileSystem->appendToFile(
					"$envDirectory/docker/docker-compose.yml",
					"\n {$name}:\n  external: true\n"
				);
				$this->runCommand("docker volume create {$name}");
			}
		}

		$this->header("Finished generating file docker-compose.yml!");
	}
}
