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

class ConfigCommand extends Command
{
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

	private $configs = [
		'env' => [],
		'services' => []
	];


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
			->setDescription('Configures "Space Station"!');
	}

	public function execute(InputInterface $input, OutputInterface $output)
	{
		$helper = $this->getHelper('question');
		$serviceQuestion = new ChoiceQuestion(
			'Please select the services you would like to install (proxy, dns, mysql, websockets, redis, mongo)',
			$this->services,
			'0,1,2,3,4,5'
		);
		$serviceQuestion->setMultiselect(true);

		$this->configs['services'] = $helper->ask($input, $output, $serviceQuestion);

		foreach ($this->defaults as $key => $value) {
			$this->configs['env'][$key] = $helper->ask($input, $output, new Question($value['text'], $value['default']));
		}

		$output->writeln("<fg=blue>This are your selected services:</> \n");
		$output->writeln("<fg=green>" . implode(', ', $this->configs['services']) . "</> \n");

		$output->writeln("<fg=blue>This is the config:</>");
		foreach ($this->configs['env'] as $KEY => $config) {
			if (strstr($config, " ")) {
				$config = "'$config'";
			}
			$output->writeln("<fg=green>$KEY=$config</>");
		}
		$output->writeln("\n");

		$confirmationQuestion = new ConfirmationQuestion(
			'Does this look ok? [y/yes] (yes)',
			'yes',
			'/^(y|j)/i'
		);

		if (!$helper->ask($input, $output, $confirmationQuestion)) {
			$output->writeln("<fg=blue>Your config is not applied please run the command again</>");
			return;
		}

		$this->creteEnvFile($output);
	}

	protected function creteEnvFile ($output) {
		$output->writeln("<fg=blue>Saving .env file ...</>");
		$fileSystem = new Filesystem();
		$fileSystem->touch('.env');
		foreach ($this->configs['env'] as $KEY => $value) {
			$fileSystem->appendToFile('.env', "$KEY=$value\n");
		}
		$output->writeln("<fg=green>Saved .env file</>");
	}


}