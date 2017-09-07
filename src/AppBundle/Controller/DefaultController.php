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
     * @Route("/fetch/{institutionUuid}/{depositUuid}.zip", name="fetch")
     *
     * @param Request $request
     * @param string  $institutionUuid
     * @param string  $depositUuid
     *
     * @return BinaryFileResponse
     */
    public function fetchAction(Request $request, $institutionUuid, $depositUuid) {
        $institutionUuid = strtoupper($institutionUuid);
        $depositUuid = strtoupper($depositUuid);
        $logger = $this->get('monolog.logger.lockss');
        $logger->notice("fetch - {$request->getClientIp()} - {$institutionUuid} - {$depositUuid}");
        $em = $this->container->get('doctrine');
        $institution = $em->getRepository('AppBundle:Institution')->findOneBy(array('uuid' => $institutionUuid));
        $deposit = $em->getRepository('AppBundle:Deposit')->findOneBy(array('depositUuid' => $depositUuid));
        if (!$deposit) {
            $logger->error("fetch - 404 DEPOSIT NOT FOUND - {$request->getClientIp()} - {$institutionUuid} - {$depositUuid}");
            throw new NotFoundHttpException("{$institutionUuid}/{$depositUuid}.zip does not exist.");
        }
        if ($deposit->getInstitution()->getId() !== $institution->getId()) {
            $logger->error("fetch - 400 JOURNAL MISMATCH - {$request->getClientIp()} - {$institutionUuid} - {$depositUuid}");
            throw new BadRequestHttpException("The requested Institution ID does not match the deposit's institution ID.");
        }
        $path = $this->get('filepaths')->getStagingBagPath($deposit);
        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            $logger->error("fetch - 404 PACKAGE NOT FOUND - {$request->getClientIp()} - {$institutionUuid} - {$depositUuid}");
            throw new NotFoundHttpException("{$institutionUuid}/{$depositUuid}.zip does not exist.");
        }

        return new BinaryFileResponse($path);
    }

    /**
     * The ONIX-PH was hosted at /onix.xml which was a dumb thing. Redirect to
     * the proper URL at /feeds/onix.xml.
     *
     * This URI must be public in security.yml
     *
     * @Route("/onix.xml")
     */
    public function onyxRedirect() {
        return new RedirectResponse(
                $this->generateUrl('onix', array('_format' => 'xml')), Response::HTTP_MOVED_PERMANENTLY
        );
    }

    /**
     * Fetch the current ONYX-PH metadata file and serve it up. The file is big
     * and nasty. It isn't generated on the fly - there must be a cron tab to
     * generate the file once in a while.
     *
     * This URI must be public in security.yml
     *
     * @see http://www.editeur.org/127/ONIX-PH/
     *
     * @param Request $request
     * @Route("/feeds/onix.{_format}", name="onix", requirements={"_format":"xml|csv"})
     */
    public function onyxFeedAction($_format) {
        $path = $this->container->get('filepaths')->getOnixPath($_format);
        $fs = new Filesystem();
        if (!$fs->exists($path)) {
            $this->container->get('logger')->critical("The ONIX-PH file could not be found at {$path}");
            throw new NotFoundHttpException('The ONIX-PH file could not be found.');
        }

        $contentType = '';
        switch ($_format) {
            case 'xml':
                $contentType = 'text/xml';
                break;
            case 'csv':
                $contentType = 'text/csv';
                break;
        }

        return new BinaryFileResponse($path, 200, array(
            'Content-Type' => $contentType,
        ));
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
