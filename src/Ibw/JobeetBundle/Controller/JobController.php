<?php

namespace Ibw\JobeetBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Ibw\JobeetBundle\Entity\Job;
use Ibw\JobeetBundle\Form\JobType;
use Ibw\JobeetBundle\IbwJobeetBundle;

/**
 * Job controller.
 *
 */
class JobController extends Controller
{

    /**
     * Lists all Job entities.
     *
     */
    public function indexAction()
    {
    	/*
        $em = $this->getDoctrine()->getManager();
        $entities = $em->getRepository('IbwJobeetBundle:Job')->findAll();
        */
    	
    	/*
    	$em = $this->getDoctrine()->getManager();
    	$query = $em->createQuery('SELECT j FROM IbwJobeetBundle:Job j WHERE j.expires_at > :date')->setParameter('date', date('Y-m-d H:i:s', time()));
    	$entities = $query->getResult();
    	*/
    	/*
    	$em = $this->getDoctrine()->getManager();
    	$entities = $em->getRepository('IbwJobeetBundle:Job')->getActiveJobs();
        return $this->render('IbwJobeetBundle:Job:index.html.twig', array(
            'entities' => $entities,
        ));
		*/
    	
    	
    	$max_per_category = $this->container->getParameter('max_jobs_on_homepage');
    	$em = $this->getDoctrine()->getManager();
    	$categories = $em->getRepository('IbwJobeetBundle:Category')->getWithJobs();
    	
    	foreach($categories as $category) {
    		$activeJobs = $em->getRepository('IbwJobeetBundle:Job')->getActiveJobs($category->getId(), $max_per_category);
    		$category->setActiveJobs($activeJobs);
    		
    		$cntActiveJos = $em->getRepository('IbwJobeetBundle:Job')->countActiveJobs($category->getId());
    		$cntMore = $cntActiveJos - $max_per_category;
    		$category->setMoreJobs($cntMore);
    	}
    	
    	return $this->render('IbwJobeetBundle:Job:index.html.twig',
			array('categories' => $categories));
    	
    }
    
    /**
     * Creates a new Job entity.
     *
     */
    public function createAction(Request $request)
    {
        $entity = new Job();
        $form = $this->createCreateForm($entity);
        $form->handleRequest($request);

        if ($form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            
            //$entity->file->move(__DIR__ . '/../../../../web/uploads/jobs', $entity->file->getClientOriginalName());
            //$entity->setLogo($entity->file->getClientOriginalName());
            
            $em->persist($entity);
            $em->flush();

            return $this->redirect($this->generateUrl('ibw_job_preview', array(
            		'token' => $entity->getToken(),
            		'company' => $entity->getCompanySlug(),
            		'location' => $entity->getLocationSlug(),
            		'position' => $entity->getPositionSlug()
            )));
        }

        return $this->render('IbwJobeetBundle:Job:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Creates a form to create a Job entity.
     *
     * @param Job $entity The entity
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createCreateForm(Job $entity)
    {
        $form = $this->createForm(new JobType(), $entity, array(
            'action' => $this->generateUrl('ibw_job_create'),
            'method' => 'POST',
        ));

        $form->add('submit', 'submit', array('label' => 'Create'));

        return $form;
    }

    /**
     * Displays a form to create a new Job entity.
     *
     */
    public function newAction()
    {
        $entity = new Job();
        $form   = $this->createCreateForm($entity);

        return $this->render('IbwJobeetBundle:Job:new.html.twig', array(
            'entity' => $entity,
            'form'   => $form->createView(),
        ));
    }

    /**
     * Finds and displays a Job entity.
     *
     */
    public function showAction($id)
    {
        $em = $this->getDoctrine()->getManager();

        //$entity = $em->getRepository('IbwJobeetBundle:Job')->find($id);
        $entity = $em->getRepository('IbwJobeetBundle:Job')->getActiveJob($id);
		if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }
        
        $deleteForm = $this->createDeleteForm($id);

        return $this->render('IbwJobeetBundle:Job:show.html.twig', array(
            'entity'      => $entity,
            'delete_form' => $deleteForm->createView(),
        ));
    }

    /**
     * Displays a form to edit an existing Job entity.
     *
     */
    public function editAction($token)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('IbwJobeetBundle:Job')->findOneByToken($token);
        
        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $editForm = $this->createEditForm($entity);
        $deleteForm = $this->createDeleteForm($token);

        return $this->render('IbwJobeetBundle:Job:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
        
        
        /*
        $editForm = $this->createForm(new JobType(), $entity);
        $deleteForm = $this->createDeleteForm($token);
        
        return $this->render('IbwJobeetBundle:Job:edit.html.twig', array(
        		'entity'      => $entity,
        		'edit_form'   => $editForm->createView(),
        		'delete_form' => $deleteForm->createView(),
        ));
        */
    }

    /**
    * Creates a form to edit a Job entity.
    *
    * @param Job $entity The entity
    *
    * @return \Symfony\Component\Form\Form The form
    */
    private function createEditForm(Job $entity)
    {
        $form = $this->createForm(new JobType(), $entity, array(
            'action' => $this->generateUrl('ibw_job_update', array('token' => $entity->getToken())),
            'method' => 'PUT',
        ));

        $form->add('submit', 'submit', array('label' => 'Update'));

        return $form;
    }
    /**
     * Edits an existing Job entity.
     *
     */
    public function updateAction(Request $request, $token)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('IbwJobeetBundle:Job')->findOneByToken($token);

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Job entity.');
        }

        $deleteForm = $this->createDeleteForm($token);
        $editForm = $this->createEditForm($entity);
        $editForm->handleRequest($request);
        
        if ($editForm->isValid()) {
        	//$em->persist($entity);
       		$em->flush();
       		return $this->redirect($this->generateUrl('ibw_job_preview', array(
       				'token' => $token,
       				'company' => $entity->getCompanySlug(),
       				'location' => $entity->getLocationSlug(),
       				'position' => $entity->getPositionSlug(),
       		)));
        }
        		

        return $this->render('IbwJobeetBundle:Job:edit.html.twig', array(
            'entity'      => $entity,
            'edit_form'   => $editForm->createView(),
            'delete_form' => $deleteForm->createView(),
        ));
    }
    
    public function previewAction($token)
    {
    	$em = $this->getDoctrine()->getManager();
    	$entity = $em->getRepository('IbwJobeetBundle:Job')->findOneByToken($token);
    	if(!$entity) {
    		throw $this->createNotFoundException('Unable to find job entity.');
    	}
    	$deleteForm = $this->createDeleteForm($token);
    	$publishForm = $this->createPublishForm($token);
    	
    	return $this->render('IbwJobeetBundle:Job:show.html.twig', array(
    		'entity' => $entity,
    		'delete_form' => $deleteForm->createView(),
    		'publish_form' => $publishForm->createView()	
    	));
    	
    }
    
    public function publishAction(Request $request, $token)
    {
	    $form = $this->createPublishForm($token);
	    $form->handleRequest($this->getRequest());
	
	    if ($form->isValid()) {
	        $em = $this->getDoctrine()->getManager();
	        $entity = $em->getRepository('IbwJobeetBundle:Job')->findOneByToken($token);
	
	        if (!$entity) {
	            throw $this->createNotFoundException('Unable to find Job entity.');
	        }
	
	        $entity->publish();
	        $em->persist($entity);
	        $em->flush();
	
	        $this->get('session')->getFlashBag()->add('notice', 'Your job is now online for 30 days.');
	    }
	
	    return $this->redirect($this->generateUrl('ibw_job_preview', array(
	        'company' => $entity->getCompanySlug(),
	        'location' => $entity->getLocationSlug(),
	        'token' => $entity->getToken(),
	        'position' => $entity->getPositionSlug()
	    )));

    }
    
    
    
    
    /**
     * Deletes a Job entity.
     *
     */
    public function deleteAction(Request $request, $token)
    {
    	$form = $this->createDeleteForm($token);
    	$form->bind($request);
    	
    	if ($form->isValid()) {
    		$em = $this->getDoctrine()->getManager();
    		$entity = $em->getRepository('IbwJobeetBundle:Job')->findOneByToken($token);
    	
    		if (!$entity) {
    			throw $this->createNotFoundException('Unable to find Job entity.');
    		}
    	
    		$em->remove($entity);
    		$em->flush();
    	}
    	
    	return $this->redirect($this->generateUrl('ibw_job'));
    	 
    	
    }

    /**
     * Creates a form to delete a Job entity by id.
     *
     * @param mixed $id The entity id
     *
     * @return \Symfony\Component\Form\Form The form
     */
    private function createDeleteForm($token)
    {
    	/*
        return $this->createFormBuilder()
            ->setAction($this->generateUrl('ibw_job_delete', array('token' => $token)))
            ->setMethod('DELETE')
            ->add('submit', 'submit', array('label' => 'Delete'))
            ->getForm()
        ;
        */
        
        return $this->createFormBuilder(array('token' => $token))
        ->add('token', 'hidden')
        ->getForm()
        ;
        
    }
    
    public function createPublishForm($token)
    {
        return $this->createFormBuilder(array('token' => $token))
        ->add('token', 'hidden')
        ->getForm()
        ;
    }
    
}
