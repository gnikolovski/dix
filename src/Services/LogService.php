<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class LogService
{
    protected $logFile;
    protected $logContents;

    public function __construct($log_file)
    {
        $this->logFile = $log_file;
        $this->logContents = file_exists($log_file) ? Yaml::parse(file_get_contents($log_file)) : [];
    }

    public function __destruct()
    {
        file_put_contents($this->logFile, Yaml::dump($this->logContents));
    }

    public function get()
    {
        return $this->logContents;
    }

    public function set($key, $value)
    {
        $this->logContents[$key] = $value;
    }
}
