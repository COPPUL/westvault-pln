<?php

$basedir = dirname(dirname(__DIR__));

require_once $basedir . '/vendor/autoload.php';

use Sami\Sami;
use Sami\RemoteRepository\GitHubRemoteRepository;
use Symfony\Component\Finder\Finder;

$iterator = Finder::create()
    ->files()
    ->name('*.php')
    ->exclude('Resources')
    ->exclude('Tests')
    ->in($basedir . '/src')
;

return new Sami($iterator, array(
    'title'                => 'PKP PLN',
    'build_dir'            => $basedir . '/docs/api',
    'cache_dir'            => $basedir . '/app/cache/sami',
    'remote_repository'    => new GitHubRemoteRepository('ubermichael/pkppln-php', $basedir),
    'default_opened_level' => 2,
));
