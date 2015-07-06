<?php
namespace Ibw\JobeetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

/**
 * Category controller
 * @author rimac
 *
 */
class CategoryController extends Controller
{
	public function showAction($slug)
	{
		$em = $this->getDoctrine()->getManager();
		$category = $em->getRepository('IbwJobeetBundle:Category')->findOneBySlug($slug);
		if(!$category) {
			throw $this->createNotFoundException('Unable to find Category Entity.');
		}
		$category->setActiveJobs($em->getRepository('IbwJobeetBundle:Job')->getActiveJobs($category->getId()));
		
		return $this->render('IbwJobeetBundle:Category:show.html.twig', array(
			'category' => $category
		));
		
	}
}