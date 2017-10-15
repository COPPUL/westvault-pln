<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Default controller for the application, handles the home page and a few others.
 */
class DefaultController extends Controller {

    /**
     * The LOCKSS permision statement, required for LOCKSS to harvest
     * content.
     */
    const PERMISSION_STMT = 'LOCKSS system has permission to collect, preserve, and serve this Archival Unit.';

    /**
     * Home page. There's different content for anonymous users vs logged in
     * users.
     *
     * @Route("/", name="home")
     *
     * @return Response
     */
    public function indexAction() {
        $em = $this->container->get('doctrine');
        $user = $this->getUser();
        if (!$user || !$this->getUser()->hasRole('ROLE_USER')) {
            return $this->render('AppBundle:Default:indexAnon.html.twig');
        }
        return $this->render('AppBundle:Default:indexUser.html.twig');
    }

    /**
     * View one document.
     *
     * @param string $path
     * @Route("/docs/{path}", name="doc_view")
     * @Template()
     *
     * @return array
     */
    public function docsViewAction($path) {
        $em = $this->container->get('doctrine');
        $user = $this->getUser();
        $doc = $em->getRepository('AppBundle:Document')->findOneBy(array(
            'path' => $path,
        ));
        if (!$doc) {
            throw new NotFoundHttpException("The requested page {$path} could not be found.");
        }

        return array('doc' => $doc);
    }

    /**
     * @Route("/docs", name="doc_list")
     * @Template()
     *
     * @return array
     */
    // Must be after docsViewAction()
    public function docsListAction() {
        $em = $this->container->get('doctrine');
        $docs = $em->getRepository('AppBundle:Document')->findAll();

        return array('docs' => $docs);
    }

    /**
     * Return the permission statement for LOCKSS.
     *
     * @Route("/permission", name="lockss_permission")
     *
     * @param Request $request
     *
     * @return Response
     */
    public function permissionAction(Request $request) {
        $this->get('monolog.logger.lockss')->notice("permission - {$request->getClientIp()}");
        $response = new Response(self::PERMISSION_STMT, Response::HTTP_OK, array(
            'content-type' => 'text/plain',
        ));

        return $response;
    }

    /**
     * Fetch a processed and packaged deposit.
     *
     * @Route("/fetch/{providerUuid}/{depositUuid}", name="fetch")
     *
     * @param Request $request
     * @param string  $providerUuid
     * @param string  $depositUuid
     *
     * @return BinaryFileResponse
     */
    public function fetchAction(Request $request, $providerUuid, $depositUuid) {
        $providerUuid = strtoupper($providerUuid);
        $depositUuid = strtoupper($depositUuid);
        $logger = $this->get('monolog.logger.lockss');
        $logger->notice("fetch - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");
        $em = $this->container->get('doctrine');
        $provider = $em->getRepository('AppBundle:Provider')->findOneBy(array('uuid' => $providerUuid));
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $depositUuid));
        if (!$deposit) {
            $logger->error("fetch - 404 DEPOSIT NOT FOUND - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");
            throw new NotFoundHttpException("Deposit {$providerUuid}/{$depositUuid} does not exist.");
        }
        if ($deposit->getProvider()->getId() !== $provider->getId()) {
            $logger->error("fetch - 400 JOURNAL MISMATCH - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");
            throw new BadRequestHttpException("The requested Provider ID does not match the deposit's provider ID.");
        }
        $path = $this->get('filepaths')->getHarvestFile($deposit);
        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            $logger->error("fetch - 404 PACKAGE NOT FOUND - {$request->getClientIp()} - {$providerUuid} - {$depositUuid}");
            throw new NotFoundHttpException("File {$providerUuid}/{$depositUuid} does not exist.");
        }

        return new BinaryFileResponse($path);
    }

    /**
     * Someone requested an RSS feed of the Terms of Use. This route generates
     * the feed in a RSS, Atom, and a custom JSON format as requested. It might
     * not be used anywhere.
     *
     * This URI must be public in security.yml
     *
     * @Route("/feeds/terms.{_format}",
     *      defaults={"_format"="atom"},
     *      name="feed_terms",
     *      requirements={"_format"="json|rss|atom"}
     * )
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function termsFeedAction(Request $request) {
        $em = $this->get('doctrine')->getManager();
        $repo = $em->getRepository('AppBundle:TermOfUse');
        $terms = $repo->getTerms();

        return array('terms' => $terms);
    }

}
