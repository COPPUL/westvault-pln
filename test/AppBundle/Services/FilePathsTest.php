<?php

namespace AppBundle\Services;

use AppBundle\Utility\AbstractTestCase;

class FilePathsTest extends AbstractTestCase {

	/**
	 * @var FilePaths
	 */
	protected $fp;
	protected static $tmpPath;

	public static function setUpBeforeClass() {
		self::$tmpPath = realpath(sys_get_temp_dir());
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
			'AppBundle\DataFixtures\ORM\test\LoadDeposits',
		);
	}

	public function setUp() {
		parent::setUp();
		$this->fp = $this->getContainer()->get('filepaths');
		$this->fp->setBaseDir(self::$tmpPath);
	}

	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Services\FilePaths', $this->fp);
	}

	public function testSetBaseDir() {
		$this->fp->setBaseDir(self::$tmpPath . '/foo');
		$this->assertEquals(self::$tmpPath . '/foo/', $this->fp->getBaseDir());
	}

	public function testSetBaseDirSlash() {
		$this->fp->setBaseDir(self::$tmpPath . '/foo/');
		$this->assertEquals(self::$tmpPath . '/foo/', $this->fp->getBaseDir());
	}

	public function testRootPath() {
		$path = $this->fp->rootPath(false);
		$this->assertStringStartsWith(self::$tmpPath, $path);
		$this->assertStringEndsWith(self::$tmpPath, $path);
	}

	public function testRootPathAbsolute() {
		$this->fp->setBaseDir(dirname(__FILE__));
		$path = $this->fp->rootPath(false);
		$this->assertStringStartsWith('/', $path);
		$this->assertStringEndsWith('/test/AppBundle/Services', $path);
	}

	public function testGetHarvestDir() {
		$journal = $this->references->getReference('journal');
		$path = $this->fp->getHarvestDir($journal);
		$this->assertEquals(
			self::$tmpPath . '/received/C0A65967-32BD-4EE8-96DE-C469743E563A', 
			$path
		);
	}

	public function testGetHarvestFile() {
		$deposit = $this->references->getReference('deposit');
		$path = $this->fp->getHarvestFile($deposit);
		$this->assertEquals(
			self::$tmpPath . '/received/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip', 
			$path
		);
	}

	public function testGetProcessingDir() {
		$journal = $this->references->getReference('journal');
		$path = $this->fp->getProcessingDir($journal);
		$this->assertEquals(
			self::$tmpPath . '/processing/C0A65967-32BD-4EE8-96DE-C469743E563A', 
			$path
		);
	}

	public function testGetProcessingFile() {
		$deposit = $this->references->getReference('deposit');
		$path = $this->fp->getProcessingBagPath($deposit);
		$this->assertEquals(
			self::$tmpPath . '/processing/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2', 
			$path
		);
	}
	
	public function testGetStagingDir() {
		$journal = $this->references->getReference('journal');
		$path = $this->fp->getStagingDir($journal);
		$this->assertEquals(
			self::$tmpPath . '/staged/C0A65967-32BD-4EE8-96DE-C469743E563A', 
			$path
		);
	}

	public function testGetStagingFile() {
		$deposit = $this->references->getReference('deposit');
		$path = $this->fp->getStagingBagPath($deposit);
		$this->assertEquals(
			self::$tmpPath . '/staged/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2.zip', 
			$path
		);
	}
	
	public function testGetOnixPath() {
		$path = $this->fp->getOnixPath();
		$this->assertEquals(
			self::$tmpPath . '/onix.xml', 
			$path
		);
	}
}
