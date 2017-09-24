<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Provider;
use AppBundle\Entity\TermOfUse;
use AppBundle\Exception\SwordException;
use AppBundle\Utility\Namespaces;
use DateTime;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use SimpleXMLElement;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * SWORD v2 Controller to receive deposits.
 *
 * See http://swordapp.org/sword-v2/sword-v2-specifications/
 *
 * Set a prefix for all routes in this controller.
 *
 * @Route("/api/sword/2.0")
 */
class SwordController extends Controller
{
    /**
     * Parse an XML string, register the namespaces it uses, and return the
     * result.
     *
     * @param string $content
     *
     * @return SimpleXMLElement
     */
    private function parseXml($content)
    {
        $xml = new SimpleXMLElement($content);
        $ns = new Namespaces();
        $ns->registerNamespaces($xml);

        return $xml;
    }

    /**
     * Fetch an HTTP header. Checks for the header name, and a variant prefixed
     * with X-, and for the header as a query string parameter.
     *
     * @param Request $request
     * @param string  $name
     *
     * @return string|null
     */
    private function fetchHeader(Request $request, $name)
    {
        if ($request->headers->has($name)) {
            return $request->headers->get($name);
        }
        if ($request->headers->has('X-'.$name)) {
            return $request->headers->has('X-'.$name);
        }
        if ($request->query->has($name)) {
            return $request->query->get($name);
        }

        return;
    }

    /**
     * Check if a provider's uuid is whitelised or blacklisted. The rules are:.
     *
     * If the provider uuid is whitelisted, return true
     * If the provider uuid is blacklisted, return false
     * Return the pln_accepting parameter from parameters.yml
     *
     * @param string $provider_uuid
     *
     * @return bool
     */
    private function checkAccess($provider_uuid)
    {
        if($provider_uuid === \Ramsey\Uuid\Uuid::NIL) {
            return false;
        }
        /* @var BlackWhitelist */
        $bw = $this->get('blackwhitelist');
        $this->get('monolog.logger.sword')->info("Checking access for {$provider_uuid}");
        if ($bw->isWhitelisted($provider_uuid)) {
            $this->get('monolog.logger.sword')->info("whitelisted {$provider_uuid}");
            return true;
        }
        if ($bw->isBlacklisted($provider_uuid)) {
            $this->get('monolog.logger.sword')->notice("blacklisted {$provider_uuid}");
            return false;
        }
        return $this->container->getParameter('pln_accepting');
    }

    /**
     * The provider with UUID $uuid has contacted the PLN. Add a record for the
     * provider if there isn't one, otherwise update the timestamp.
     *
     * @param string $uuid
     * @param string $providerName
     *
     * @return Provider
     */
    private function providerContact($uuid, $providerName)
    {
        $logger = $this->get('monolog.logger.sword');
        $em = $this->getDoctrine()->getManager();
        $providerRepo = $em->getRepository('AppBundle:Provider');
        $provider = $providerRepo->findOneBy(array(
            'uuid' => $uuid,
        ));
        if ($provider !== null) {
            if ($provider->getName() !== $providerName) {
                $logger->warning("provider name mismatch - {$uuid} - {$provider->getName()} - {$providerName}");
                $provider->setName($providerName);
            }
        } else {
            $provider = new Provider();
            $provider->setUuid($uuid);
            $provider->setName($providerName);
            $em->persist($provider);
        }
        $em->flush($provider);

        return $provider;
    }

    /**
     * Fetch the terms of use from the database.
     *
     * @todo does this really need to be a function?
     *
     * @return TermOfUse[]
     */
    private function getTermsOfUse()
    {
        $em = $this->getDoctrine()->getManager();
        /* @var TermOfUseRepository */
        $repo = $em->getRepository('AppBundle:TermOfUse');
        $terms = $repo->getTerms();

        return $terms;
    }

    /**
     * Figure out which message to return for the network status widget in OJS.
     *
     * @param Provider $provider
     *
     * @return string
     */
    private function getNetworkMessage(Provider $provider)
    {
        $bw = $this->get('blackwhitelist');
        if ($bw->isWhitelisted($provider->getUuid())) {
            return $this->container->getParameter('network_accepting');
        }
        return $this->container->getParameter('network_default');
    }

    /**
     * Return a SWORD service document for a provider. Requires On-Behalf-Of
     * and Provider-Url HTTP headers.
     *
     * @Route("/sd-iri", name="service_document")
     * @Method("GET")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function serviceDocumentAction(Request $request)
    {
        /* @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');

        $obh = strtoupper($this->fetchHeader($request, 'On-Behalf-Of'));
        $providerName = $this->fetchHeader($request, 'Provider-Name');

        $accepting = $this->checkAccess($obh);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("service document - {$request->getClientIp()} - {$obh} - {$providerName} - {$acceptingLog}");
        if (!$obh) {
            throw new SwordException(400, "Missing On-Behalf-Of header for {$providerName}");
        }
        if (!$providerName) {
            throw new SwordException(400, "Missing Provider-Name header for {$obh}");
        }

        $provider = $this->providerContact($obh, $providerName);

        /* @var Response */
        $response = $this->render('AppBundle:Sword:serviceDocument.xml.twig', array(
            'onBehalfOf' => $obh,
            'accepting' => $accepting ? 'Yes' : 'No',
            'message' => $this->getNetworkMessage($provider),
            'colIri' => $this->generateUrl(
                'create_deposit',
                array('provider_uuid' => $obh),
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'terms' => $this->getTermsOfUse(),
            'termsUpdated' => $this->getDoctrine()->getManager()->getRepository('AppBundle:TermOfUse')->getLastUpdated(),
        ));
        /* @var Response */
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * Create a deposit.
     *
     * @Route("/col-iri/{provider_uuid}", name="create_deposit")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $provider_uuid
     *
     * @return Response
     */
    public function createDepositAction(Request $request, $provider_uuid)
    {
        /* @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $provider_uuid = strtoupper($provider_uuid);
        $accepting = $this->checkAccess($provider_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("create deposit - {$request->getClientIp()} - {$provider_uuid} - {$acceptingLog}");
        if (!$accepting) {
            throw new SwordException(400, 'Not authorized to create deposits.');
        }

        if ($this->checkAccess($provider_uuid) === false) {
            $logger->notice("create deposit [Not Authorized] - {$request->getClientIp()} - {$provider_uuid}");
            throw new SwordException(400, 'Not authorized to make deposits.');
        }

        $xml = $this->parseXml($request->getContent());
        try {
            $provider = $this->get('providerbuilder')->fromXml($xml, $provider_uuid);
            $deposit = $this->get('depositbuilder')->fromXml($provider, $xml);
        } catch (\Exception $e) {
            throw new SwordException(500, $e->getMessage(), $e);
        }

        /* @var Response */
        $response = $this->statementAction($request, $provider->getUuid(), $deposit->getDepositUuid());
        $response->headers->set(
            'Location',
            $deposit->getDepositReceipt(),
            true
        );
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }

    /**
     * Check that status of a deposit by fetching the sword statemt.
     *
     * @Route("/cont-iri/{provider_uuid}/{deposit_uuid}/state", name="statement")
     * @Method("GET")
     *
     * @param Request $request
     * @param string  $provider_uuid
     * @param string  $deposit_uuid
     *
     * @return Response
     */
    public function statementAction(Request $request, $provider_uuid, $deposit_uuid)
    {
        /* @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $provider_uuid = strtoupper($provider_uuid);
        $accepting = $this->checkAccess($provider_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("statement - {$request->getClientIp()} - {$provider_uuid} - {$acceptingLog}");

        if (!$accepting && !$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new SwordException(400, 'Not authorized to request statements.');
        }

        $em = $this->getDoctrine()->getManager();

        /* @var Provider */
        $provider = $em->getRepository('AppBundle:Provider')->findOneBy(array('uuid' => $provider_uuid));
        if ($provider === null) {
            throw new SwordException(400, 'Provider UUID not found.');
        }

        /* @var Deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $deposit_uuid));
        if ($deposit === null) {
            throw new SwordException(400, 'Deposit UUID not found.');
        }

        if ($provider->getId() !== $deposit->getProvider()->getId()) {
            throw new SwordException(400, 'Deposit does not belong to provider.');
        }

        $provider->setContacted(new DateTime());
        $em->flush();

        /* @var Response */
        $response = $this->render('AppBundle:Sword:statement.xml.twig', array(
            'deposit' => $deposit,
        ));
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * Edit a deposit with an HTTP PUT.
     *
     * @Route("/cont-iri/{provider_uuid}/{deposit_uuid}/edit")
     * @Method("PUT")
     *
     * @param Request $request
     * @param string  $provider_uuid
     * @param string  $deposit_uuid
     *
     * @return Response
     */
    public function editAction(Request $request, $provider_uuid, $deposit_uuid)
    {
        /* @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $provider_uuid = strtoupper($provider_uuid);
        $deposit_uuid = strtoupper($deposit_uuid);
        $accepting = $this->checkAccess($provider_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("edit deposit - {$request->getClientIp()} - {$provider_uuid} - {$acceptingLog}");
        if (!$accepting) {
            throw new SwordException(400, 'Not authorized to edit deposits.');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var Provider $provider */
        $provider = $em->getRepository('AppBundle:Provider')->findOneBy(array(
            'uuid' => $provider_uuid,
        ));
        if ($provider === null) {
            throw new SwordException(400, 'Provider UUID not found.');
        }

        /** @var Deposit $deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array(
            'depositUuid' => $deposit_uuid,
        ));
        if ($deposit === null) {
            throw new SwordException(400, "Deposit UUID {$deposit_uuid} not found.");
        }

        if ($provider->getId() !== $deposit->getProvider()->getId()) {
            throw new SwordException(400, 'Deposit does not belong to provider.');
        }

        $provider->setContacted(new DateTime());
        $xml = $this->parseXml($request->getContent());
        $newDeposit = $this->get('depositbuilder')->fromXml($provider, $xml);

        /* @var Response */
        $response = $this->statementAction($request, $provider_uuid, $deposit_uuid);
        $response->headers->set(
            'Location',
            $newDeposit->getDepositReceipt(),
            true
        );
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
