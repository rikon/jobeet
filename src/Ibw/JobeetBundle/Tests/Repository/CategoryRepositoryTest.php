<?php
namespace Ibw\JobeetBundle\Tests\Repository;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Doctrine\Bundle\DoctrineBundle\Command\DropDatabaseDoctrineCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand;
use Symfony\Component\Form\Extension\Core\ChoiceList\ObjectChoiceList;


class CategoryRepositoryTest extends WebTestCase
{
	private $em;
	private $application;
	
	
	public function setUp()
	{
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
		
		//一旦切断
		$connection = $this->application->getKernel()->getContainer()->get('doctrine')->getConnection();
		if($connection->isConnected()) {
			$connection->close();
		}
		
		//create database
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
		
		// get the Entity Manager
		$this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
		
		//load fixtures
		$client = static::createClient();
		$loader = new \Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader($client->getContainer());
		$loader->loadFromDirectory(static::$kernel->locateResource('@IbwJobeetBundle/DataFixtures/ORM'));
		$purger = new \Doctrine\Common\DataFixtures\Purger\ORMPurger($this->em);
		$executor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor($this->em, $purger);
		$executor->execute($loader->getFixtures());
	}
	
	public function testGetWithJobs()
	{
		$query = $this->em->createQuery('SELECT c FROM IbwJobeetBundle:Category c LEFT JOIN c.jobs j WHERE j.expires_at > :date');
		$query->setParameter('date', date('Y-m-d H:i:s' ,time()));
		$categories_db = $query->getResult();
		
		$categories_repo = $this->em->getRepository('IbwJobeetBundle:Category')->getWithJobs();
		$this->assertEquals(count($categories_db), count($categories_repo));
	}
	
	
	protected function tearDown()
	{
		parent::tearDown();
		//$this->em->close();
	}
}