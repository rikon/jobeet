<?php
namespace Ibw\JobeetBundle\Controller;


use Sonata\AdminBundle\Controller\CRUDController as Controller;
use Sonata\DoctrineORMAdminBundle\Datagrid\ProxyQuery as ProxyQueryInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class AffiliateAdminController extends Controller
{
	public function batchActionActivate(ProxyQueryInterface $selectedModelQuery)
	{
		if($this->admin->isGranted('EDIT') === false && $this->admin->isGranted('DELETE') === false ) {
			throw new AccessDeniedException();
		}
		
		$request = $this->get('request');
		$modelManager  = $this->admin->getModelManager();
		$selectModels = $selectedModelQuery->execute();
		
		try {
			
			foreach ($selectModels as $selectedModel) {
				$selectedModel->activate();
				$modelManager->update($selectedModel);
				
				
				//send email
				$message = \Swift_Message::newInstance()
				->setSubject('Jobeet affiliate token')
				->setFrom('ri_kon@comsq.com')
				->setTo($selectedModel->getEmail())
				->setBody(
						$this->renderView('IbwJobeetBundle:Affiliate:email.txt.twig', array('affiliate' => $selectedModel->getToken())))
						;
				
				$this->get('mailer')->send($message);
				
			}
			
		} catch(\Exception $e) {
			$this->get('session')->getFlashBag()->add('sonota_flash_error', $e->getMessage());
			return new RedirectResponse($this->generateUrl('list', $this->admin->getFilterParameters()));
		}
		
		
		$this->get('session')->getFlashBag()->add('sonata_flash_success', sprintf('The selected accounts have been activated'));
		
		return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
		
	}
	
	
	
	
	public function batchActionDeactivate(ProxyQueryInterface $selectedModelQuery)
	{
		if($this->admin->isGranted('EDIT') === false || $this->admin->isGranted('DELETE') === false) {
			throw new AccessDeniedException();
		}
	
		$request = $this->get('request');
		$modelManager = $this->admin->getModelManager();
	
		$selectedModels = $selectedModelQuery->execute();
	
		try {
			foreach($selectedModels as $selectedModel) {
				$selectedModel->deactivate();
				$modelManager->update($selectedModel);
			}
		} catch(\Exception $e) {
			$this->get('session')->getFlashBag()->add('sonata_flash_error', $e->getMessage());
	
			return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
		}
	
		$this->get('session')->getFlashBag()->add('sonata_flash_success',  sprintf('The selected accounts have been deactivated'));
	
		return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
	}
	
	
	
	public function activateAction($id)
	{
		
		if($this->admin->isGranted('EDIT') === false) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
		$affiliate = $em->getRepository('IbwJobeetBundle:Affiliate')->findOneById($id);
		
		try {
			$affiliate->setIsActive(true);
			$em->flush();
			
			$message = \Swift_Message::newInstance()
						->setSubject('Jobeet affiliate token')
						->setFrom('ri_kon@comsq.com')
						->setTo($affiliate->getEmail())
						->setBody($this->render('IbwJobeetBundle:Affiliate:email.txt.twig', array('affiliate'=>$affiliate->getToken())))
			;
			
			$this->get('mailer')->send($message);
			
		} catch(\Exception $e) {
			$this->get('session')->getFlashBag()->add('sonata_flash_error', $e->getMessage());
		
			return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
		}
		
		return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
		
	}
	
	public function deactivateAction($id)
	{
		if($this->admin->isGranted('EDIT') === false) {
			throw new AccessDeniedException();
		}
		
		$em = $this->getDoctrine()->getManager();
		$affiliate = $em->getRepository('IbwJobeetBundle:Affiliate')->findOneById($id);
		
		try {
			$affiliate->setIsActive(false);
			$em->flush();
		} catch(\Exception $e) {
			$this->get('session')->getFlashBag()->add('sonata_flash_error', $e->getMessage());
		
			return new RedirectResponse($this->admin->generateUrl('list', $this->admin->getFilterParameters()));
		}
		
		return new RedirectResponse($this->admin->generateUrl('list',$this->admin->getFilterParameters()));
		
	}
	
	
}