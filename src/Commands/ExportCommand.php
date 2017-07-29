<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use App\Services\ConfigService;
use App\Services\LogService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class ExportCommand extends Command
{
    protected $configService;
    protected $logService;

    public function __construct(ConfigService $config_service, LogService $log_service)
    {
        parent::__construct();
        $this->configService = $config_service;
        $this->logService = $log_service;
    }

    protected function configure()
    {
        $this
            ->setName('export')
            ->setAliases(['ex'])
            ->setDescription('Export database')
            ->addOption('dbname', NULL, InputOption::VALUE_REQUIRED, 'Database name')
            ->addOption('user', NULL, InputOption::VALUE_REQUIRED, 'Database username')
            ->addOption('pass', NULL, InputOption::VALUE_REQUIRED, 'Database password')
            ->addOption('msg', NULL, InputOption::VALUE_REQUIRED, 'Log message');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database_name = $input->getOption('dbname');
        $username = $input->getOption('user');
        $password = $input->getOption('pass');
        $message = $input->getOption('msg');

        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        $export_directory = $this->configService->get('directory');
        if (!$export_directory) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Directory path not defined. Use config command to set export location'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        if (!$database_name) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Please specify database name'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        $config_databases = $this->configService->get('databases');
        if (!isset($username)) {
            $username = isset($config_databases[$database_name]['username']) ? $config_databases[$database_name]['username'] :
            (isset($config_databases['*']['username']) ? $config_databases['*']['username'] : NULL);
        }
        if (!isset($password)) {
            $password = isset($config_databases[$database_name]['password']) ? $config_databases[$database_name]['password'] :
            (isset($config_databases['*']['password']) ? $config_databases['*']['password'] : NULL);
        }

        if ($username && $password) {
            $this->exportDatabase($database_name, $username, $password, $message, $export_directory, $output);
        }
        else {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Database credentials not found'), 'error', true);
            $output->writeln($formattedBlock);
        }
    }

    private function exportDatabase($database_name, $username, $password, $message, $export_directory, $output)
    {
        $info_style = new OutputFormatterStyle('black', 'green');
        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('info', $info_style);
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        $hash = hash('sha1', uniqid());
        $export_directory_path = $export_directory . '/' . $database_name;
        if (!file_exists($export_directory_path)) {
            mkdir($export_directory_path);
        }
        $database_filename = $export_directory_path . '/' . $database_name . '-' . $hash . '.sql';
        if (file_exists($database_filename)) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Sql file already exists. Export aborted'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }
        $command = "mysqldump --user=$username --password=$password $database_name 2>&1 > $database_filename";
        $result = shell_exec($command);

        if (strpos($result, 'error') !== FALSE) {
            $formattedBlock = $formatter->formatBlock(array('Error!', trim($result)), 'error', true);
            $output->writeln($formattedBlock);
            if (file_exists($database_filename)) {
                unlink($database_filename);
            }
        }
        else {
            $formattedBlock = $formatter->formatBlock(array('Success', 'Database exported'), 'info', true);
            $output->writeln($formattedBlock);
            $this->logService->set($hash, [
                'database' => $database_name,
                'path' => $database_filename,
                'date' => time(),
                'message' => $message,
            ]);
        }
    }
}
