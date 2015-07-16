<?php
namespace Ibw\JobeetBundle\Controller;


use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ibw\JobeetBundle\Entity\Affiliate;


class AffiliateController extends Controller
{
	
	public function newAction()
	{
		$entity = new Affiliate();
		$form = $this->createForm(new AffiliateType(), $entity);
		
		return $this->render('IbwJobeetBundle:Affiliate:affiliate_new.html.twig', array(
			'entity' => $entity,
			'form' => $form->createView(),
		));
	}
	
}