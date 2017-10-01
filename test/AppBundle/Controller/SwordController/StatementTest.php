<?php

namespace AppBundle\Controller\SwordController;

class StatementTest extends AbstractSwordTestCase {

	// journal not whitelisted
	public function testStatementNotWhitelisted() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/96B68B76-DC69-4E62-A9A3-AE76B702EB2B/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}
	
	// requested journal uuid does not match deposit uuid.
	public function testStatementMismatch() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/A556CBF2-B674-444F-87B7-23DEE36F013D/578205CB-0947-4CD3-A384-CDF186F5E86B/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}
	
	// journal uuid unknown.
	public function testStatementJournalNonFound() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/96B68B76-DC69-4E62-A9A3-AE76B702EB2B/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Not authorized to request statements.', $this->client->getResponse()->getContent());
	}
	
	// deposit uuid unknown.
	public function testStatementDepositNonFound() {
		$this->client->request('GET', '/api/sword/2.0/cont-iri/c0a65967-32bd-4ee8-96de-c469743e563a/BFDC45E7-58A8-4C46-B194-E20E040F0BD7/state');
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
		$this->assertContains('Deposit UUID not found.', $this->client->getResponse()->getContent());		
	}
	
	public function testStatement(){
		$this->client->request('GET', '/api/sword/2.0/cont-iri/c0a65967-32bd-4ee8-96de-c469743e563a/578205CB-0947-4CD3-A384-CDF186F5E86B/state');
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
		$xml = $this->getXml($this->client);
		$this->assertEquals('http://journal.example2.com/path/to/deposit', $this->getXmlValue($xml, '//atom:content/text()'));
	}
}
