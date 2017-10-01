<?php

namespace AppBundle\Utility;

use GuzzleHttp\Message\Response;
use GuzzleHttp\Stream\Stream;
use PHPUnit_Framework_TestCase;

class PingResultTest extends PHPUnit_Framework_TestCase {

	/**
	 * @var PingResult
	 */
	protected $result;

	public function setUp() {
		$response = new Response(200);
		$response->setBody(Stream::factory($this->getPingXml()));
		$this->result = new PingResult($response);
	}

	public function testHasXml() {
		$this->assertTrue($this->result->hasXml());
	}

	public function testHasError() {
		$this->assertFalse($this->result->hasError());
	}

	public function testGetHttpStatus() {
		$this->assertEquals(200, $this->result->getHttpStatus());
	}

	public function testGetError() {
		$this->assertEquals(null, $this->result->getError());
	}

	public function testGetOjsRelease() {
		$this->assertEquals('2.4.8.0', $this->result->getOjsRelease());
	}

	public function testGetPluginReleaseVersion() {
		$this->assertEquals('1.2.0.0', $this->result->getPluginReleaseVersion());
	}

	public function tetGetPluginReleaseDate() {
		$this->assertEquals('2015-07-13', $this->result->getPluginReleaseDate());
	}

	public function testIsPluginCurrent() {
		$this->assertEquals('Yes', $this->result->isPluginCurrent());
	}

	public function testAreTermsAccepted() {
		$this->assertEquals('yes', $this->result->areTermsAccepted());
	}

	public function testGetJournalTitle() {
		$this->assertEquals('Journal of Mocked Titles', $this->result->getJournalTitle());
	}

	public function testGetArticleCount() {
		$this->assertEquals(72, $this->result->getArticleCount());
	}

	public function testGetArticleTitles() {
		$articles = $this->result->getArticleTitles();
		$this->assertEquals(2, count($articles));
		$this->assertEquals('2015-07-14 19:57:31', (string)$articles[0]['date']);
		$this->assertEquals('Transnational Publics: Asylum and the Arts in the City of Glasgow', $articles[0]['title']);		
		$this->assertEquals('2015-07-14 19:57:31', (string)$articles[1]['date']);
		$this->assertEquals('Storytelling and the Lives of Asylum Seekers', $articles[1]['title']);
	}

	private function getPingXml() {
		$str = <<<ENDSTR
<plnplugin>
    <ojsInfo>
        <release>2.4.8.0</release>
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
        <title>Journal of Mocked Titles</title>
        <articles count="72">
            <article pubDate="2015-07-14 19:57:31">Transnational Publics: Asylum and the Arts in the City of Glasgow</article>
            <article pubDate="2015-07-14 19:57:31">Storytelling and the Lives of Asylum Seekers</article>
        </articles>
    </journalInfo>
</plnplugin>
ENDSTR;
		return $str;
	}
}
