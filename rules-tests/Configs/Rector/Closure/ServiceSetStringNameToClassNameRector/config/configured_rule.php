<?php

declare(strict_types=1);

use Rector\Config\RectorConfig;
use Rector\Symfony\Tests\ConfigList;
use Rector\Symfony\Configs\Rector\Closure\ServiceSetStringNameToClassNameRector;

return static function (RectorConfig $rectorConfig): void {
    $rectorConfig->import(ConfigList::MAIN);

    $rectorConfig->rule(ServiceSetStringNameToClassNameRector::class);
    $rectorConfig->symfonyContainerXml(__DIR__ . '/../xml/services.xml');
};