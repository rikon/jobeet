<?php
namespace Ibw\JobeetBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Output\NullOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Doctrine\Bundle\DoctrineBundle\Command\DropDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\HttpExceptionInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

class ApiControllerTest extends WebTestCase
{
	private $em;
	private $application;
	
	public function setUp()
	{
		static::$kernel = static::createKernel();
		static::$kernel->boot();
		$this->application = new Application(static::$kernel);
		
		//drop the database
		$command = new DropDatabaseDoctrineCommand();
		$this->application->add($command);
		$input = new ArrayInput(array(
			'command' => 'doctrine:database:drop',
			'--force' => true
		));
		$command->run($input, new NullOutput());
		
		//"no database selected" error
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
		
		//get entity manager
		$this->em = static::$kernel->getContainer()->get('doctrine')->getManager();
		
		//load fixtures;
		$client = static::createClient();
		$loader = new ContainerAwareLoader($client->getContainer());
		$loader->loadFromDirectory(static::$kernel->locateResource('@IbwJobeetBundle/DataFixtures/ORM'));
		$purger = new ORMPurger($this->em);
		$executor = new ORMExecutor($this->em, $purger);
		$executor->execute($loader->getFixtures());
	}
	
	
	public function testList()
	{
		$this->assertTrue(true);
		$client = static::createClient();
		$crawler = $client->request('GET', '/api/sensio-labs/jobs.xml');
		$this->assertEquals('Ibw\JobeetBundle\Controller\ApiController::listAction', $client->getRequest()->attributes->get('_controller'));
		$this->assertTrue($crawler->filter('description')->count() == 32);
		
		
		//for xml
		$crawler = $client->request('GET', '/api/sensio-labs87/jobs.xml');
		$this->assertTrue(404 == $client->getResponse()->getStatusCode());
		
		$crawler = $client->request('GET', '/api/symfony/jobs.xml');
		$this->assertTrue(404 == $client->getResponse()->getStatusCode());
		
		//for json
		$crawler = $client->request('GET', '/api/sensio-labs/jobs.json');
		$this->assertEquals('Ibw\JobeetBundle\Controller\ApiController::listAction', $client->getRequest()->attributes->get('_controller'));
		$this->assertRegExp('/"category"\:"Programming"/', $client->getResponse()->getContent());
		
		$crawler = $client->request('GET', '/api/sensio-labs87/jobs.json');
		$this->assertTrue(404 == $client->getResponse()->getStatusCode());
				
		//for yaml
		$crawler = $client->request('GET', '/api/sensio-labs/jobs.yaml');
		$this->assertEquals('Ibw\JobeetBundle\Controller\ApiController::listAction', $client->getRequest()->attributes->get('_controller'));
		$this->assertRegExp('/category\: Programming/', $client->getResponse()->getContent());
		
		$crawler = $client->request('GET', '/api/sensio-labs87/jobs.yaml');
		$this->assertTrue(404 == $client->getResponse()->getStatusCode());
		
	}
}