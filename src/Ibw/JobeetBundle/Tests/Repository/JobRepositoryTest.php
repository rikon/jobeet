<?php
namespace Ibw\JobeetBundle\Tests\Repository;




use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Doctrine\Bundle\DoctrineBundle\Command\DropDatabaseDoctrineCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand;
use Ibw\JobeetBundle\Entity\Job;


class JobRepositoryTest extends WebTestCase
{
	
	private $em;
	private $application;
	
	
	public function setUp() {
		static::$kernel = static::createKernel();
		static::$kernel->boot();
		
		
		$this->application = new Application(static::$kernel);
		
		//drop database
		$command = new DropDatabaseDoctrineCommand();
		$this->application->add($command);
		$input = new ArrayInput(array(
			'command' => 'doctrine:database:drop',
			'--force' => true
		));
		$command->run($input, new NullOutput());
		
		//削除したらいったん切断
		$connection = $this->application->getKernel()->getContainer()->get('doctrine')->getConnection();
		if($connection->isConnected()) {
			$connection->close();
		}
		
		//create database;
		$command = new CreateDatabaseDoctrineCommand();
		$this->application->add($command);
		$input = new ArrayInput(array(
			'command' => 'doctrine:database:create'
		));
		$command->run($input, new NullOutput());
		
		//create schema
		$command = new CreateSchemaDoctrineCommand();
		$this->application->add($command);
		$input = new ArrayInput(array(
			'command' => 'doctrine:schema:create'
		));
		$command->run($input, new NullOutput());
		
		//load fixtures
		$this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
		//TODO: this->application->getKernel()->getContainerとの違いは？
		
		$client = static::createClient();
		$loader = new \Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader($client->getContainer());
		$loader->loadFromDirectory(static::$kernel->locateResource('@IbwJobeetBundle/DataFixtures/ORM'));
		$purger = new \Doctrine\Common\DataFixtures\Purger\ORMPurger($this->em);
		$executor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor($this->em, $purger);
		$executor->execute($loader->getFixtures());
	}
	
	public function testCountActiveJobs()
	{
		$query = $this->em->createQuery('SELECT c FROM IbwJobeetBundle:Category c');
		$categories = $query->getResult();
		
		foreach($categories as $category) {
			$query = $this->em->createQuery('SELECT COUNT(j.id) FROM IbwJobeetBundle:Job j WHERE j.category = :category AND j.expires_at > :date');
			$query->setParameter('category', $category->getId());
			$query->setParameter('date', date('Y-m-d H:i:s', time()));
			$jobs_db = $query->getSingleScalarResult();
			$jobs_repo = $this->em->getRepository('IbwJobeetBundle:Job')->countActiveJobs($category->getId());
			
			$this->assertEquals($jobs_repo, $jobs_db);
		}
	}
	
	public function testGetActiveJobs()
	{
		$query = $this->em->createQuery('SELECT c FROM IbwJobeetBundle:Category c');
		$categories = $query->getResult();
		foreach($categories as $category) {
			$query = $this->em->createQuery('SELECT COUNT(j.id) FROM IbwJobeetBundle:Job j WHERE j.category = :category AND j.expires_at>:date');
			$query->setParameter('category', $category->getId());
			$query->setParameter('date', date('Y-m-d H:i:s', time()));
			$jobs_db = $query->getSingleScalarResult();
			
			$jobs_repo = $this->em->getRepository('IbwJobeetBundle:Job')->getActiveJobs($category->getId(), null, null);

			$this->assertEquals($jobs_db, count($jobs_repo));
		}
	}
	
	
	public function testGetActiveJob()
	{
		$query = $this->em->createQuery('SELECT j FROM IbwJobeetBundle:Job j WHERE j.expires_at > :date');
		$query->setParameter('date', date('Y-m-d H:i:s', time()));
		$query->setMaxResults(1);
		$job_db = $query->getSingleResult();
		$job_repo = $this->em->getRepository('IbwJobeetBundle:Job')->getActiveJob($job_db->getId());
		$this->assertNotNull($job_repo);
		
		$query = $this->em->createQuery('SELECT j FROM IbwJobeetBundle:Job j WHERE j.expires_at > :date');
		$query->setParameter('date', date('Y-m-d H:i:s', time()));
		$query->setMaxResults(1);
		$job_expired = $query->getSingleResult();
		$job_repo = $this->em->getRepository('IbwJobeetBundle:Job')->getActiveJob($job_expired->getId());
		$this->assertNotNull($job_repo);
	}
	
	
	
	public function testGetForLuceneQuery()
	{
		$em = static::$kernel->getContainer()->get('doctrine')->getManager();
		
		$job = new Job();
		$job->setType('part-time');
		$job->setCompany('Sensio');
		$job->setPosition('FOO6');
		$job->setLocation('Paris');
		$job->setDescription('WebDevelopment');
		$job->setHowToApply('Send resumee');
		$job->setEmail('jobeet@example.com');
		$job->setUrl('http://sensio-labs.com');
		$job->setIsActivated(false);
		$em->persist($job);
		$em->flush();
		
		$jobs = $em->getRepository('IbwJobeetBundle:Job')->getForLuceneQuery('FOO6');
		$this->assertEquals(count($jobs), 0);
		
		
		
		$job = new Job();
		$job->setType('part-time');
		$job->setCompany('Sensio');
		$job->setPosition('FOO7');
		$job->setLocation('Paris');
		$job->setDescription('WebDevelopment');
		$job->setHowToApply('Send resumee');
		$job->setEmail('jobeet@example.com');
		$job->setUrl('http://sensio-labs.com');
		$job->setIsActivated(true);
		
		$em->persist($job);
		$em->flush();
				
		$jobs = $em->getRepository('IbwJobeetBundle:Job')->getForLuceneQuery('position:FOO7');
		$this->assertEquals(count($jobs), 1);
		foreach ($jobs as $job_rep) {
			$this->assertEquals($job_rep->getId(), $job->getId());
		}		
		$em->remove($job);
		$em->flush();
		
		
		$jobs = $em->getRepository('IbwJobeetBundle:Job')->getForLuceneQuery('position:FOO7');
		$this->assertEquals(count($jobs), 0);		
		
	}
	
	
	
	protected function tearDown()
	{
		parent::tearDown();
		//$this->em->close();
	}
	
	
}