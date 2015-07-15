<?php
namespace Ibw\JobeetBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class ApiController extends Controller
{
	public function listAction(Request $request, $token)
	{
		$em = $this->getDoctrine()->getManager();
		
		$jobs = array();
		
		$repo = $em->getRepository('IbwJobeetBundle:Affiliate');
		$affiliate = $repo->getForToken($token);
		
		if(!$affiliate) {
			throw $this->createNotFoundException('This affiliate account dose not exists!');
		}
		
		$repo = $em->getRepository('IbwJobeetBundle:Job');
		$active_jobs = $repo->getActiveJobs(null, null, null, $affiliate->getId());
		
		foreach($active_jobs as $job) {
			$jobs[$this->get('router')->generate('ibw_job_show', array('company' => $job->getCompanySlug(), 'location' => $job->getLocationSlug(), 'id' => $job->getId(), 'position' => $job->getPositionSlug()), true)] = $job->asArray($request->getHost());
		}
		$format = $this->getRequest()->getRequestFormat();
		$jsonData = json_encode($jobs);
		
		if($format == 'json') {
			$headers = array('Content-Type' => 'application/json');
			$response = new Response($jsonData, 200, $headers);
			return $response;
		}
		
		return $this->render('IbwJobeetBundle:Api:jobs.' . $format . '.twig', array('jobs'=>$jobs));
	}
}