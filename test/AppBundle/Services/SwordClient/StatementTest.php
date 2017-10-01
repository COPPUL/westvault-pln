<?php

namespace AppBundle\Services\SwordClient;

use AppBundle\Entity\Deposit;
use AppBundle\Services\SwordClient;
use AppBundle\Utility\AbstractTestCase;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class StatementTest extends AbstractTestCase {

    /**
     * @var SwordClient
     */
    protected $sc;

    /**
     * @var History
     */
    protected $history;
    
    protected $xml;

    public function setUp() {
        parent::setUp();
        $this->sc = $this->getContainer()->get('swordclient');
        $client = new Client();

        $this->history = new History();
        $client->getEmitter()->attach($this->history);

        $mock = new Mock([
            $this->getReceiptResponse(),
            $this->getStatementResponse(),
        ]);
        $client->getEmitter()->attach($mock);
        $this->sc->setClient($client);
        $deposit = $this->references->getReference('deposit');
        $deposit->setAuContainer($this->references->getReference('aucontainer'));
        $this->xml = $this->sc->statement($deposit);
    }

    public function fixtures() {
        return array(
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
            'AppBundle\DataFixtures\ORM\test\LoadDeposits',
            'AppBundle\DataFixtures\ORM\test\LoadAuContainers',
        );
    }
    
    public function testHistory() {
        $this->assertEquals(2, $this->history->count());
        $requests = $this->history->getRequests();
        
        $this->assertEquals('http://example.com/path/to/receipt', $requests[0]->getUrl());
        $this->assertEquals('http://lom.dv/DB416EBE1887/state', $requests[1]->getUrl());
    }
    
    public function testXml() {
        $this->assertInstanceOf('SimpleXMLElement', $this->xml);
    }

    private function getReceiptResponse() {
        $str = <<<ENDSTR
<entry xmlns="http://www.w3.org/2005/Atom" xmlns:sword="http://purl.org/net/sword/">
   <sword:treatment>Content URLs deposited to LOCKSSOMatic, collection PKP PLN Staging Server.</sword:treatment>
   <content src="http://lom.dv/web/app_dev.php/deposit/123456"/>
   <link rel="edit-media" href="http://lom.dv/E1C03A83A206"/>
   <link rel="http://purl.org/net/sword/terms/add" href="http://lom.dv/DB416EBE1887/edit"/>
   <link rel="edit" href="http://lom.dv/E1C03A83A206/DB416EBE1887/edit"/>
   <link rel="http://purl.org/net/sword/terms/statement" type="application/atom+xml;type=feed" 
         href="http://lom.dv/DB416EBE1887/state" />
</entry>
ENDSTR;
        $stream = Stream::factory($str);
        
        $response = new Response(201, array(
            'Location' => 'http://example.com/path/to/receipt',
        ), $stream);
        return $response;
    }
    
    private function getStatementResponse() {
        $str = <<<ENDSTR
<atom:feed xmlns:sword="http://purl.org/net/sword/terms/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:lom="http://lockssomatic.info/SWORD2">
    <atom:category scheme="http://purl.org/net/sword/terms/state" term="agreement" label="State">
        LOCKSS boxes have harvested the content and agree on the checksum.
    </atom:category>
    <atom:entry>
        <atom:category scheme="http://purl.org/net/sword/terms" term="http://purl.org/net/sword/terms/originalDeposit" label="Original Deposit"/>
        <sword:depositedOn>April 26, 2016 13:05</sword:depositedOn>
        <lom:agreement>1</lom:agreement>
    </atom:entry>
</atom:feed>
ENDSTR;
        $stream = Stream::factory($str);
        
        $response = new Response(200, array(), $stream);
        return $response;
    }
}
