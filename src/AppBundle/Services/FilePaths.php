<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Institution;
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
     * Get an absolute path to a processing directory for the institution.
     *
     * @param string  $dirname
     * @param Institution $institution
     *
     * @return string
     */
    protected function absolutePath($dirname, Institution $institution = null)
    {
        $path = $this->rootPath().'/'.$dirname;
        if (substr($dirname, -1) !== '/') {
            $path .= '/';
        }
        if (!$this->fs->exists($path)) {
            $this->fs->mkdir($path);
        }
        if ($institution !== null) {
            return  $path.$institution->getUuid();
        }

        return realpath($path);
    }

    public function getRestoreDir(Institution $institution)
    {
        $path = $this->absolutePath('restore', $institution);
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
     * @param Institution $institution
     *
     * @return string
     */
    final public function getHarvestDir(Institution $institution = null)
    {
        $path = $this->absolutePath('received', $institution);
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
        $path = $this->getHarvestDir($deposit->getInstitution());

        return $path.'/'.$deposit->getFileName();
    }

    /**
     * Get the processing directory.
     *
     * @param Institution $institution
     *
     * @return string
     */
    final public function getProcessingDir(Institution $institution)
    {
        $path = $this->absolutePath('processing', $institution);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a deposit bag being processed.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    public function getProcessingBagPath(Deposit $deposit)
    {
        $path = $this->getProcessingDir($deposit->getInstitution());

        return $path.'/'.$deposit->getDepositUuid();
    }

    /**
     * Get the staging directory for processed deposits.
     *
     * @param Institution $institution
     *
     * @return string
     */
    final public function getStagingDir(Institution $institution)
    {
        $path = $this->absolutePath('staged', $institution);
        if (!$this->fs->exists($path)) {
            $this->logger->notice("Creating directory {$path}");
            $this->fs->mkdir($path);
        }

        return $path;
    }

    /**
     * Get the path to a processed, staged, bag.
     *
     * @param Deposit $deposit
     *
     * @return type
     */
    final public function getStagingBagPath(Deposit $deposit)
    {
        $path = $this->getStagingDir($deposit->getInstitution());

        return $path.'/'.$deposit->getDepositUuid().'.zip';
    }

    /**
     * Get the path to the onix feed file.
     *
     * @param string $_format
     *
     * @return string
     */
    public function getOnixPath($_format = 'xml')
    {
        return $this->rootPath().'/onix.'.$_format;
    }
}
