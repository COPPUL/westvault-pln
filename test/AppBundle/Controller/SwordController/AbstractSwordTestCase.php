<?php

namespace AppBundle\Controller\SwordController;

use AppBundle\Utility\AbstractTestCase;
use AppBundle\Utility\Namespaces;
use GuzzleHttp\Stream\Stream;
use SimpleXMLElement;
use Symfony\Component\BrowserKit\Client;
use Symfony\Component\HttpFoundation\Response;

abstract class AbstractSwordTestCase extends AbstractTestCase {
	/**
	 * @var Client
	 */
	protected $client;

	/**
	 * @var Namespaces
	 */
	protected $ns;

	public function setUp() {
		parent::setUp();
		$this->client = static::createClient();
		$this->ns = new Namespaces();
	}
	
    public function fixtures() {
        return array(
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
            'AppBundle\DataFixtures\ORM\test\LoadDeposits',
			'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
			'AppBundle\DataFixtures\ORM\test\LoadWhitelist',
        );
    }
	
	/**
	 * @return SimpleXMLElement
	 * @param Client $client
	 */
	protected function getXml() {
		$xml = new SimpleXMLElement($this->client->getResponse()->getContent());
		$this->ns->registerNamespaces($xml);
		return $xml;
	}
	
    /**
     * Get a single XML value as a string.
     * 
     * @param SimpleXMLElement $xml
     * @param type $xpath
     * @return string
     * @throws Exception
     */
    public function getXmlValue(SimpleXMLElement $xml, $xpath) {
        $data = $xml->xpath($xpath);
        if (count($data) === 1) {
            return trim((string) $data[0]);
        }
        if (count($data) === 0) {
            return null;
        }
        throw new Exception("Too many elements for '{$xpath}'");
    }
}
