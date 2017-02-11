<?php

use Pimple\Container;
use Gnikolovski\Services\ConfigService;
use Gnikolovski\Services\LogService;
use Gnikolovski\Commands\ConfigCommand;
use Gnikolovski\Commands\LogCommand;
use Gnikolovski\Commands\ExportCommand;
use Gnikolovski\Commands\ImportCommand;
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

$container['commands'] = function($container) {
    return [
        $container['command.config'],
        $container['command.log'],
        $container['command.export'],
        $container['command.import'],
    ];
};

$container['application'] = function($container) {
    $application = new Application('Database Import eXport - <> with <3 by Goran Nikolovski (2017)');
    $application->addCommands($container['commands']);
    return $application;
};

return $container;
