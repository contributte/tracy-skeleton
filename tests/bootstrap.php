<?php declare(strict_types = 1);

use Contributte\Tester\Environment;
use Contributte\Utils\FileSystem;

if (@!include __DIR__ . '/../vendor/autoload.php') {
	echo 'Install Nette Tester using `composer update --dev`';
	exit(1);
}

Environment::setup(__DIR__);

FileSystem::createDir(__DIR__ . '/../var/tmp');
FileSystem::createDir(__DIR__ . '/../var/log');

if (!file_exists(__DIR__ . '/../config/local.neon')) {
	FileSystem::copy(__DIR__ . '/../config/local.neon.example', __DIR__ . '/../config/local.neon');
}
