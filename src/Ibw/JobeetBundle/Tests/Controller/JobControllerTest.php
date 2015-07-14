<?php

namespace Ibw\JobeetBundle\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Doctrine\Bundle\DoctrineBundle\Command\DropDatabaseDoctrineCommand;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\NullOutput;
use Doctrine\Bundle\DoctrineBundle\Command\CreateDatabaseDoctrineCommand;
use Doctrine\Bundle\DoctrineBundle\Command\Proxy\CreateSchemaDoctrineCommand;

class JobControllerTest extends WebTestCase
{
    private $em;
    private $application;

    public function setUp()
    {
        static::$kernel = static::createKernel();
        static::$kernel->boot();

        $this->application = new Application(static::$kernel);

        // drop the database
        $command = new DropDatabaseDoctrineCommand();
        $this->application->add($command);
        $input = new ArrayInput(array(
            'command' => 'doctrine:database:drop',
            '--force' => true
        ));
        $command->run($input, new NullOutput());

        // we have to close the connection after dropping the database so we don't get "No database selected" error
        $connection = $this->application->getKernel()->getContainer()->get('doctrine')->getConnection();
        if ($connection->isConnected()) {
            $connection->close();
        }

        // create the database
        $command = new CreateDatabaseDoctrineCommand();
        $this->application->add($command);
        $input = new ArrayInput(array(
            'command' => 'doctrine:database:create',
        ));
        $command->run($input, new NullOutput());

        // create schema
        $command = new CreateSchemaDoctrineCommand();
        $this->application->add($command);
        $input = new ArrayInput(array(
            'command' => 'doctrine:schema:create',
        ));
        $command->run($input, new NullOutput());

        // get the Entity Manager
        $this->em = static::$kernel->getContainer()
            ->get('doctrine')
            ->getManager();

        // load fixtures
        $client = static::createClient();
        $loader = new \Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader($client->getContainer());
        $loader->loadFromDirectory(static::$kernel->locateResource('@IbwJobeetBundle/DataFixtures/ORM'));
        $purger = new \Doctrine\Common\DataFixtures\Purger\ORMPurger($this->em);
        $executor = new \Doctrine\Common\DataFixtures\Executor\ORMExecutor($this->em, $purger);
        $executor->execute($loader->getFixtures());
    }

    public function getMostRecentProgrammingJob()
    {
        $kernel = static::createKernel();
        $kernel->boot();
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $query = $em->createQuery('SELECT j from IbwJobeetBundle:Job j LEFT JOIN j.category c WHERE c.slug = :slug AND j.expires_at > :date ORDER BY j.created_at DESC');
        $query->setParameter('slug', 'programming');
        $query->setParameter('date', date('Y-m-d H:i:s', time()));
        $query->setMaxResults(1);

        return $query->getSingleResult();
    }

    public function getExpiredJob()
    {
        $kernel = static::createKernel();
        $kernel->boot();
        $em = $kernel->getContainer()->get('doctrine.orm.entity_manager');

        $query = $em->createQuery('SELECT j from IbwJobeetBundle:Job j WHERE j.expires_at < :date');
        $query->setParameter('date', date('Y-m-d H:i:s', time()));
        $query->setMaxResults(1);

        return $query->getSingleResult();
    }

    public function testIndex()
    {
        // get the custom parameters from app config.yml
        $kernel = static::createKernel();
        $kernel->boot();
        $max_jobs_on_homepage = $kernel->getContainer()->getParameter('max_jobs_on_homepage');

        $client = static::createClient();

        $crawler = $client->request('GET', '/');
        $this->assertEquals('Ibw\JobeetBundle\Controller\JobController::indexAction', $client->getRequest()->attributes->get('_controller'));

        // expired jobs are not listed
        $this->assertTrue($crawler->filter('.jobs td.position:contains("Expired")')->count() == 0);

        // only $max_jobs_on_homepage jobs are listed for a category
        $this->assertTrue($crawler->filter('.category_programming tr')->count()<= $max_jobs_on_homepage);
        $this->assertTrue($crawler->filter('.category_design .more_jobs')->count() == 0);
        $this->assertTrue($crawler->filter('.category_programming .more_jobs')->count() == 1);

        // jobs are sorted by date
        $this->assertTrue($crawler->filter('.category_programming tr')->first()->filter(sprintf('a[href*="/%d/"]', $this->getMostRecentProgrammingJob()->getId()))->count() == 1);

        // each job on the homepage is clickable and give detailed information
        $job = $this->getMostRecentProgrammingJob();
        $link = $crawler->selectLink('Web Developer')->first()->link();
        $crawler = $client->click($link);
        $this->assertEquals('Ibw\JobeetBundle\Controller\JobController::showAction', $client->getRequest()->attributes->get('_controller'));
        $this->assertEquals($job->getCompanySlug(), $client->getRequest()->attributes->get('company'));
        $this->assertEquals($job->getLocationSlug(), $client->getRequest()->attributes->get('location'));
        $this->assertEquals($job->getPositionSlug(), $client->getRequest()->attributes->get('position'));
        $this->assertEquals($job->getId(), $client->getRequest()->attributes->get('id'));

        // a non-existent job forwards the user to a 404
        $crawler = $client->request('GET', '/job/foo-inc/milano-italy/0/painter');
        $this->assertTrue(404 === $client->getResponse()->getStatusCode());

        // an expired job page forwards the user to a 404
        $crawler = $client->request('GET', sprintf('/job/sensio-labs/paris-france/%d/web-developer', $this->getExpiredJob()->getId()));
        $this->assertTrue(404 === $client->getResponse()->getStatusCode());
    }
    
    public function testJobForm()
    {
    	$client = static::createClient();
    	$crawler = $client->request('GET', '/job/new');
    	$this->assertEquals('Ibw\JobeetBundle\Controller\JobController::newAction', $client->getRequest()->attributes->get('_controller'));
    	
    	//preview you job's test
    	$form = $crawler->selectButton('Preview your job')->form(array(
    			'job[company]'	=> 'Sensio Labs',
    			'job[url]'		=> 'http://www.sensio.com/',
    			'job[file]'		=> __DIR__.'/../../../../../web/bundles/ibwjobeet/images/sensio-labs.gif',
    			'job[position]'	=> 'Developer',
    			'job[location]'	=> 'Atlanta, USA',
    			'job[description]'	=> 'You will work with symfony to develop websites for our customers.',
    			'job[how_to_apply]'	=> 'Send me an email',
    			'job[email]'		=> 'for.a.job@example.com',
    			'job[is_public]'	=> false,
    	));
    	$client->submit($form);
    	$this->assertEquals('Ibw\JobeetBundle\Controller\JobController::createAction', $client->getRequest()->attributes->get('_controller'));
    	
    	
    	//TODO:??????
    	//$client->followRedirect();
    	//$this->assertEquals('Ibw\JobeetBundle\Controller\JobController::previewAction', $client->getRequest()->attributes->get('_controller'));
    	
    	
    	//
    	$kernel = static::createKernel();
    	$kernel->boot();
    	$em = $kernel->getContainer()->get('doctrine.orm.entity_manager');
    	
    	$query = $em->createQuery('SELECT count(j.id) FROM IbwJobeetBundle:Job j WHERE j.location=:location AND j.is_public=0');
    	$query->setParameter('location', 'Paris, France');
    	$this->assertTrue(0 < $query->getSingleScalarResult());
    }
}