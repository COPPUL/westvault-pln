<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Deposit;
use AppBundle\Entity\Institution;
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
     * Check if a institution's uuid is whitelised or blacklisted. The rules are:.
     *
     * If the institution uuid is whitelisted, return true
     * If the institution uuid is blacklisted, return false
     * Return the pln_accepting parameter from parameters.yml
     *
     * @param string $institution_uuid
     *
     * @return bool
     */
    private function checkAccess($institution_uuid)
    {
        /* @var BlackWhitelist */
        $bw = $this->get('blackwhitelist');
        $this->get('monolog.logger.sword')->info("Checking access for {$institution_uuid}");
        if ($bw->isWhitelisted($institution_uuid)) {
            $this->get('monolog.logger.sword')->info("whitelisted {$institution_uuid}");

            return true;
        }
        if ($bw->isBlacklisted($institution_uuid)) {
            $this->get('monolog.logger.sword')->notice("blacklisted {$institution_uuid}");

            return false;
        }

        return $this->container->getParameter('pln_accepting');
    }

    /**
     * The institution with UUID $uuid has contacted the PLN. Add a record for the
     * institution if there isn't one, otherwise update the timestamp.
     *
     * @param string $uuid
     * @param string $url
     *
     * @return Institution
     */
    private function institutionContact($uuid, $url)
    {
        $logger = $this->get('monolog.logger.sword');
        $em = $this->getDoctrine()->getManager();
        $institutionRepo = $em->getRepository('AppBundle:Institution');
        $institution = $institutionRepo->findOneBy(array(
            'uuid' => $uuid,
        ));
        if ($institution !== null) {
            $institution->setTimestamp();
            if ($institution->getUrl() !== $url) {
                $logger->warning("institution URL mismatch - {$uuid} - {$institution->getUrl()} - {$url}");
                $institution->setUrl($url);
            }
        } else {
            $institution = new Institution();
            $institution->setUuid($uuid);
            $institution->setUrl($url);
            $institution->setTimestamp();
            $institution->setTitle('unknown');
            $institution->setIssn('unknown');
            $institution->setStatus('new');
            $institution->setEmail('unknown@unknown.com');
            $em->persist($institution);
        }
        if ($institution->getStatus() !== 'new') {
            $institution->setStatus('healthy');
        }
        $em->flush($institution);

        return $institution;
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
     * @param Institution $institution
     *
     * @return string
     */
    private function getNetworkMessage(Institution $institution)
    {
        if ($institution->getOjsVersion() === null) {
            return $this->container->getParameter('network_default');
        }
        if (version_compare($institution->getOjsVersion(), $this->container->getParameter('min_ojs_version'), '>=')) {
            return $this->container->getParameter('network_accepting');
        }

        return $this->container->getParameter('network_oldojs');
    }

    /**
     * Return a SWORD service document for a institution. Requires On-Behalf-Of
     * and Institution-Url HTTP headers.
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
        $institutionUrl = $this->fetchHeader($request, 'Institution-Url');

        $accepting = $this->checkAccess($obh);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("service document - {$request->getClientIp()} - {$obh} - {$institutionUrl} - {$acceptingLog}");
        if (!$obh) {
            throw new SwordException(400, "Missing On-Behalf-Of header for {$institutionUrl}");
        }
        if (!$institutionUrl) {
            throw new SwordException(400, "Missing Institution-Url header for {$obh}");
        }

        $institution = $this->institutionContact($obh, $institutionUrl);

        /* @var Response */
        $response = $this->render('AppBundle:Sword:serviceDocument.xml.twig', array(
            'onBehalfOf' => $obh,
            'accepting' => $accepting ? 'Yes' : 'No',
            'message' => $this->getNetworkMessage($institution),
            'colIri' => $this->generateUrl(
                'create_deposit',
                array('institution_uuid' => $obh),
                UrlGeneratorInterface::ABSOLUTE_URL
            ),
            'terms' => $this->getTermsOfUse(),
        ));
        /* @var Response */
        $response->headers->set('Content-Type', 'text/xml');

        return $response;
    }

    /**
     * Create a deposit.
     *
     * @Route("/col-iri/{institution_uuid}", name="create_deposit")
     * @Method("POST")
     *
     * @param Request $request
     * @param string  $institution_uuid
     *
     * @return Response
     */
    public function createDepositAction(Request $request, $institution_uuid)
    {
        /* @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $institution_uuid = strtoupper($institution_uuid);
        $accepting = $this->checkAccess($institution_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("create deposit - {$request->getClientIp()} - {$institution_uuid} - {$acceptingLog}");
        if (!$accepting) {
            throw new SwordException(400, 'Not authorized to create deposits.');
        }

        if ($this->checkAccess($institution_uuid) === false) {
            $logger->notice("create deposit [Not Authorized] - {$request->getClientIp()} - {$institution_uuid}");
            throw new SwordException(400, 'Not authorized to make deposits.');
        }

        $xml = $this->parseXml($request->getContent());
        try {
            $institution = $this->get('institutionbuilder')->fromXml($xml, $institution_uuid);
            $institution->setStatus('healthy');
            $deposit = $this->get('depositbuilder')->fromXml($institution, $xml);
        } catch (\Exception $e) {
            throw new SwordException(500, $e->getMessage(), $e);
        }

        /* @var Response */
        $response = $this->statementAction($request, $institution->getUuid(), $deposit->getDepositUuid());
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
     * @Route("/cont-iri/{institution_uuid}/{deposit_uuid}/state", name="statement")
     * @Method("GET")
     *
     * @param Request $request
     * @param string  $institution_uuid
     * @param string  $deposit_uuid
     *
     * @return Response
     */
    public function statementAction(Request $request, $institution_uuid, $deposit_uuid)
    {
        /* @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $institution_uuid = strtoupper($institution_uuid);
        $accepting = $this->checkAccess($institution_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("statement - {$request->getClientIp()} - {$institution_uuid} - {$acceptingLog}");

        if (!$accepting && !$this->get('security.authorization_checker')->isGranted('IS_AUTHENTICATED_REMEMBERED')) {
            throw new SwordException(400, 'Not authorized to request statements.');
        }

        $em = $this->getDoctrine()->getManager();

        /* @var Institution */
        $institution = $em->getRepository('AppBundle:Institution')->findOneBy(array('uuid' => $institution_uuid));
        if ($institution === null) {
            throw new SwordException(400, 'Institution UUID not found.');
        }

        /* @var Deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $deposit_uuid));
        if ($deposit === null) {
            throw new SwordException(400, 'Deposit UUID not found.');
        }

        if ($institution->getId() !== $deposit->getInstitution()->getId()) {
            throw new SwordException(400, 'Deposit does not belong to institution.');
        }

        $institution->setContacted(new DateTime());
        $institution->setStatus('healthy');
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
     * @Route("/cont-iri/{institution_uuid}/{deposit_uuid}/edit")
     * @Method("PUT")
     *
     * @param Request $request
     * @param string  $institution_uuid
     * @param string  $deposit_uuid
     *
     * @return Response
     */
    public function editAction(Request $request, $institution_uuid, $deposit_uuid)
    {
        /* @var LoggerInterface */
        $logger = $this->get('monolog.logger.sword');
        $institution_uuid = strtoupper($institution_uuid);
        $deposit_uuid = strtoupper($deposit_uuid);
        $accepting = $this->checkAccess($institution_uuid);
        $acceptingLog = 'not accepting';
        if ($accepting) {
            $acceptingLog = 'accepting';
        }

        $logger->notice("edit deposit - {$request->getClientIp()} - {$institution_uuid} - {$acceptingLog}");
        if (!$accepting) {
            throw new SwordException(400, 'Not authorized to edit deposits.');
        }

        $em = $this->getDoctrine()->getManager();

        /** @var Institution $institution */
        $institution = $em->getRepository('AppBundle:Institution')->findOneBy(array(
            'uuid' => $institution_uuid,
        ));
        if ($institution === null) {
            throw new SwordException(400, 'Institution UUID not found.');
        }

        /** @var Deposit $deposit */
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array(
            'depositUuid' => $deposit_uuid,
        ));
        if ($deposit === null) {
            throw new SwordException(400, "Deposit UUID {$deposit_uuid} not found.");
        }

        if ($institution->getId() !== $deposit->getInstitution()->getId()) {
            throw new SwordException(400, 'Deposit does not belong to institution.');
        }

        $institution->setContacted(new DateTime());
        $institution->setStatus('healthy');
        $xml = $this->parseXml($request->getContent());
        $newDeposit = $this->get('depositbuilder')->fromXml($institution, $xml);

        /* @var Response */
        $response = $this->statementAction($request, $institution_uuid, $deposit_uuid);
        $response->headers->set(
            'Location',
            $newDeposit->getDepositReceipt(),
            true
        );
        $response->setStatusCode(Response::HTTP_CREATED);

        return $response;
    }
}
