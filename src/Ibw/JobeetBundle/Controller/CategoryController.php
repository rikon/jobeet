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
	public function showAction($slug, $page)
	{
		$em = $this->getDoctrine()->getManager();
		$category = $em->getRepository('IbwJobeetBundle:Category')->findOneBySlug($slug);
		if(!$category) {
			throw $this->createNotFoundException('Unable to find Category Entity.');
		}
		
		//for atom
		$latestJob = $em->getRepository('IbwJobeetBundle:Job')->getLatestPost($category->getId());
		if($latestJob) {
			$lastUpdated = $latestJob->getCreatedAt()->format(DATE_ATOM);
		} else {
			$lastUpdated = new \DateTime();
			$lastUpdated = $lastUpdated->format(DATE_ATOM);
		}		
		
		
		
		$total_jobs = $em->getRepository('IbwJobeetBundle:Job')->countActiveJobs($category->getId());
		$jobs_per_page = $this->container->getParameter('max_jobs_on_category');
		$last_page = ceil($total_jobs/$jobs_per_page);
		$previous_page = $page > 1 ? ($page - 1) : 1;
		$next_page = $page > $last_page ?  $last_page : ($page + 1) ; 
		$category->setActiveJobs($em->getRepository('IbwJobeetBundle:Job')->getActiveJobs($category->getId(), $jobs_per_page, ($page - 1) * $jobs_per_page ));
		
		return $this->render('IbwJobeetBundle:Category:show.html.twig', array(
			'category'	=> $category,
			'last_page'	=> $last_page,
			'previous_page'	=> $previous_page,
			'next_page'	=> $next_page,
			'current_page'	=> $page,
			'total_jobs'	=> $total_jobs,
			'feedId' => sha1($this->get('router')->generate('ibw_jobeet_category', array('slug' => $category->getSlug(), 'format' => 'atom'), true)),
			'lastUpdated' => $lastUpdated
		));
		//$category->setActiveJobs($em->getRepository('IbwJobeetBundle:Job')->getActiveJobs($category->getId()));
		//return $this->render('IbwJobeetBundle:Category:show.html.twig', array(
		//	'category' => $category
		//));
		
	}
}