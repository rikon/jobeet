<?php
namespace Ibw\JobeetBundle\Tests\Entity;


use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Doctrine\Bundle\DoctrineBundle\Command\DropDatabaseDoctrineCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand;
use Ibw\JobeetBundle\IbwJobeetBundle;
use Ibw\JobeetBundle\Utils\Jobeet;
class JobTest extends WebTestCase
{
	private $em;
	private $application;
	
	
	public function setUp()
	{
		static::$kernel = static::createKernel();
		static::$kernel->boob();
		
		$this->application = new Application(static::$kernel);
		
		//drop database;
		$command = new DropDatabaseDoctrineCommand();
		$this->application->add($command);
		$input = new ArrayInput(array(
			'command'	=> 'doctrine:database:drop',
			'--force'	=> true
		));
		$command->run($input, new NullOutput());
		
		//削除したらいったん切断
		$connection = $this->application->getKernel()->getContainer()->get('doctrine')->getConnection();
		if($connection->isConnected()) {
			$connection->close();
		}
		
		
		//create the database
		$command = new CreateDatabaseDoctrineCommand();
		$this->application->add($command);
		$input = new ArrayInput(array(
			'command'	=> 'doctrine:database:create'
		));
		$command->run($input, new NullOutput());
		
		//create schema
		$command = new CreateSchemaDoctrineCommand();
		$this->application->add($command);
		$input = new ArrayInput(array(
			'command'	=> 'dodctrine:schema:create'
		));
		$command->run($input, new NullOutput());
		
		
		//get entity manager
		$this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
		
		//load fixtures;
		$client = static::createClient();
		$loader = new \Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader($client->getContainer());
		$loader->loadFromDirectory(static::$kernel->locateResource('@IbwJobeetBundle/DataFixtures/ORM'));
		$purger = new \Doctrine\Common\DataFixtures\Purger\ORMPurger($this->em);
		$exeutor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor($this->em, $purger);
		$exeutor->execute($loader->getFixtures());
		
	}
	
	
	public function testGetCompanySlug()
	{
		$job = $this->em-createQuery('SELECT j FROM IbwJobeetBundle:Job j')
				->setMaxResults(1)
				->getSingleResult();
		
		$this->assertEquals($job->getCompanySlug(), Jobeet::slugify($job->getCompany()));
	}
	
	public function testGetPositionSlug()
	{
		$job = $this->em->createQuery('SELECT j FROM IbwJobeetBundle:Job j')
				->setMaxResults(1)
				->getSingleResult();
		
		$this->assertEquals($job->getPositionSlug(), Jobeet::slugify($job->getPosition()));
	}
	
	public function testGetLocationSlug()
	{
		$job = $this->em->createQuery('SELECT j FROM IbwJobeetBundle:Job j ')
		->setMaxResults(1)
		->getSingleResult();
	
		$this->assertEquals($job->getLocationSlug(), Jobeet::slugify($job->getLocation()));
	}

	public function testSetExpiresAtValue()
	{
		$job = new Job();
		$job->setExpiresAtValue();
		
		$this->assertEquals(time() + 86400 * 30, $job->getExpiresAt()->format('U'));
	}
	
	
	protected function tearDown()
	{
		parent::tearDown();
		$this->em->close();
	}
}