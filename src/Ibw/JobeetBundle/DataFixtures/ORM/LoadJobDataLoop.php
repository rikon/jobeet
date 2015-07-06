<?php
namespace Ibw\JobeetBundle\DataFixtures\ORM;

use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\OrderedFixtureInterface;
use Doctrine\Common\Persistence\ObjectManager;
use Ibw\JobeetBundle\Entity\Job;


class LoadJobDataLoop extends AbstractFixture implements OrderedFixtureInterface
{

	public function load(ObjectManager $em)
	{
		for ($i = 100; $i <= 130; $i++) {
			$job = new Job();
			$job->setCategory($em->merge($this->getReference('category-programming')));
			$job->setType('full-time');
			$job->setCompany('Company '.$i);
			$job->setPosition('Web Developer');
			$job->setLocation('Paris, France');
			$job->setDescription('Lorem ipsum dolor sit amet, consectetur adipisicing elit.');
			$job->setHowToApply('Send your resume to lorem.ipsum [at] dolor.sit');
			$job->setIsPublic(true);
			$job->setIsActivated(true);
			$job->setToken('job_'.$i);
			$job->setEmail('job@example.com');
			
			$em->persist($job);
		}
		
		$em->flush();
	}
	
	public function getOrder() {
		return 3;
	}
}