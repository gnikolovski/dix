<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use App\Services\ConfigService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\Table;

class ConfigCommand extends Command
{
    protected $configService;

    public function __construct(ConfigService $config_service)
    {
        parent::__construct();
        $this->configService = $config_service;
    }

    protected function configure()
    {
        $this
            ->setName('config')
            ->setAliases(['cf'])
            ->setDescription('Get and set application configuration')
            ->addOption('dir', NULL, InputOption::VALUE_REQUIRED, 'Backup directory')
            ->addOption('dbname', NULL, InputOption::VALUE_REQUIRED, 'Database name')
            ->addOption('user', NULL, InputOption::VALUE_OPTIONAL, 'Database username')
            ->addOption('pass', NULL, InputOption::VALUE_OPTIONAL, 'Database password');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $directory = $input->getOption('dir');
        $database_name = $input->getOption('dbname');
        $username = $input->getOption('user');
        $password = $input->getOption('pass');

        if (!$directory && !$database_name && !$username && !$password) {
            $config_directory = $this->configService->get('directory');
            if ($config_directory) {
                $output->writeln('<info>Directory:</info>' . $config_directory);
            }
            else {
                $output->writeln('<info>Directory:</info><error>DIRECTORY NOT CONFIGURED</error>');
            }

            $config_databases = $this->configService->get('databases');
            $config_databases_rows = [];
            if ($config_databases) {
                foreach ($config_databases as $key => $value) {
                    $config_databases_rows[] = [
                        'database' => $key,
                        'username' => isset($value['username']) ? $value['username'] : 'n/a',
                        'password' => isset($value['password']) ? $value['password'] : 'n/a',
                    ];
                }
            }
            $table = new Table($output);
            $table
                ->setHeaders(['Database', 'Username', 'Password'])
                ->setRows($config_databases_rows);
            $table->render();
            if (!$config_databases_rows) {
                $output->writeln('<error>DATABASE CREDENTIALS NOT CONFIGURED</error>');
            }
        }

        $info_style = new OutputFormatterStyle('black', 'green');
        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('info', $info_style);
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        $updated = FALSE;
        if ($directory && file_exists($directory)) {
            $this->configService->set('directory', rtrim($directory, '/'));
            $updated = TRUE;
        }
        if ($database_name && !$username && !$password) {
            $updated = $this->configService->remove('databases', $database_name);
        }
        elseif ($database_name && $username && $password) {
            $this->configService->set('databases', [
                $database_name => [
                    'username' => $username,
                    'password' => $password
                ]
            ]);
            $updated = TRUE;
        }
        if ($updated) {
            $formattedBlock = $formatter->formatBlock(array('Success', 'Configuration has been successfully updated'), 'info', true);
            $output->writeln($formattedBlock);
        }
    }
}
