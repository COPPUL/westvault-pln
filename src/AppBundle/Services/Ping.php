<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Services;

use AppBundle\Entity\Institution;
use AppBundle\Utility\PingResult;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\ORM\EntityManager;
use Exception;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\XmlParseException;
use Monolog\Logger;

/**
 * Send a PING request to a institution, and return the result.
 */
class Ping
{
    /**
     * @var EntityManager
     */
    private $em;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Client
     */
    private $client;

    /**
     * Set the ORM thing.
     *
     * @param Registry $registry
     */
    public function setManager(Registry $registry)
    {
        $this->em = $registry->getManager();
    }

    /**
     * Set the service logger.
     *
     * @param Logger $logger
     */
    public function setLogger(Logger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Set the HTTP client.
     *
     * @param Client $client
     */
    public function setClient(Client $client)
    {
        $this->client = $client;
    }

    /**
     * @return Client
     */
    public function getClient()
    {
        if (!$this->client) {
            $this->client = new Client();
        }

        return $this->client;
    }

    /**
     * Ping a institution, check on it's health, etc.
     *
     * @param Institution $institution
     *
     * @return PingResult
     *
     * @throws Exception
     */
    public function ping(Institution $institution)
    {
        $this->logger->notice("Pinging {$institution}");
        $url = $institution->getGatewayUrl();
        $client = $this->getClient();
        try {
            $response = $client->get($url, array(
                'allow_redirects' => true,
                'headers' => array(
                    'User-Agent' => 'PkpPlnBot 1.0; http://pkp.sfu.ca',
                    'Accept' => 'application/xml,text/xml,*/*;q=0.1',
                ),
            ));
            $pingResponse = new PingResult($response);
            if ($pingResponse->getHttpStatus() === 200) {
                $institution->setContacted(new DateTime());
                $institution->setTitle($pingResponse->getInstitutionTitle('(unknown title)'));
                $institution->setOjsVersion($pingResponse->getOjsRelease());
                $institution->setTermsAccepted($pingResponse->areTermsAccepted() === 'yes');
            } else {
                $institution->setStatus('ping-error');
            }
            $this->em->flush($institution);

            return $pingResponse;
        } catch (RequestException $e) {
            $institution->setStatus('ping-error');
            $this->em->flush($institution);
            if ($e->hasResponse()) {
                return new PingResult($e->getResponse());
            }
            throw $e;
        } catch (XmlParseException $e) {
            $institution->setStatus('ping-error');
            $this->em->flush($institution);

            return new PingResult($e->getResponse());
        } catch (Exception $e) {
            $institution->setStatus('ping-error');
            $this->em->flush($institution);
            throw $e;
        }
    }
}
