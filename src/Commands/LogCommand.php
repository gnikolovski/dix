<?php

namespace App\Commands;

use Symfony\Component\Console\Command\Command;
use App\Services\LogService;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

class LogCommand extends Command
{
    protected $logService;

    public function __construct(LogService $log_service)
    {
        parent::__construct();
        $this->logService = $log_service;
    }

    protected function configure()
    {
        $this
            ->setName('log')
            ->setAliases(['lg'])
            ->setDescription('Show export logs')
            ->addOption('sort', NULL, InputOption::VALUE_REQUIRED, 'Sort results')
            ->addOption('lim', NULL, InputOption::VALUE_REQUIRED, 'Limit number of results')
            ->addOption('dbname', NULL, InputOption::VALUE_REQUIRED, 'Filter results by database name')
            ->addOption('dest', NULL, InputOption::VALUE_REQUIRED, 'Filter results by destination')
            ->addOption('date', NULL, InputOption::VALUE_REQUIRED, 'Filter results by date')
            ->addOption('msg', NULL, InputOption::VALUE_REQUIRED, 'Filter results by message');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $sort = $input->getOption('sort');
        $limit = $input->getOption('lim');
        $database_name = $input->getOption('dbname');
        $destination = $input->getOption('dest');
        $date = $input->getOption('date');
        $message = $input->getOption('msg');

        $error_style = new OutputFormatterStyle('white', 'red');
        $output->getFormatter()->setStyle('error', $error_style);
        $formatter = $this->getHelper('formatter');

        $log_contents = $this->logService->get();

        if (!$log_contents) {
            $formattedBlock = $formatter->formatBlock(array('Notice', 'Log is empty'), 'error', true);
            $output->writeln($formattedBlock);
            return FALSE;
        }

        if ($sort != 'asc') {
            $log_contents = array_reverse($log_contents);
        }

        $item_count = 0;
        foreach ($log_contents as $key => $value) {
            $database_value = isset($value['database']) ? $value['database'] : NULL;
            $destination_value = isset($value['destination']) ? $value['destination'] : NULL;
            $path_value = isset($value['path']) ? $value['path'] : NULL;
            $date_value = isset($value['date']) ? $value['date'] : NULL;
            $message_value = isset($value['message']) ? $value['message'] : NULL;

            if (isset($limit) && $item_count >= $limit) {
                continue;
            }
            $database_filter = $this->filterByText($database_name, $database_value);
            if ($database_filter) {
                continue;
            }
            $dest_filter = $this->filterByText($destination, $destination_value);
            if ($dest_filter) {
                continue;
            }
            $date_filter = $this->filterByDate($date, $date_value);
            if ($date_filter) {
                continue;
            }
            $msg_filter = $this->filterByText($message, $message_value);
            if ($msg_filter) {
                continue;
            }
            $item_count++;

            $output->writeln('<comment>' . $key . '</comment>');
            $output->writeln('Database:    ' . $database_value);
            $output->writeln('Path:        ' . $path_value);
            $output->writeln('Destination: ' . $this->buildDestination($destination_value));
            $output->writeln('Date:        ' . date('Y.m.d H:i:s', $date_value));
            if ($message_value) {
                $output->writeln('Message:     ' . $message_value);
            }
            else {
                $output->writeln('Message:  -' . $message_value);
            }
            $output->writeln('');
        }
    }

    private function filterByText($needle, $haystack)
    {
        if ($needle && strpos($haystack, $needle) === FALSE) {
            return TRUE;
        }
        return FALSE;
    }

    private function filterByDate($date, $date_log)
    {
        if ($date) {
            $date_log_obj = new \Datetime();
            $date_log_obj->setTimestamp($date_log);
            $date = str_replace(['.', '-'], '', $date);
            $search_date = new \Datetime($date);
            if ($date_log_obj->format('Y-m-d') != $search_date->format('Y-m-d')) {
                return TRUE;
            }
        }
        return FALSE;
    }

    private function buildDestination($destination_value)
    {
        if ($destination_value) {
            return $destination_value;
        }
        return 'local';
    }
}
