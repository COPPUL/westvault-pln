<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Provider;
use Monolog\Logger;
use Symfony\Component\Filesystem\Filesystem;

/**
 * Calculate file paths.
 */
class FilePaths
{
    /**
     * Base directory where the files are stored.
     *
     * @var string
     */
    private $baseDir;

    /**
     * Symfony filesystem object.
     *
     * @var FileSystem
     */
    private $fs;

    /**
     * Kernel environment, a path on the file system.
     *
     * @var string
     */
    private $env;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Build the service.
     */
    public function __construct()
    {
        $this->fs = new Filesystem();
    }

    /**
     * Set the service logger.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the kernel environment.
     *
     * @param string $env
     */
    public function setKernelEnv($env)
    {
        $this->env = $env;
    }

    public function getBaseDir()
    {
        return $this->baseDir;
    }

    /**
     * Set the file system base directory.
     *
     * @param type $dir
     */
    public function setBaseDir($dir)
    {
        if (substr($dir, -1) !== '/') {
            $this->baseDir = $dir.'/';
        } else {
            $this->baseDir = $dir;
        }
    }

    /**
     * Get the root dir, based on the baseDir.
     *
     * @return string
     */
    public function rootPath($mkdir = true)
    {
        $path = $this->baseDir;
        if (!$this->fs->isAbsolutePath($path)) {
            $root = dirname($this->env);
            $path = $root.'/'.$path;
        }
        if (!$this->fs->exists($path) && $mkdir) {
            $this->fs->mkdir($path);
        }

        return realpath($path);
    }

    /**
     * Get an absolute path to a processing directory for the provider.
     *
     * @param string  $dirname
     * @param Provider $provider
     *
     * @return string
     */
    protected function absolutePath($dirname, Provider $provider = null)
    {
        $path = $this->rootPath().'/'.$dirname;
        if (substr($dirname, -1) !== '/') {
            $path .= '/';
        }
        if (!$this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }
        if ($provider !== null) {
            return  $path.$provider->getUuid();
        }

        return realpath($path);
    }

    public function getRestoreDir(Provider $provider)
    {
        $path = $this->absolutePath('restore', $provider);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the harvest directory.
     *
     * @see AppKernel#getRootDir
     *
     * @param Provider $provider
     *
     * @return string
     */
    final public function getHarvestDir(Provider $provider = null)
    {
        $path = $this->absolutePath('received', $provider);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a harvested deposit.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    final public function getHarvestFile(Deposit $deposit)
    {
        $path = $this->getHarvestDir($deposit->getProvider());

        return $path.'/'.$deposit->getFileName();
    }

}
