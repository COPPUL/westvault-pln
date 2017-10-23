<?php

namespace AppUserBundle\Command;

use AppBundle\Utility\AbstractCommandTestCase;

class CreateUserCommandTest extends AbstractCommandTestCase {

	public function getCommand() {
		return new CreateUserCommand();
	}

	public function getCommandName() {
		return 'fos:user:create';
	}

	public function testCreateUser() {
		$this->commandTester->execute(array(
			'command' => $this->getCommandName(),
			'email' => 'bob@example.com',
			'password' => 'secret',
			'fullname' => 'Bob Terwilliger',
			'institution' => 'Springfield State Penn',
		));
		
		$this->em->clear();
		$user = $this->em->getRepository('AppUserBundle:User')->findOneBy(array(
			'email' => 'bob@example.com',
		));
		$this->assertInstanceOf('AppUserBundle\Entity\User', $user);
		$this->assertEquals('bob@example.com', $user->getUsername());
		$this->assertEquals('bob@example.com', $user->getEmail());
		$this->assertEquals('Bob Terwilliger', $user->getFullname());
		$this->assertEquals('Springfield State Penn', $user->getInstitution());
	}
}