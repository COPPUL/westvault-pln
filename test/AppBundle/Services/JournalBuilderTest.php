<?php

namespace AppBundle\Services;

use AppBundle\Entity\Deposit;
use AppBundle\Utility\AbstractTestCase;
use Exception;
use SimpleXMLElement;

class JournalBuilderTest extends AbstractTestCase {

	/**
	 * @var JournalBuilder
	 */
	protected $builder;

	public function setUp() {
		parent::setUp();
		$this->builder = $this->getContainer()->get('journalbuilder');
	}

	public function testGet() {
		$this->assertInstanceOf('AppBundle\Services\JournalBuilder', $this->builder);
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
}

