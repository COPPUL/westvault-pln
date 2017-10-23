<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractTestCase;
use Exception;
use SimpleXMLElement;

class DepositBuilderTest extends AbstractTestCase {

	/**
	 * @var DepositBuilder
	 */
	protected $builder;

	public function setUp() {
		parent::setUp();
		$this->builder = $this->getContainer()->get('depositbuilder');
	}

	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals',
			'AppBundle\DataFixtures\ORM\test\LoadDeposits',
		);
	}

	public function testGet() {
		$this->assertInstanceOf('AppBundle\Services\DepositBuilder', $this->builder);
	}

	public function testGetXmlValue() {
		$xml = new SimpleXMLElement('<root><child a="b">foo</child><child a="c">bar</child></root>');
		$r = $this->builder->getXmlValue($xml, '//child[@a="b"]');
		$this->assertEquals('foo', $r);
	}

	public function testGetXmlValueNull() {
		$xml = new SimpleXMLElement('<root><child a="b">foo</child><child a="c">bar</child></root>');
		$r = $this->builder->getXmlValue($xml, '//child[@a="d"]');
		$this->assertEquals(null, $r);
	}

	public function testGetXmlValueException() {
		$xml = new SimpleXMLElement('<root><child a="b">foo</child><child a="c">bar</child></root>');
		try {
			$r = $this->builder->getXmlValue($xml, '//child');
		} catch (Exception $e) {
			$this->assertStringStartsWith('Too many elements for', $e->getMessage());
			return;
		}
		$this->fail('No exception thrown.');
	}

	public function testBuildDepositReceiptUrl() {
		$deposit = $this->references->getReference('deposit');
		$this->assertEquals('http://pkppln.test/web/app_dev.php/api/sword/2.0/cont-iri/C0A65967-32BD-4EE8-96DE-C469743E563A/D38E7ECB-7D7E-408D-94B0-B00D434FDBD2/state', $this->builder->buildDepositReceiptUrl($deposit));
	}

	public function testGetLicensingInfo() {
		$content = '<root xmlns:pkp="http://pkp.sfu.ca/SWORD">'
				. '<pkp:license>'
				. '<item1>cheers</item1><item2>cheerio</item2>'
				. '</pkp:license></root>';
		$xml = new SimpleXMLElement($content);
		$deposit = new Deposit();
		$this->builder->getLicensingInfo($deposit, $xml);
		$license = $deposit->getLicense();
		$expected = Array (
			'item1' => 'cheers',
			'item2' => 'cheerio',
		);
		$this->assertEquals($expected, $license);
	}


	public function testGetLicensingInfoMissing() {
		$content = '<root xmlns:pkp="http://pkp.sfu.ca/SWORD"></root>';
		$xml = new SimpleXMLElement($content);
		$deposit = new Deposit();
		$this->builder->getLicensingInfo($deposit, $xml);
		$license = $deposit->getLicense();
		$expected = Array ();
		$this->assertEquals($expected, $license);
	}
}
