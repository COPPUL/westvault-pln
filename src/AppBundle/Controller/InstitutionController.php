<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Institution;
use AppBundle\Form\InstitutionType;
use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Institution controller. Institutions can be deleted, and it's possible to update
 * the institution health status.
 *
 * @Route("/institution")
 */
class InstitutionController extends Controller
{
    /**
     * Lists all Institution entities.
     *
     * @Route("/", name="institution")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function indexAction(Request $request)
    {
        /*
         * @var EntityManager
         */
        $em = $this->getDoctrine()->getManager();
        $repo = $em->getRepository('AppBundle:Institution');
        $qb = $repo->createQueryBuilder('e');
        $status = $request->query->get('status');
        if ($status !== null) {
            $qb->where('e.status = :status');
            $qb->setParameter('status', $status);
        }
        $qb->orderBy('e.id');
        $query = $qb->getQuery();

        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );
        $statuses = $repo->statusSummary();

        return array(
            'entities' => $entities,
            'statuses' => $statuses,
        );
    }

    /**
     * Search institutions.
     *
     * In the InstitutionController, this action must appear before showAction().
     *
     * @Route("/search", name="institution_search")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     *
     * @return array
     */
    public function searchAction(Request $request)
    {
        $em = $this->getDoctrine()->getManager();
        $q = $request->query->get('q', '');

        $repo = $em->getRepository('AppBundle:Institution');
        $paginator = $this->get('knp_paginator');

        $entities = array();
        $results = array();
        if ($q !== '') {
            $results = $repo->search($q);

            $entities = $paginator->paginate(
                $results,
                $request->query->getInt('page', 1),
                25
            );
        }

        return array(
            'q' => $q,
            'count' => count($results),
            'entities' => $entities,
        );
    }

    /**
     * Finds and displays a Institution entity.
     *
     * @Route("/{id}", name="institution_show")
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

        $entity = $em->getRepository('AppBundle:Institution')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Institution entity.');
        }

        return array(
            'entity' => $entity,
        );
    }

    /**
     * Build and return a form to delete a institution.
     *
     * @param Institution $institution
     *
     * @return Form
     */
    private function createDeleteForm(Institution $institution)
    {
        $formBuilder = $this->createFormBuilder($institution);
        $formBuilder->setAction($this->generateUrl('institution_delete', array('id' => $institution->getId())));
        $formBuilder->setMethod('DELETE');
        $formBuilder->add('confirm', 'checkbox', array(
            'label' => 'Yes, delete this institution',
            'mapped' => false,
            'value' => 'yes',
            'required' => false,
        ));
        $formBuilder->add('delete', 'submit', array('label' => 'Delete'));
        $form = $formBuilder->getForm();

        return $form;
    }

    /**
     * Creates a form to edit a Institution entity.
     *
     * @param Document $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Institution $entity)
    {
        $form = $this->createForm(new InstitutionType(), $entity, array(
            'action' => $this->generateUrl('institution_edit', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Displays a form to edit an existing Institution entity.
     *
     * @Route("/{id}/edit", name="institution_edit")
     * @Method({"GET", "PUT"})
     * @Template()
	 * @param Request $request
	 * @param Page $page
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Institution')->find($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The institution has been updated.');
            return $this->redirectToRoute('institution_show', array('id' => $id));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Finds and displays a Institution entity.
     *
     * @Route("/{id}/delete", name="institution_delete")
     * @Method({"GET","DELETE"})
     * @Template()
     *
     * @param Request $request
     * @param string  $id
     *
     * @return array
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Institution')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Institution entity.');
        }

        if ($entity->countDeposits() > 0) {
            $this->addFlash('warning', 'Institutions which have made deposits cannot be deleted.');

            return $this->redirect($this->generateUrl('institution_show', array('id' => $entity->getId())));
        }

        $form = $this->createDeleteForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->get('confirm')->getData()) {
            //            Once InstitutionUrls are a thing, uncomment these lines.
//            foreach($entity->getUrls() as $url) {
//                $em->remove($url);
//            }

            $whitelist = $em->getRepository('AppBundle:Whitelist')->findOneBy(array('uuid' => $entity->getUuid()));
            if ($whitelist) {
                $em->remove($whitelist);
            }
            $blacklist = $em->getRepository('AppBundle:Whitelist')->findOneBy(array('uuid' => $entity->getUuid()));
            if ($blacklist) {
                $em->remove($blacklist);
            }
            $em->remove($entity);
            $em->flush();

            $this->addFlash('success', 'Institution deleted.');

            return $this->redirect($this->generateUrl('institution'));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Update a institution status.
     *
     * @Route("/{id}/status", name="institution_status")
     *
     * @param Request $request
     * @param string  $id
     */
    public function updateStatus(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Institution')->find($id);
        $status = $request->query->get('status');
        if (!$status) {
            $this->addFlash('error', "The institution's status has not been changed.");
        } else {
            $entity->setStatus($status);
            if ($status === 'healthy') {
                $entity->setContacted(new DateTime());
            }
            $this->addFlash('success', "The institution's status has been updated.");
            $em->flush();
        }

        return $this->redirect($this->generateUrl('institution_show', array('id' => $entity->getId())));
    }

    /**
     * Ping a institution and display the result.
     *
     * @Route("/ping/{id}", name="institution_ping")
     * @Method("GET")
     * @Template()
     *
     * @param string $id
     *
     * @return array
     */
    public function pingAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Institution')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Institution entity.');
        }

        try {
            $result = $this->container->get('ping')->ping($entity);
            if (!$result->hasXml() || $result->hasError() || ($result->getHttpStatus() !== 200)) {
                $this->addFlash('warning', "The ping did not complete. HTTP {$result->getHttpStatus()} {$result->getError()}");

                return $this->redirect($this->generateUrl('institution_show', array(
                    'id' => $id,
                )));
            }
            $entity->setContacted(new DateTime());
            $entity->setTitle($result->getInstitutionTitle());
            $entity->setStatus('healthy');
            $em->flush($entity);

            return array(
                'entity' => $entity,
                'ping' => $result,
            );
        } catch (Exception $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirect($this->generateUrl('institution_show', array(
                'id' => $id,
            )));
        }
    }

    /**
     * Show the deposits for a institution.
     *
     * @Route("/{id}/deposits", name="institution_deposits")
     * @Method("GET")
     * @Template()
     *
     * @param Request $request
     * @param string  $id
     *
     * @return array
     */
    public function showDepositsAction(Request $request, $id)
    {
        /** var ObjectManager $em */
        $em = $this->getDoctrine()->getManager();
        $institution = $em->getRepository('AppBundle:Institution')->find($id);
        if (!$institution) {
            throw $this->createNotFoundException('Unable to find Institution entity.');
        }

        $qb = $em->getRepository('AppBundle:Deposit')->createQueryBuilder('d')
                ->where('d.institution = :institution')
                ->setParameter('institution', $institution);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'institution' => $institution,
            'entities' => $entities,
        );
    }
}
