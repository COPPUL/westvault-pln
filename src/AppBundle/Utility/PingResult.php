<?php

namespace AppBundle\Utility;

use Exception;
use GuzzleHttp\Exception\XmlParseException;
use GuzzleHttp\Message\Response;
use SimpleXMLElement;

/**
 * Capture all the information in an HTTP ping and store it for later.
 */
class PingResult
{
    /**
     * @var SimpleXMLElement
     */
    private $xml;

    /**
     * HTTP Status Code.
     *
     * @var int
     */
    private $status;

    /**
     * @var string
     */
    private $error;

    /**
     * @var string
     */
    private $body;

    /**
     * @var Response
     */
    private $response;

    /**
     * Construct a Ping response from an HTTP response.
     *
     * @param Response $response
     */
    public function __construct(Response $response)
    {
        $this->response = $response;
        $this->status = $response->getStatusCode();
        $this->error = null;
        $this->xml = null;
        $this->body = $response->getBody();
        try {
            $this->xml = $response->xml();
        } catch (Exception $ex) {
            $this->error = $ex->getMessage();
        } catch (XmlParseException $ex) {
            $this->error = $ex->getMessage();
        }
    }

    /**
     * Get the body of a ping response, optionally stripping out tags.
     *
     * @param type $stripTags
     *
     * @return type
     */
    public function getBody($stripTags = true)
    {
        if ($stripTags) {
            return strip_tags($this->body);
        }

        return $this->body;
    }

    /**
     * Get an XML value.
     *
     * @param string $q XPath query
     *
     * @return string|null
     */
    private function simpleQuery($q)
    {
        if ($this->xml === null) {
            return;
        }
        $element = $this->xml->xpath($q);
        if ($element) {
            return (string) $element[0];
        }

        return;
    }

    /**
     * Return true if the response contained valid XML.
     *
     * @return bool
     */
    public function hasXml()
    {
        return $this->xml !== null;
    }

    /**
     * @return SimpleXmlElement
     */
    public function getXml()
    {
        return $this->xml;
    }

    /**
     * Fetch a named header.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getHeader($name)
    {
        return $this->response->getHeader($name);
    }

    /**
     * Return true if the response was an error.
     *
     * @return bool
     */
    public function hasError()
    {
        return $this->error !== null;
    }

    /**
     * Return the HTTP status code.
     *
     * @return int
     */
    public function getHttpStatus()
    {
        return $this->status;
    }

    /**
     * Return the HTTP error if there was one.
     *
     * @return string|null
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get the OJS release version.
     *
     * @return string
     */
    public function getOjsRelease()
    {
        return $this->simpleQuery('/plnplugin/ojsInfo/release');
    }

    /**
     * Get the plugin release version.
     *
     * @return string
     */
    public function getPluginReleaseVersion()
    {
        return $this->simpleQuery('/plnplugin/pluginInfo/release');
    }

    /**
     * Get the release date for the plugin as a string.
     *
     * @return string
     */
    public function getPluginReleaseDate()
    {
        return $this->simpleQuery('/plnplugin/pluginInfo/releaseDate');
    }

    /**
     * Return 'Yes' if the plugin reports itself as current.
     *
     * @return string
     */
    public function isPluginCurrent()
    {
        if ($this->simpleQuery('/plnplugin/pluginInfo/current')) {
            return 'Yes';
        }

        return 'No';
    }

    /**
     * Return 'Yes' if the plugin reports the terms are accepted.
     *
     * @return string
     */
    public function areTermsAccepted()
    {
        return $this->simpleQuery('/plnplugin/pluginInfo/terms/@termsAccepted');
    }

    /**
     * Return the provider title.
     *
     * @return string
     */
    public function getProviderTitle($default = null)
    {
        $title = $this->simpleQuery('/plnplugin/providerInfo/title');
        if ($title === null) {
            return $default;
        }

        return $title;
    }

    /**
     * Return the article count.
     *
     * @return int
     */
    public function getArticleCount()
    {
        return $this->simpleQuery('/plnplugin/providerInfo/articles/@count');
    }

    /**
     * Return a list of the article titles in the ping.
     *
     * @return array
     */
    public function getArticleTitles()
    {
        $articles = array();
        if ($this->xml === null) {
            return $articles;
        }
        foreach ($this->xml->xpath('/plnplugin/providerInfo/articles/article') as $element) {
            $articles[] = array(
                'date' => $element['pubDate'],
                'title' => (string) $element,
            );
        }

        return $articles;
    }
}
