<?php

namespace AppBundle\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;

/**
 * TermOfUseHistory controller. The history is read only.
 *
 * @Route("/termhistory")
 */
class TermOfUseHistoryController extends Controller
{
    /**
     * Lists all TermOfUseHistory entities.
     *
     * @Route("/", name="termhistory")
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
        $dql = 'SELECT e FROM AppBundle:TermOfUseHistory e ORDER BY e.termId';
        $query = $em->createQuery($dql);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a TermOfUseHistory entity.
     *
     * @Route("/{id}", name="termhistory_show")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     * @param Request $id
     *
     * @return array
     */
    public function showAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:TermOfUseHistory');
        $query = $repo->createQueryBuilder('h')
                ->where('h.termId = :termId')
                ->getQuery()
                ->setParameter('termId', $id);

        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'entities' => $entities,
        );
    }
}
