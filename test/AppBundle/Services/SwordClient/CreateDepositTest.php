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

class CreateDepositTest extends AbstractTestCase {

    /**
     * @var SwordClient
     */
    protected $sc;

    /**
     * @var History
     */
    protected $history;

    public function setUp() {
        parent::setUp();
        $this->sc = $this->getContainer()->get('swordclient');
        $client = new Client();

        $this->history = new History();
        $client->getEmitter()->attach($this->history);

        $mock = new Mock([
            $this->getServiceDocumentResponse(),
            $this->getCreateDepositResponse(),
        ]);
        $client->getEmitter()->attach($mock);
        $this->sc->setClient($client);
        $deposit = $this->references->getReference('deposit');
        $deposit->setAuContainer($this->references->getReference('aucontainer'));
        $this->sc->createDeposit($deposit);
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
        
        $this->assertEquals('http://lom.dv/web/app_dev.php/api/sword/2.0/sd-iri', $requests[0]->getUrl());
        $this->assertEquals('http://lom.dv/AA84-E1C03A83A206', $requests[1]->getUrl());
    }

    public function testDepositSent() {
        $this->em->clear();
        $deposit = $this->em->getRepository('AppBundle:Deposit')->find(1);
        $this->assertEquals('http://example.com/path/to/receipt', $deposit->getDepositReceipt());
        $this->assertNotNull($deposit->getDepositReceipt());
    }

    private function getCreateDepositResponse() {
        $str = <<<ENDSTR
<entry xmlns="http://www.w3.org/2005/Atom" xmlns:sword="http://purl.org/net/sword/">
   <sword:treatment>Content URLs deposited to LOCKSSOMatic, collection PKP PLN Staging Server.</sword:treatment>
   <content src="http://lom.dv/web/app_dev.php/deposit/2"/>
   <link rel="edit-media" href="http://lom.dv/web/app_dev.php/api/sword/2.0/col-iri/C45B7FE2-4697-4108-AA84-E1C03A83A206"/>
   <link rel="http://purl.org/net/sword/terms/add" href="http://lom.dv/web/app_dev.php/api/sword/2.0/cont-iri/C45B7FE2-4697-4108-AA84-E1C03A83A206/2BC85105-3BDC-4AAE-BF13-DB416EBE1887/edit"/>
   <link rel="edit" href="http://lom.dv/web/app_dev.php/api/sword/2.0/cont-iri/C45B7FE2-4697-4108-AA84-E1C03A83A206/2BC85105-3BDC-4AAE-BF13-DB416EBE1887/edit"/>
   <link rel="http://purl.org/net/sword/terms/statement" type="application/atom+xml;type=feed" href="http://lom.dv/web/app_dev.php/api/sword/2.0/cont-iri/C45B7FE2-4697-4108-AA84-E1C03A83A206/2BC85105-3BDC-4AAE-BF13-DB416EBE1887/state" />
</entry>
ENDSTR;
        $stream = Stream::factory($str);
        
        $response = new Response(201, array(
            'Location' => 'http://example.com/path/to/receipt',
        ), $stream);
        return $response;
    }

    private function getServiceDocumentResponse() {
        $str = <<<ENDSTR
<service xmlns:dcterms="http://purl.org/dc/terms/"
   xmlns:sword="http://purl.org/net/sword/"
   xmlns:atom="http://www.w3.org/2005/Atom"
   xmlns:lom="http://lockssomatic.info/SWORD2"
   xmlns="http://www.w3.org/2007/app">
   <sword:version>2.0</sword:version>
   <sword:maxUploadSize>12345</sword:maxUploadSize>
   <lom:uploadChecksumType>SHA-1 MD5</lom:uploadChecksumType>
   <workspace>
      <atom:title>LOCKSSOMatic</atom:title>     
      <collection href="http://lom.dv/AA84-E1C03A83A206">
         <lom:pluginIdentifier id="ca.sfu.lib.plugin.pkppln.PkpPlnPlugin"/>
         <atom:title>PKP PLN Staging Server</atom:title>
         <accept>application/atom+xml;type=entry</accept> 
         <sword:mediation>true</sword:mediation>
         <lom:property name="base_url" definitional="true" />
         <lom:property name="container_number" definitional="true" />
         <lom:property name="manifest_url" definitional="true" />
         <lom:property name="permission_url" definitional="true" />
      </collection>
   </workspace>
</service>
ENDSTR;
        $stream = Stream::factory($str);
        $response = new Response(200, array(), $stream);
        return $response;
    }
}
