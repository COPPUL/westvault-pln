<?php

require 'vendor/autoload.php';

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('Resources')
    ->exclude('Tests')
    ->in(__DIR__ . '/src');

return new Sami($iterator, array(
    'title' => 'CEWW API',
    'build_dir' => __DIR__ . '/docs/api',
    'cache_dir' => __DIR__ . '/var/cache/sami',
));