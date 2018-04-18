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
			'text' => 'Please enter the HTTP port: (80) ',
			'default' => 80
		],
		'HTTPS_PORT' =>  [
			'text' => 'Please enter the HTTPS port: (443) ',
			'default' => 443
		],
		'NETWORK_NAME' =>  [
			'text' => 'Please enter the network name: (dev-env-network) ',
			'default' => 'dev-env-network'
		],
		'REDIS_PORT' =>  [
			'text' => 'Please enter the redis port: (6379) ',
			'default' => 6379
		],
		'MONGODB_PORT' =>  [
			'text' => 'Please enter the mongo db port: (27017) ',
			'default' => 27017
		],
		'DB_PORT' =>  [
			'text' => 'Please enter the mysql port: (3306) ',
			'default' => 3306
		],
		'DB_ROOT_PASS' =>  [
			'text' => 'Please enter the mysql root password: (secret) ',
			'default' => 'secret'
		],
		'CONTAINER_PREFIX' =>  [
			'text' => 'Please enter the prefix for your containers: (dev-env) ',
			'default' => 'dev-env'
		],
		'CERT_COUNTRY' =>  [
			'text' => 'Please enter the country for certificate: (US) ',
			'default' => 'US'
		],
		'CERT_CITY' =>  [
			'text' => 'Please enter the city for certificate: (Springfield) ',
			'default' => 'Springfield'
		],
		'CERT_STATE' =>  [
			'text' => 'Please enter the state for certificate: (Foo) ',
			'default' => 'Foo'
		],
		'CERT_COMPANY' =>  [
			'text' => 'Please enter the company for certificate: (Mosaiqo) ',
			'default' => 'Mosaiqo'
		],
		'CERT_DEPARTMENT' =>  [
			'text' => 'Please enter the department for certificate: (Dev Team) ',
			'default' => 'Dev Team'
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
	private $services = ['proxy', 'dns', 'mysql', 'websockets', 'redis', 'mongo'];
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
			if ($input->getOption('force')) {
				$override = true;
			} else {
				$override = $helper->ask($input, $output, new ConfirmationQuestion(
					'There is already a config file, do you want to override it? [yes/no] (no) ',
					false,
					'/^(y|j)/i'
				));
			}

			if ($override) {
				$this->removeEnvFile();;
			} else {
				return 0;
			}

		}

		if ($input->getOption('default')) {
			$this->createConfigFileByDefault();
		} else {
			$this->createConfigFileOnUserInput($input, $output);
		}

		$this->createEnvFile();
		$this->createDockerFile();
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
			'0,1,2,3,4,5'
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
			'Does this look ok? [yes|no] (yes)',
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
		$directory = getcwd();
		$envDirectory = $this->getEnvDirectory();
		$helper = $this->getHelper('question');
		if ($this->input->getOption('force')) {
			$override = true;
		} else {
			$override = $helper->ask($this->input, $this->output, new ConfirmationQuestion(
				'The file docker-compose.yml already exists, do you want to override it? [yes/no] (no) ',
				false,
				'/^(y|j)/i'
			));
		}
		if (!$override) { return; }

		$this->fileSystem->mirror("$directory/docker", "$envDirectory/docker");
		$this->fileSystem->mirror("$directory/logs", "$envDirectory/logs");

		$this->header("Generating docker-compose.yml file");
		$this->fileSystem->remove("$directory/docker/docker-compose.yml");
		$this->fileSystem->copy("$directory/services/base.yml", "$envDirectory/docker/docker-compose.yml");

		$this->configs['services'][] = 'network';

		foreach ($this->configs['services'] as $service) {
			$content = file_get_contents("$directory/services/$service.yml");
			$this->fileSystem->appendToFile(
				"$envDirectory/docker/docker-compose.yml",
				"\n$content"
			);
		}
		$volumeServices = array_intersect($this->configs['services'], ['mysql', 'redis', 'mongo']);
		if ($volumeServices) {
			$this->fileSystem->appendToFile("$envDirectory/docker/docker-compose.yml", "\nvolumes:");
			foreach ($volumeServices as $service) {
				$this->fileSystem->appendToFile(
					"$envDirectory/docker/docker-compose.yml",
					"\n {$service}data:\n  driver: \"local\""
				);
			}
		}

		$this->header("Finished generating file docker-compose.yml!");
	}
}