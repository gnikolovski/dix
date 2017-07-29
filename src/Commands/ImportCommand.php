<?php

namespace App\Commands;

use App\Services\ConfigService;
use App\Services\LogService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ConfirmationQuestion;

class ImportCommand extends Command
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
            ->setName('import')
            ->setAliases(['im'])
            ->setDescription('Import database')
            ->addOption('dbname', NULL, InputOption::VALUE_REQUIRED, 'Database name')
            ->addOption('id', NULL, InputOption::VALUE_REQUIRED, 'Log ID')
            ->addOption('user', NULL, InputOption::VALUE_REQUIRED, 'Database username')
            ->addOption('pass', NULL, InputOption::VALUE_REQUIRED, 'Database password')
            ->addOption('force', NULL, InputOption::VALUE_REQUIRED, 'Force import even if target exists');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $database_name = $input->getOption('dbname');
        $log_id = $input->getOption('id');
        $username = $input->getOption('user');
        $password = $input->getOption('pass');
        $force = $input->getOption('force');

        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        if (!$database_name && !$log_id) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Please specify options'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        if ($database_name && $log_id) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Options dbname and id are mutually exclusive. Please specify only one option'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        $this->importDatabase($username, $password, $database_name, $log_id, $force, $input, $output);
    }

    private function importDatabase($username, $password, $database_name, $log_id, $force, $input, $output)
    {
        $info_style = new OutputFormatterStyle('black', 'green');
        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('info', $info_style);
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        if ($database_name) {
            $database_path = $this->getLastDatabasePath($database_name);
        }
        else {
            if (strlen($log_id) < 5) {
                $formattedBlock = $formatter->formatBlock(array('Error!', 'ID must contain at least first 5 characters'), 'error', true);
                $output->writeln($formattedBlock);
                return FALSE;
            }
            $database_from_log = $this->getDatabaseFromLogId($log_id, $output);
            $database_name = isset($database_from_log['database_name']) ? $database_from_log['database_name'] : NULL;
            $database_path = isset($database_from_log['database_path']) ? $database_from_log['database_path'] : NULL;
        }

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

        if (!$database_path) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Database path not found'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        if (!$username || !$password) {
            $formattedBlock = $formatter->formatBlock(array('Error!', 'Database credentials not found'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        $db_table_count = shell_exec("mysql --user=$username --password=$password -N -e \"SELECT COUNT(DISTINCT table_name) FROM information_schema.columns WHERE table_schema = '" . $database_name . "'\" 2>/dev/null");
        $command = "mysqldump --user=$username --password=$password --add-drop-table --no-data $database_name 2>/dev/null | grep ^DROP | mysql --user=$username --password=$password $database_name 2>/dev/null && mysql --user=$username --password=$password $database_name < $database_path 2>/dev/null";

        if ($db_table_count > 0) {
            if (!$force) {
                $formattedBlock = $formatter->formatBlock(array('Error!', 'Database already exists. If you want to overwrite it use --force=true option'), 'error', true);
                $output->writeln($formattedBlock);
            }
            else {
                $helper = $this->getHelper('question');
                $question = new ConfirmationQuestion('<comment>Are you sure want to overwrite existing database (Y/N)?</comment>', FALSE);
                if (!$helper->ask($input, $output, $question)) {
                    return;
                }
                $result = shell_exec($command);
                $formattedBlock = $formatter->formatBlock(array('Success', 'Database imported'), 'info', true);
                $output->writeln($formattedBlock);
            }
        }
        else {
            $result = shell_exec($command);
            $formattedBlock = $formatter->formatBlock(array('Success', 'Database imported'), 'info', true);
            $output->writeln($formattedBlock);
        }
    }

    private function getDatabaseFromLogId($log_id, $output)
    {
        $export_log = $this->logService->get();
        $contains_count = 0;
        $database_name = NULL;
        $database_path = NULL;
        foreach ($export_log as $key => $value) {
            if (strpos($key, $log_id) === 0) {
                $contains_count++;
                $database_name = $value['database'];
                $database_path = $value['path'];
            }
        }
        if ($contains_count == 1) {
            return [
                'database_name' => $database_name,
                'database_path' => $database_path,
            ];
        }
        elseif ($contains_count > 1) {
            $error_style = new OutputFormatterStyle('white', 'red');
            $output->getFormatter()->setStyle('error', $error_style);
            $formatter = $this->getHelper('formatter');
            $formattedBlock = $formatter->formatBlock(array('Error!', 'ID is not unique. Use more characters'), 'error', true);
            $output->writeln($formattedBlock);
            die();
        }
        return [
            'database_name' => NULL,
            'database_path' => NULL,
        ];
    }

    private function getLastDatabasePath($database_name)
    {
        $log = $this->logService->get();
        $log = array_reverse($log);
        foreach ($log as $key => $value) {
            if ($value['database'] == $database_name) {
                return $value['path'];
            }
        }
        return NULL;
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
