<?php

namespace AppBundle\Services\SwordClient;

use AppBundle\Services\SwordClient;
use AppBundle\Utility\AbstractTestCase;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use GuzzleHttp\Subscriber\History;
use GuzzleHttp\Subscriber\Mock;

class ServiceDocumentErrorTest extends AbstractTestCase {
	
	/**
	 * @var SwordClient
	 */
	protected $sc;
	
	public function setUp() {
		parent::setUp();
		$this->sc = $this->getContainer()->get('swordclient');
		$client = new Client();
		
		$this->history = new History();
		$client->getEmitter()->attach($this->history);
		
		$mock = new Mock([
			new Response(
				400, 
				array(),
				$this->getResponseBody()
            )]);
		$client->getEmitter()->attach($mock);
		$this->sc->setClient($client);
		// do not call serviceDocument() here - it's going throw an exception.
	}
	
	public function fixtures() {
		return array(
			'AppBundle\DataFixtures\ORM\test\LoadJournals'
		);
	}

	public function testInstance() {
		$this->assertInstanceOf('AppBundle\Services\SwordClient', $this->sc);
	}
	
	public function testError() {
		try {
			$this->sc->serviceDocument($this->references->getReference('journal'));
		} catch (Exception $ex) {
			$this->assertStringStartsWith('Client error response', $ex->getMessage());
			return;
		}
		$this->fail('no exception.');
	}
	
	private function getResponseBody() {
		$str = <<<ENDSTR

<sword:error xmlns="http://www.w3.org/2005/Atom" 
             xmlns:sword="http://purl.org/net/sword/"
             href="http://purl.org/net/sword/error/ErrorBadRequest">    
    <summary>400 - Required HTTP header On-Behalf-Of missing.</summary>
    <sword:verboseDescription>
#0 /Users/michael/Sites/lom/src/LOCKSSOMatic/SwordBundle/Controller/SwordController.php(154): LOCKSSOMatic\SwordBundle\Controller\SwordController-&gt;fetchHeader(Object(Symfony\Component\HttpFoundation\Request), &#039;On-Behalf-Of&#039;, true)
#1 [internal function]: LOCKSSOMatic\SwordBundle\Controller\SwordController-&gt;serviceDocumentAction(Object(Symfony\Component\HttpFoundation\Request))
#2 /Users/michael/Sites/lom/app/bootstrap.php.cache(3128): call_user_func_array(Array, Array)
#3 /Users/michael/Sites/lom/app/bootstrap.php.cache(3090): Symfony\Component\HttpKernel\HttpKernel-&gt;handleRaw(Object(Symfony\Component\HttpFoundation\Request), 1)
#4 /Users/michael/Sites/lom/app/bootstrap.php.cache(3241): Symfony\Component\HttpKernel\HttpKernel-&gt;handle(Object(Symfony\Component\HttpFoundation\Request), 1, true)
#5 /Users/michael/Sites/lom/app/bootstrap.php.cache(2458): Symfony\Component\HttpKernel\DependencyInjection\ContainerAwareHttpKernel-&gt;handle(Object(Symfony\Component\HttpFoundation\Request), 1, true)
#6 /Users/michael/Sites/lom/web/app_dev.php(28): Symfony\Component\HttpKernel\Kernel-&gt;handle(Object(Symfony\Component\HttpFoundation\Request))
#7 {main}
    </sword:verboseDescription>
</sword:error>
ENDSTR;
		$stream = Stream::factory($str);
		return $stream;
	}
}
