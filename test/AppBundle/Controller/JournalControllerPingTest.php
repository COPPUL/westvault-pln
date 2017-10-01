<?php

namespace AppBundle\Controller;

use AppBundle\Services\Ping;
use AppBundle\Utility\AbstractTestCase;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response as GuzzleResponse;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;
use Symfony\Component\HttpFoundation\Response;

class JournalControllerPingTest extends AbstractTestCase {

	protected $prePing;
	
	protected $client;
	
    public function setUp() {
        parent::setUp();
		$this->prePing = array();
		
        $this->client = static::createClient(array(), array(
                'PHP_AUTH_USER' => 'admin@example.com',
                'PHP_AUTH_PW' => 'supersecret',
        ));
		
		$journal = $this->references->getReference('journal');
		$this->prePing['contacted'] = $journal->getContacted();
		$this->prePing['status'] = $journal->getstatus();
		$this->prePing['ojsVersion'] = $journal->getOjsVersion();
		$this->prePing['title'] = $journal->getTitle();
		
    }

    public function fixtures() {
        return array(
            'AppBundle\DataFixtures\ORM\test\LoadJournals',
            'AppUserBundle\DataFixtures\ORM\test\LoadUsers',
        );
    }
    
    public function testIndex() {
        $this->client->request('GET', '/journal/1');
        $response = $this->client->getResponse();
        $this->assertEquals(Response::HTTP_OK, $response->getStatusCode());
        $crawler = $this->client->getCrawler();
        
        $linkCrawler = $crawler->selectLink('Ping');
        $this->assertCount(1, $linkCrawler);
        $this->assertEquals('http://localhost/journal/ping/1', $linkCrawler->link()->getUri());
    }
    
	public function testPing200() {
		$controller = new JournalController();
		$container = self::$kernel->getContainer();
		
		$ping = $container->get('ping');
		$client = new Client();
		
		$this->history = new History();
		$client->getEmitter()->attach($this->history);
		
		$mock = new Mock([
			new GuzzleResponse(200, array(), $this->getResponseBody())
		]);
		$client->getEmitter()->attach($mock);
		$ping->setClient($client);
		
		$controller->setContainer($container);		
		$controller->pingAction(1);

		$this->em->clear();
		$journal = $this->em->getRepository('AppBundle:Journal')->find(1);
		$this->assertEquals('Minula', $journal->getTitle());
		$this->assertNotEquals($this->prePing['contacted'], $journal->getContacted());
		$this->assertEquals('healthy', $journal->getStatus());
		$this->assertEquals('2.4.8.1', $journal->getOjsVersion());		
	}

	private function getResponseBody() {
		$str = <<<ENDSTR
<plnplugin>
    <ojsInfo>
        <release>2.4.8.1</release>
    </ojsInfo>
    <pluginInfo>
        <release>1.2.0.0</release>
        <releaseDate>2015-07-13</releaseDate>
        <current>1</current>
        <prerequisites>
            <phpVersion>5.5.31</phpVersion>
            <curlVersion>7.43.0</curlVersion>
            <zipInstalled>yes</zipInstalled>
            <tarInstalled>yes</tarInstalled>
            <acron>yes</acron>
            <tasks>no</tasks>
        </prerequisites>
        <terms termsAccepted="yes">
            <term key="pkp:plugins.generic.pln.terms_of_use.jm_has_authority"
                updated="2016-03-07 17:52:35+00:00" accepted="2016-03-30T17:32:24+00:00"> I have the
                authority to include this journal's content in a secure preservation network and, if
                and when necessary, to make the content available in the PKP PLN. </term>
            <term key="pkp:plugins.generic.pln.terms_of_use.pkp_can_use_address"
                updated="2016-03-07 17:52:35+00:00" accepted="2016-03-30T17:32:24+00:00"> I agree to
                allow the PKP-PLN to include this journal's title and ISSN, and the email address of
                the Primary Contact, with the preserved journal content. </term>
        </terms>
    </pluginInfo>
    <journalInfo>
        <title>Minula</title>
        <articles count="72">
            <article pubDate="2015-07-14 19:57:31">Transnational Publics: Asylum and the Arts in the City of Glasgow</article>
            <article pubDate="2015-07-14 19:57:31">Storytelling and the Lives of Asylum Seekers</article>
        </articles>
    </journalInfo>
</plnplugin>			
ENDSTR;
		$stream = Stream::factory($str);
		return $stream;
	}
}
