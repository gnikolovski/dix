<?php

namespace App\Services;

use Symfony\Component\Yaml\Yaml;

class ConfigService
{
    protected $configFile;
    protected $configContents;

    public function __construct($config_file)
    {
        $this->configFile = $config_file;
        $this->configContents = file_exists($config_file) ? Yaml::parse(file_get_contents($config_file)) : [];
    }

    public function __destruct()
    {
        file_put_contents($this->configFile, Yaml::dump($this->configContents));
    }

    public function get($key)
    {
        $key = strtolower($key);
        return isset($this->configContents[$key]) ? $this->configContents[$key] : NULL;
    }

    public function set($key, $value)
    {
        $key = strtolower($key);
        if (!is_array($value)) {
            $this->configContents[$key] = $value;
        }
        else {
            $array_keys = array_keys($value);
            $value_subkey = reset($array_keys);
            $array_values = array_values($value);
            $this->configContents[$key][$value_subkey] = reset($array_values);
        }
    }

    public function remove($key, $value)
    {
        $key = strtolower($key);
        $value = strtolower($value);
        if (isset($this->configContents[$key][$value])) {
            unset($this->configContents[$key][$value]);
            return TRUE;
        }
        return FALSE;
    }
}
