<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use Exception;
use SimpleXMLElement;

class DepositBuilderXmlTest extends AbstractTestCase {

	/**
	 * @var DepositBuilder
	 */
	protected $builder;
	
	/**
	 * @var Deposit
	 */
	protected $deposit;

	public function setUp() {
		parent::setUp();
		$this->builder = $this->getContainer()->get('depositbuilder');
		$this->deposit = $this->builder->fromXml(
            $this->references->getReference('journal'),
            $this->getDepositXml()
		);
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
		);
	}

	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Entity\Deposit', $this->deposit);
	}
	
	public function testAction() {
		$this->assertEquals('add', $this->deposit->getAction());
	}
	
	public function testChecksumType() {
		$this->assertEquals('SHA-1', $this->deposit->getChecksumType());
	}
	
	public function testChecksumValue() {
		$this->assertEquals('D46C034EF54C36237B89D456968965432830A603', $this->deposit->getChecksumValue());
	}
	
	public function testDepositUuid() {
		$this->assertEquals('28FF5B33-D3C4-440C-B87A-16D402D10D93', $this->deposit->getDepositUuid());
	}
	
	public function testFileType() {
		$this->assertEquals('', $this->deposit->getFileType());
	}
	
	public function testIssue() {
		$this->assertEquals(4, $this->deposit->getIssue());
	}
	
	public function testVolume() {
		$this->assertEquals(2, $this->deposit->getVolume());
	}
	
	public function testPubDate() {
		$this->assertEquals(
            '2016-04-22T00:00:00-07:00', 
            $this->deposit->getPubDate()->format('c')
		);
	}
	
	public function testJournal() {
		$journal = $this->deposit->getJournal();
		$this->assertInstanceOf('AppBundle\Entity\Journal', $journal);
		$this->assertEquals('C0A65967-32BD-4EE8-96DE-C469743E563A', $journal->getUuid());
	}
	
	public function testSize() {
		$this->assertEquals(123, $this->deposit->getSize());
	}
	
	public function testUrl() {
		$this->assertEquals('http://example.com/deposit/foo.zip', $this->deposit->getUrl());
	}
	
	public function testDepositReceipt() {
		$this->assertEquals('http://pkppln.test/web/app_dev.php/api/sword/2.0/cont-iri/C0A65967-32BD-4EE8-96DE-C469743E563A/28FF5B33-D3C4-440C-B87A-16D402D10D93/state', $this->deposit->getDepositReceipt());
	}
	
	public function testLicensingInfo() {
		$license = $this->deposit->getLicense();
		$this->assertEquals(7, count($license));
		$this->assertEquals('Open', $license['publishingMode']);
		$this->assertEquals('OA GOOD', $license['openAccessPolicy']);
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
