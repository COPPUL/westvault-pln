<?php

namespace AppBundle\Controller;

use AppBundle\Entity\Provider;
use AppBundle\Form\ProviderType;
use DateTime;
use Exception;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\Form\Form;
use Symfony\Component\HttpFoundation\Request;

/**
 * Provider controller. Providers can be deleted, and it's possible to update
 * the provider health status.
 *
 * @Route("/provider")
 */
class ProviderController extends Controller
{
    /**
     * Lists all Provider entities.
     *
     * @Route("/", name="provider")
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
        $repo = $em->getRepository('AppBundle:Provider');
        $qb = $repo->createQueryBuilder('e');
        $qb->orderBy('e.id');
        $query = $qb->getQuery();

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
     * Search providers.
     *
     * In the ProviderController, this action must appear before showAction().
     *
     * @Route("/search", name="provider_search")
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

        $repo = $em->getRepository('AppBundle:Provider');
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
     * Finds and displays a Provider entity.
     *
     * @Route("/{id}", name="provider_show")
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

        $entity = $em->getRepository('AppBundle:Provider')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Provider entity.');
        }

        return array(
            'entity' => $entity,
        );
    }

    /**
     * Build and return a form to delete a provider.
     *
     * @param Provider $provider
     *
     * @return Form
     */
    private function createDeleteForm(Provider $provider)
    {
        $formBuilder = $this->createFormBuilder($provider);
        $formBuilder->setAction($this->generateUrl('provider_delete', array('id' => $provider->getId())));
        $formBuilder->setMethod('DELETE');
        $formBuilder->add('confirm', 'checkbox', array(
            'label' => 'Yes, delete this provider',
            'mapped' => false,
            'value' => 'yes',
            'required' => false,
        ));
        $formBuilder->add('delete', 'submit', array('label' => 'Delete'));
        $form = $formBuilder->getForm();

        return $form;
    }

    /**
     * Creates a form to edit a Provider entity.
     *
     * @param Document $entity The entity
     *
     * @return Form The form
     */
    private function createEditForm(Provider $entity)
    {
        $form = $this->createForm(new ProviderType(), $entity, array(
            'action' => $this->generateUrl('provider_edit', array('id' => $entity->getId())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }

    /**
     * Displays a form to edit an existing Provider entity.
     *
     * @Route("/{id}/edit", name="provider_edit")
     * @Method({"GET", "PUT"})
     * @Template()
	 * @param Request $request
	 * @param Page $page
     */
    public function editAction(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Provider')->find($id);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);

        if ($editForm->isSubmitted() && $editForm->isValid()) {
            $em->flush();
            $this->addFlash('success', 'The provider has been updated.');
            return $this->redirectToRoute('provider_show', array('id' => $id));
        }

        return array(
            'entity' => $entity,
            'edit_form' => $editForm->createView(),
        );
    }

    /**
     * Finds and displays a Provider entity.
     *
     * @Route("/{id}/delete", name="provider_delete")
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

        $entity = $em->getRepository('AppBundle:Provider')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Provider entity.');
        }

        if ($entity->countDeposits() > 0) {
            $this->addFlash('warning', 'Providers which have made deposits cannot be deleted.');

            return $this->redirect($this->generateUrl('provider_show', array('id' => $entity->getId())));
        }

        $form = $this->createDeleteForm($entity);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $form->get('confirm')->getData()) {
            //            Once ProviderUrls are a thing, uncomment these lines.
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

            $this->addFlash('success', 'Provider deleted.');

            return $this->redirect($this->generateUrl('provider'));
        }

        return array(
            'entity' => $entity,
            'form' => $form->createView(),
        );
    }

    /**
     * Update a provider status.
     *
     * @Route("/{id}/status", name="provider_status")
     *
     * @param Request $request
     * @param string  $id
     */
    public function updateStatus(Request $request, $id)
    {
        $em = $this->getDoctrine()->getManager();
        $entity = $em->getRepository('AppBundle:Provider')->find($id);
        $status = $request->query->get('status');
        if (!$status) {
            $this->addFlash('error', "The provider's status has not been changed.");
        } else {
            $entity->setStatus($status);
            if ($status === 'healthy') {
                $entity->setContacted(new DateTime());
            }
            $this->addFlash('success', "The provider's status has been updated.");
            $em->flush();
        }

        return $this->redirect($this->generateUrl('provider_show', array('id' => $entity->getId())));
    }

    /**
     * Ping a provider and display the result.
     *
     * @Route("/ping/{id}", name="provider_ping")
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

        $entity = $em->getRepository('AppBundle:Provider')->find($id);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Provider entity.');
        }

        try {
            $result = $this->container->get('ping')->ping($entity);
            if (!$result->hasXml() || $result->hasError() || ($result->getHttpStatus() !== 200)) {
                $this->addFlash('warning', "The ping did not complete. HTTP {$result->getHttpStatus()} {$result->getError()}");

                return $this->redirect($this->generateUrl('provider_show', array(
                    'id' => $id,
                )));
            }
            $entity->setContacted(new DateTime());
            $entity->setTitle($result->getProviderTitle());
            $entity->setStatus('healthy');
            $em->flush($entity);

            return array(
                'entity' => $entity,
                'ping' => $result,
            );
        } catch (Exception $e) {
            $this->addFlash('danger', $e->getMessage());

            return $this->redirect($this->generateUrl('provider_show', array(
                'id' => $id,
            )));
        }
    }

    /**
     * Show the deposits for a provider.
     *
     * @Route("/{id}/deposits", name="provider_deposits")
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
        $provider = $em->getRepository('AppBundle:Provider')->find($id);
        if (!$provider) {
            throw $this->createNotFoundException('Unable to find Provider entity.');
        }

        $qb = $em->getRepository('AppBundle:Deposit')->createQueryBuilder('d')
                ->where('d.provider = :provider')
                ->setParameter('provider', $provider);
        $paginator = $this->get('knp_paginator');
        $entities = $paginator->paginate(
            $qb,
            $request->query->getInt('page', 1),
            25
        );

        return array(
            'provider' => $provider,
            'entities' => $entities,
        );
    }
}
