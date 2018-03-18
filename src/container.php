<?php

use App\Commands\ConfigCommand;
use App\Commands\DropTablesCommand;
use App\Commands\ExportCommand;
use App\Commands\ImportCommand;
use App\Commands\LogCommand;
use App\Services\ConfigService;
use App\Services\LogService;
use Pimple\Container;
use Symfony\Component\Console\Application;

$container = new Container();

if (!file_exists($_SERVER['HOME'] . '/dix')) {
    mkdir($_SERVER['HOME'] . '/dix');
}

$container['parameters'] = [
    'config.file' => $_SERVER['HOME'] . '/dix/config.yml',
    'log.file' => $_SERVER['HOME'] . '/dix/log.yml'
];

$container['config.service'] = function($container) {
    return new ConfigService($container['parameters']['config.file']);
};

$container['log.service'] = function($container) {
    return new LogService($container['parameters']['log.file']);
};

$container['command.config'] = function($container) {
    return new ConfigCommand($container['config.service']);
};

$container['command.log'] = function($container) {
    return new LogCommand($container['log.service']);
};

$container['command.export'] = function($container) {
    return new ExportCommand($container['config.service'], $container['log.service']);
};

$container['command.import'] = function($container) {
    return new ImportCommand($container['config.service'], $container['log.service']);
};

$container['command.drop-tables'] = function($container) {
    return new DropTablesCommand($container['config.service']);
};

$container['commands'] = function($container) {
    return [
        $container['command.config'],
        $container['command.log'],
        $container['command.export'],
        $container['command.import'],
        $container['command.drop-tables'],
    ];
};

$container['application'] = function($container) {
    $version = '0.4.0';
    $app_info = 'Database Import eXport ' . $version;
    $credits = ' <> with <3 by Goran Nikolovski';
    $website = 'Website: http://gorannikolovski.com';
    $application = new Application($app_info . $credits . PHP_EOL . $website);
    $application->addCommands($container['commands']);
    return $application;
};

return $container;
