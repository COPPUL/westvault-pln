<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * AuContainer controller. AuContainers are read-only.
 *
 * @Route("/aucontainer")
 */
class AuContainerController extends Controller
{
    /**
     * Lists all AuContainer entities.
     *
     * @Route("/", name="aucontainer")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $dql = 'SELECT e FROM AppBundle:AuContainer e ORDER BY e.id';
        $query = $em->createQuery($dql);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        $openContainer = $em->getRepository('AppBundle:AuContainer')->getOpenContainer();

        return array(
            'entities' => $entities,
            'openContainer' => $openContainer,
        );
    }

    /**
     * Finds and displays a AuContainer entity.
     *
     * @Route("/{id}", name="aucontainer_show")
     * @Method("GET")
     * @Template()
     *
     * @param string $id
     *
     * @return array
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:AuContainer')->find($id);
        $openContainer = $em->getRepository('AppBundle:AuContainer')->getOpenContainer();

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find AuContainer entity.');
        }

        return array(
            'entity' => $entity,
            'openContainer' => $openContainer,
        );
    }
}
