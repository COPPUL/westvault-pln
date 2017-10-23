<?php

namespace AppBundle\Utility;

use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use PHPUnit_Framework_TestCase;

class FailedPingResultTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var PingResult
	 */
	protected $result;

	public function setUp() {
		$response = new Response(404);
		$response->setBody(Stream::factory($this->getBody()));
		$this->result = new PingResult($response);
	}

	public function testHasXml() {
		$this->assertFalse($this->result->hasXml());
	}

	public function testHasError() {
		$this->assertTrue($this->result->hasError());
	}

	public function testGetHttpStatus() {
		$this->assertEquals(404, $this->result->getHttpStatus());
	}

	public function testGetError() {
		$this->assertEquals('Unable to parse response body into XML: String could not be parsed as XML', $this->result->getError());
	}

	public function testGetOjsRelease() {
		$this->assertNull($this->result->getOjsRelease());
	}

	public function testGetPluginReleaseVersion() {
		$this->assertNull($this->result->getPluginReleaseVersion());
	}

	public function tetGetPluginReleaseDate() {
		$this->assertNull($this->result->getPluginReleaseDate());
	}

	public function testIsPluginCurrent() {
		$this->assertEquals('No', $this->result->isPluginCurrent());
	}

	public function testAreTermsAccepted() {
		$this->assertNull($this->result->areTermsAccepted());
	}

	public function testGetJournalTitle() {
		$this->assertNull($this->result->getJournalTitle());
	}

	public function testGetArticleCount() {
		$this->assertEquals(0, $this->result->getArticleCount());
	}

	public function testGetArticleTitles() {
		$this->assertEquals(array(), $this->result->getArticleTitles());
	}
	
	public function testGetBody() {
		$this->assertEquals('Not found. Please try again.', $this->result->getBody());
	}

	private function getBody() {
		// intentionally NOT XML.
		$str = <<<ENDSTR
<html><body>Not found.<br> Please try again.</body></html>
ENDSTR;
		return $str;
	}
}
