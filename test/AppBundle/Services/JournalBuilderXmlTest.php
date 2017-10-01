<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use Exception;
use SimpleXMLElement;

class JournalBuilderXmlTest extends AbstractTestCase {

	/**
	 * @var JournalBuilder
	 */
	protected $builder;

	/**
	 * @var Journal
	 */
	protected $journal;

	public function setUp() {
		parent::setUp();
		$this->builder = $this->getContainer()->get('journalbuilder');
		$this->journal = $this->builder->fromXml(
            $this->getDepositXml(),
            '1B4D7E02-06B9-4791-8762-B6DF064DE1DA'
		);
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
		);
	}

	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Entity\Journal', $this->journal);
	}

	public function testTitle() {
		$this->assertEquals('Test Data Journal of Testing', $this->journal->getTitle());
	}

	public function testUrl() {
		$this->assertEquals('http://tdjt.example.com', $this->journal->getUrl());
	}

	public function testEmail() {
		$this->assertEquals('foo@example.com', $this->journal->getEmail());
	}

	public function testIssn() {
		$this->assertEquals('1234-1234', $this->journal->getIssn());
	}

	public function testPublisherName() {
		$this->assertEquals('Publisher of Stuff', $this->journal->getPublisherName());
	}

	public function testPublisherUrl() {
		$this->assertEquals('http://publisher.example.com', $this->journal->getPublisherUrl());
	}

	private function getDepositXml() {
		$str = <<<'ENDXML'
<entry 
    xmlns="http://www.w3.org/2005/Atom" 
    xmlns:dcterms="http://purl.org/dc/terms/"
    xmlns:pkp="http://pkp.sfu.ca/SWORD">
    <email>foo@example.com</email>
    <title>Test Data Journal of Testing</title>
    <pkp:journal_url>http://tdjt.example.com</pkp:journal_url>
    <pkp:publisherName>Publisher of Stuff</pkp:publisherName>
    <pkp:publisherUrl>http://publisher.example.com</pkp:publisherUrl>
    <pkp:issn>1234-1234</pkp:issn>
    <id>urn:uuid:28FF5B33-D3C4-440C-B87A-16D402D10D93</id>
    <updated>2016-04-22T12:35:48Z</updated>
    <pkp:content size="123" volume="2" issue="4" pubdate="2016-04-22" 
		checksumType="SHA-1"
        checksumValue="d46c034ef54c36237b89d456968965432830a603">http://example.com/deposit/foo.zip</pkp:content>
    <pkp:license>
        <pkp:publishingMode>Open</pkp:publishingMode>
        <pkp:openAccessPolicy>OA GOOD</pkp:openAccessPolicy>
        <pkp:licenseUrl>http://example.com/license</pkp:licenseUrl>
        <pkp:copyrightNotice>Copyright ME</pkp:copyrightNotice>
        <pkp:copyrightBasis>ME</pkp:copyrightBasis>
        <pkp:copyrightHolder>MYSELF</pkp:copyrightHolder>
    </pkp:license>
</entry>
ENDXML;

		$xml = new SimpleXmlElement($str);
		$ns = new Namespaces();
		$ns->registerNamespaces($xml);
		return $xml;
	}
}
