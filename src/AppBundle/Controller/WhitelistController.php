<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Whitelist;
use AppBundle\Form\WhitelistType;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Whitelist controller. The whitelist is read/write.
 *
 * @Route("/whitelist")
 */
class WhitelistController extends Controller
{
    /**
     * Lists all Whitelist entities.
     *
     * @Route("/", name="whitelist")
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
        $dql = 'SELECT e FROM AppBundle:Whitelist e';
        $query = $em->createQuery($dql);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            25
        );
        $providerRepo = $em->getRepository('AppBundle:Provider');

        return array(
            'entities' => $entities,
            'repo' => $providerRepo,
        );
    }

    /**
     * Creates a new Whitelist entity.
     *
     * @Route("/", name="whitelist_create")
     * @Method("POST")
     * @Template("AppBundle:Whitelist:new.html.twig")
     *
     * @param Request $request
     *
     * @return array
     */
    public function createAction(Request $request)
    {
        $entity = new Whitelist();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($entity);
            $em->flush();
            $this->addFlash('success', 'The whitelist entry has been saved.');

            return $this->redirect($this->generateUrl('whitelist_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Creates a form to create a Whitelist entity.
     *
     * @param Whitelist $entity The entity
     *
     * @return Form The form
     */
    private function createCreateForm(Whitelist $entity)
    {
        $form = $this->createForm(new WhitelistType(), $entity, array(
            'action' => $this->generateUrl('whitelist_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Whitelist entity.
     *
     * @Route("/new", name="whitelist_new")
     * @Method("GET")
     * @Template()
     *
     * @return array
     */
    public function newAction()
    {
        $entity = new Whitelist();
        $form = $this->createCreateForm($entity);

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Search for a whitelist entry by uuid, url, or comment.
     *
     * @Route("/search", name="whitelist_search")
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

        $repo = $em->getRepository('AppBundle:Whitelist');
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
     * Finds and displays a Whitelist entity.
     *
     * @Route("/{id}", name="whitelist_show")
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

        $entity = $em->getRepository('AppBundle:Whitelist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Whitelist entity.');
        }

        $provider = $em->getRepository('AppBundle:Provider')->findOneBy(array(
            'uuid' => $entity->getUuid(),
        ));

        $deleteForm = $this->createDeleteForm($id);

        return array(
            'entity' => $entity,
            'provider' => $provider,
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Displays a form to edit an existing Whitelist entity.
     *
     * @Route("/{id}/edit", name="whitelist_edit")
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

        $entity = $em->getRepository('AppBundle:Whitelist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Whitelist entity.');
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
     * Creates a form to edit a Whitelist entity.
     *
     * @param Whitelist $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Whitelist $entity)
    {
        $form = $this->createForm(new WhitelistType(), $entity, array(
            'action' => $this->generateUrl('whitelist_update', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Edits an existing Whitelist entity.
     *
     * @Route("/{id}", name="whitelist_update")
     * @Method("PUT")
     * @Template("AppBundle:Whitelist:edit.html.twig")
     *
     * @param Request $request
     * @param string  $id
     *
     * @return array
     */
    public function updateAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Whitelist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Whitelist entity.');
        }

        $deleteForm = $this->createDeleteForm($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The whitelist entry has been updated.');

            return $this->redirect($this->generateUrl('whitelist_show', array('id' => $entity->getId())));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        );
    }

    /**
     * Deletes a Whitelist entity.
     *
     * @Route("/{id}/delete", name="whitelist_delete")
     *
     * @param Request $request
     * @param string  $id
     *
     * @return array
     */
    public function deleteAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Whitelist')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Whitelist entity.');
        }

        $em->remove($entity);
        $this->addFlash('success', 'The whitelist entry has been deleted.');
        $em->flush();

        return $this->redirect($this->generateUrl('whitelist'));
    }

    /**
     * Creates a form to delete a Whitelist entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return Form The form
     */
    private function createDeleteForm($id)
    {
        return $this->createFormBuilder()
                        ->setAction($this->generateUrl('whitelist_delete', array('id' => $id)))
                        ->setMethod('DELETE')
                        ->add('submit', 'submit', array('label' => 'Delete'))
                        ->getForm()
        ;
    }
}
