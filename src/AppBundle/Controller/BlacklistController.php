<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Blacklist;
use AppBundle\Form\BlacklistType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Blacklist controller. The blacklist is read/write.
 *
 * @Route("/blacklist")
 */
class BlacklistController extends Controller
{
    /**
     * Lists all Blacklist entities.
     *
     * @Route("/", name="blacklist")
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
        $dql = 'SELECT e FROM AppBundle:Blacklist e';
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
     * Creates a new Blacklist entity.
     *
     * @Route("/", name="blacklist_create")
     * @Method("POST")
     * @Template("AppBundle:Blacklist:new.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        $entity = new Blacklist();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $this->addFlash('success', 'The blacklist entry has been saved.');

            return $this->redirect($this->generateUrl('blacklist_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Blacklist entity.
     *
     * @param Blacklist $entity The entity
     *
     * @return Form  The form
     * @return array
     */
    private function createCreateForm(Blacklist $entity)
    {
        $form = $this->createForm(new BlacklistType(), $entity, array(
            'action' => $this->generateUrl('blacklist_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Blacklist entity.
     *
     * @Route("/new", name="blacklist_new")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function newAction()
    {
        $entity = new Blacklist();
        $form = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * @Route("/search", name="blacklist_search")
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

        $repo = $em->getRepository('AppBundle:Blacklist');
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
     * Finds and displays a Blacklist entity.
     *
     * @Route("/{id}", name="blacklist_show")
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

        $entity = $em->getRepository('AppBundle:Blacklist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blacklist entity.');
        }

        $institution = $em->getRepository('AppBundle:Institution')->findOneBy(array(
            'uuid' => $entity->getUuid(),
        ));

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'institution' => $institution,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Blacklist entity.
     *
     * @Route("/{id}/edit", name="blacklist_edit")
     * @Method("GET")
     * @Template()
     *
     * @param string $id
     *
     * @return array
     */
    public function editAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Blacklist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blacklist entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Creates a form to edit a Blacklist entity.
     *
     * @param Blacklist $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Blacklist $entity)
    {
        $form = $this->createForm(new BlacklistType(), $entity, array(
            'action' => $this->generateUrl('blacklist_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Blacklist entity.
     *
     * @Route("/{id}", name="blacklist_update")
     * @Method("PUT")
     * @Template("AppBundle:Blacklist:edit.html.twig")
     *
     * @param Request $request
     * @param string  $id
     *
     * @return array
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Blacklist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blacklist entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The blacklist entry has been updated.');

            return $this->redirect($this->generateUrl('blacklist_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Blacklist entity.
     *
     * @Route("/{id}/delete", name="blacklist_delete")
     *
     * @param Request $request
     * @param Request $request
     *
     * @return array
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Blacklist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Blacklist entity.');
        }

        $em->remove($entity);
        $this->addFlash('success', 'The blacklist entry has been deleted.');
        $em->flush();

        return $this->redirect($this->generateUrl('blacklist'));
    }

    /**
     * Creates a form to delete a Blacklist entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('blacklist_delete', array('id' => $id)))
                        ->setMethod('DELETE')
                        ->add('submit', 'submit', array('label' => 'Delete'))
                        ->getForm()
        ;
    }
}
