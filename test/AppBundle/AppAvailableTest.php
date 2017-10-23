<?php

namespace Tests\AppBundle;

use AppBundle\Utility\AbstractTestCase;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class AppAvailableTest extends AbstractTestCase {

    public function fixtures() {
        return array(
            'AppBundle\DataFixtures\ORM\test\LoadTermsOfUse',
        );
    }
    
 	/**
	 * @dataProvider publicUrlProvider
	 * @param string $url
	 */
	public function testPublicPages($url) {
		$client = self::createClient();
		$client->request('GET', $url);
        $this->assertEquals(200, $client->getResponse()->getStatusCode());
        $this->assertTrue($client->getResponse()->isSuccessful(), "{$url} is public.");
	}
	
	public function publicUrlProvider() {
		return array(
			array('/'),
            array('/login'),
            array('/resetting/request'),
            array('/docs'),
            array('/permission'),  
            array('/feeds/terms.rss'),
            array('/feeds/terms.json'),
            array('/feeds/terms.atom'),
    	);        
	}

    /**
     * @dataProvider protectedUrlProvider
     */
    public function testProtectedPages($url) {
		$client = self::createClient();
		$client->request('GET', $url);
        $this->assertTrue($client->getResponse()->isRedirect('http://localhost/login'), "{$url} should not be public");
    }
	
	public function protectedUrlProvider() {
		return array(
			array('/admin/user'),
            array('/aucontainer/'),
            array('/blacklist/'),
            array('/whitelist/'),
            array('/deposit/'),
            array('/admin/document/'),
            array('/journal/'),
            array('/termofuse/'),
            array('/register/'),
		);
	}
}