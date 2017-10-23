<?php

namespace AppBundle\Utility;

use PHPUnit_Framework_TestCase;

class NamespaceTest extends PHPUnit_Framework_TestCase {

	protected $ns;

	public function setUp() {
		$this->ns = new Namespaces();
	}

	public function testGetNamespace() {
		$this->assertEquals(Namespaces::DCTERMS, $this->ns->getNamespace('dcterms'));
	}

	public function testGetNamespaceUndef() {
		$this->assertEquals(null, $this->ns->getNamespace('foo'));
	}

	public function testGetNamespaceEmptyString() {
		$this->assertEquals(null, $this->ns->getNamespace(''));
	}

	public function testGetNamespaceNull() {
		$this->assertEquals(null, $this->ns->getNamespace(null));
	}

	public function testRegisterNamespaces() {
		$xml = new \SimpleXMLElement('<sword:root xmlns:sword="http://pkp.sfu.ca/SWORD"/>');
		$this->ns->registerNamespaces($xml);
		$nodes = $xml->xpath('/sword:root');
		$this->assertEquals(1, count($nodes));
	}
}
