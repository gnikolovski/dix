<?php

namespace App\Commands;

use App\Services\ConfigService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class DropTablesCommand extends Command
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
            ->setName('drop-tables')
            ->setAliases(['dt'])
            ->setDescription('Drop all tables')
            ->addOption('dbname', NULL, InputOption::VALUE_REQUIRED, 'Database name')
            ->addOption('user', NULL, InputOption::VALUE_REQUIRED, 'Database username')
            ->addOption('pass', NULL, InputOption::VALUE_REQUIRED, 'Database password');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database_name = $input->getOption('dbname');
        $username = $input->getOption('user');
        $password = $input->getOption('pass');

        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        if (!$database_name) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Please specify options'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        $this->dropAllTables($username, $password, $database_name, $output);
    }

    private function dropAllTables($username, $password, $database_name, $output)
    {
        $info_style = new OutputFormatterStyle('black', 'green');
        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('info', $info_style);
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        $credentials = $this->getCredentialsFromConfig($database_name);

        if (!$username) {
            $username = isset($credentials['username']) ? $credentials['username'] : NULL;
        }
        if (!$password) {
            $password = isset($credentials['password']) ? $credentials['password'] : NULL;
        }

        if (!$database_name) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Database name not found'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        if (!$username || !$password) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Database credentials not found'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        $db_table_count = shell_exec("mysql --user=$username --password=$password -N -e \"SELECT COUNT(DISTINCT table_name) FROM information_schema.columns WHERE table_schema = '" . $database_name . "'\" 2>/dev/null");
        $command = "mysqldump --user=$username --password=$password --add-drop-table --no-data $database_name 2>/dev/null | grep ^DROP | mysql --user=$username --password=$password $database_name 2>/dev/null";

        if ($db_table_count <= 0) {
            $formattedBlock = $formatter->formatBlock(array('Error', 'Database not found or it has no tables'), 'error', true);
            $output->writeln($formattedBlock);
        }
        else {
            shell_exec($command);
            $formattedBlock = $formatter->formatBlock(array('Success', 'Database tables dropped'), 'info', true);
            $output->writeln($formattedBlock);
        }
    }

    private function getCredentialsFromConfig($database_name)
    {
        $config_databases = $this->configService->get('databases');
        $username = isset($config_databases[$database_name]['username']) ? $config_databases[$database_name]['username'] :
                    (isset($config_databases['*']['username']) ? $config_databases['*']['username'] : NULL);
        $password = isset($config_databases[$database_name]['password']) ? $config_databases[$database_name]['password'] :
                    (isset($config_databases['*']['password']) ? $config_databases['*']['password'] : NULL);
        $credentials = [
            'username' => $username,
            'password' => $password
        ];
        return $credentials;
    }
}
