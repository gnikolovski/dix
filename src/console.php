#!/usr/bin/env php
<?php

set_time_limit(0);

require __DIR__ . '/../vendor/autoload.php';

$container = require __DIR__ . '/container.php';

$application = $container['application'];

$application->run();
