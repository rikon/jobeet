<?php
namespace Ibw\JobeetBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Ibw\JobeetBundle\Entity\User;


class JobeetUsersCommand extends ContainerAwareCommand
{
	public function configure()
	{
		$this->setName('ibw:jobeet:users')
			->setDescription('Add Jobeet users')
			->addArgument('username', InputArgument::REQUIRED, 'The username')
			->addArgument('password', InputArgument::REQUIRED, 'The password');
	}
	
	
	public function execute(InputInterface $input, OutputInterface $output) {
		
		$username = $input->getArgument('username');
		$password = $input->getArgument('password');
		
		$em = $this->getContainer()->get('doctrine')->getManager();
		
		$user = new User();
		$user->setUsername($username);
		
		//encode password
		$factory = $this->getContainer()->get('security.encoder_factory');
		$encoder = $factory->getEncoder($user);
		$encodePassword = $encoder->encodePassword($password, $user->getSalt());
		$user->setPassword($encodePassword);
		$em->persist($user);
		$em->flush(9);
		
		$output->writeln(sprintf('Added %s user with password %s', $username, $password));

	}
}