<?php

namespace AppBundle\Controller\SwordController;

class ServiceDocumentTest extends AbstractSwordTestCase {

	public function testServiceDocument() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
			'HTTP_Journal-Url' => 'http://example.com'
		));
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
	}
	
	public function testServiceDocumentNoOBH() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_Journal-Url' => 'http://example.com'
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}
	
	public function testServiceDocumentNoJournalUrl() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentBadObh() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '',
			'HTTP_Journal-Url' => 'http://example.com'
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}
	
	public function testServiceDocumentBadJournalUrl() {
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
			'HTTP_Journal-Url' => ''
		));
		$this->assertEquals(400, $this->client->getResponse()->getStatusCode());
	}

	public function testServiceDocumentContentNewJournal() {
		$this->assertCount(2, $this->em->getRepository('AppBundle:Journal')->findAll());
		
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F',
			'HTTP_Journal-Url' => 'http://example.com'
		));
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
		$xml = $this->getXml($this->client);
		$this->assertEquals('service', $xml->getName());
		$this->assertEquals(2.0, $this->getXmlValue($xml, '//sword:version'));
		$this->assertEquals('No', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
		$this->assertEquals('The PKP PLN does not know about this journal yet.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
		$this->assertCount(3, $xml->xpath('//pkp:terms_of_use/*'));
		$this->assertEquals('PKP PLN deposit for 7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//atom:title'));
		$this->assertEquals('http://localhost/api/sword/2.0/col-iri/7AD045C9-89E6-4ACA-8363-56FE9A45C34F', $this->getXmlValue($xml, '//app:collection/@href'));
		
		$this->assertCount(3, $this->em->getRepository('AppBundle:Journal')->findAll());
		
		$journal = $this->em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => '7AD045C9-89E6-4ACA-8363-56FE9A45C34F'));
		$this->assertNotNull($journal);
		$this->assertEquals('unknown', $journal->getTitle());
		$this->assertEquals('http://example.com', $journal->getUrl());
		$this->assertEquals('new', $journal->getStatus());		
	}
	
	public function testServiceDocumentContentWhitelistedJournal() {
		$this->assertCount(2, $this->em->getRepository('AppBundle:Journal')->findAll());
		
		$this->client->request('GET', '/api/sword/2.0/sd-iri', array(), array(), array(
			'HTTP_On-Behalf-Of' => 'c0a65967-32bd-4ee8-96de-c469743e563a',
			'HTTP_Journal-Url' => 'http://example.com'
		));
		$this->assertEquals(200, $this->client->getResponse()->getStatusCode());
		$xml = $this->getXml($this->client);
		$this->assertEquals('service', $xml->getName());
		$this->assertEquals(2.0, $this->getXmlValue($xml, '//sword:version'));
		$this->assertEquals('Yes', $this->getXmlValue($xml, '//pkp:pln_accepting/@is_accepting'));
		$this->assertEquals('The PKP PLN can accept deposits from this journal.', $this->getXmlValue($xml, '//pkp:pln_accepting'));
		$this->assertCount(3, $xml->xpath('//pkp:terms_of_use/*'));
		$this->assertEquals('PKP PLN deposit for C0A65967-32BD-4EE8-96DE-C469743E563A', $this->getXmlValue($xml, '//atom:title'));
		$this->assertEquals('http://localhost/api/sword/2.0/col-iri/C0A65967-32BD-4EE8-96DE-C469743E563A', $this->getXmlValue($xml, '//app:collection/@href'));
		
		$this->em->clear();
		$this->assertCount(2, $this->em->getRepository('AppBundle:Journal')->findAll());
		$journal = $this->em->getRepository('AppBundle:Journal')->findOneBy(array('uuid' => 'C0A65967-32BD-4EE8-96DE-C469743E563A'));
		$this->assertEquals('http://example.com', $journal->getUrl());
	}
}
