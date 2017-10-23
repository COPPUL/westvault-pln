<?php

namespace AppBundle\Services;

use AppBundle\Utility\AbstractTestCase;
use DOMDocument;

class DtdValidatorTest extends AbstractTestCase {
	
	/**
	 * @var DtdValidator
	 */
	protected $validator;
	
	public function setUp() {
		parent::setUp();
		$this->validator = $this->getContainer()->get('dtdvalidator');
		$this->validator->clearErrors();
	}
	
	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Services\DtdValidator', $this->validator);
	}
	
	public function testValidateNoDtd() {
		$dom = new DOMDocument();
		$dom->loadXML('<root />');
		$this->validator->validate($dom);
		$this->assertEquals(0, $this->validator->countErrors());
	}
	
	public function testValidate() {
		$dom = new DOMDocument();
		$dom->loadXML($this->getValidXml());
		$this->validator->validate($dom, true);
		$this->assertEquals(0, $this->validator->countErrors());
	}
	
	public function testValidateWithErrors() {
		$dom = new DOMDocument();
		$dom->loadXML($this->getinvalidXml());
		$this->validator->validate($dom, true);
		$this->assertEquals(1, $this->validator->countErrors());
	}
	
	private function getValidXml() {
		$str = <<<ENDSTR
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE root [
<!ELEMENT root (item)+ >
<!ELEMENT item EMPTY >
<!ATTLIST item type CDATA #REQUIRED>
]>
<root>
	<item type="foo"/>
	<item type="bar"/>
</root>
ENDSTR;
		return $str;
	}

	private function getInvalidXml() {
		$str = <<<ENDSTR
<?xml version="1.0" standalone="yes"?>
<!DOCTYPE root [
<!ELEMENT root (item)+ >
<!ELEMENT item EMPTY >
<!ATTLIST item type CDATA #REQUIRED>
]>
<root>
	<item/>
	<item type="bar"/>
</root>
ENDSTR;
		return $str;
	}
}