<?php

/*
 * Copyright (C) 2015-2016 Michael Joyce <ubermichael@gmail.com>
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace AppBundle\Command\Shell;

use DateTime;
use Swift_Message;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;

/**
 * Send reminders about institutions that haven't contacted the PLN in a while.
 */
class HealthReminderCommand extends ContainerAwareCommand
{
    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Logger
     */
    protected $logger;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:health:reminder');
        $this->setDescription('Remind admins about silent institutions.');
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not update institution status'
        );
        parent::configure();
    }

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->templating = $container->get('templating');
        $this->logger = $container->get('monolog.logger.processing');
    }

    /**
     * Send the notifications.
     *
     * @param int       $days
     * @param User[]    $users
     * @param Institution[] $institutions
     */
    protected function sendReminders($days, $users, $institutions)
    {
        $notification = $this->templating->render('AppBundle:HealthCheck:reminder.txt.twig', array(
            'institutions' => $institutions,
            'days' => $days,
        ));
        $mailer = $this->getContainer()->get('mailer');
        foreach ($users as $user) {
            $message = Swift_Message::newInstance(
                'Automated reminder from the PKP PLN',
                $notification,
                'text/plain',
                'utf-8'
            );
            $message->setFrom('noreplies@pkp-pln.lib.sfu.ca');
            $message->setTo($user->getEmail(), $user->getFullname());
            $mailer->send($message);
        }
    }

    /**
     * Execute the runall command, which executes all the commands.
     *
     * @param InputInterface  $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $em = $this->getContainer()->get('doctrine')->getManager();
        $days = $this->getContainer()->getParameter('days_reminder');
        $institutions = $em->getRepository('AppBundle:Institution')->findOverdue($days);
        $count = count($institutions);
        $this->logger->notice("Found {$count} overdue institutions.");
        if (count($institutions) === 0) {
            return;
        }

        $users = $em->getRepository('AppUserBundle:User')->findUserToNotify();
        if (count($users) === 0) {
            $this->logger->error('No users to notify.');

            return;
        }
        $this->sendReminders($days, $users, $institutions);

        foreach ($institutions as $institution) {
            $institution->setNotified(new DateTime());
        }

        if (!$input->getOption('dry-run')) {
            $em->flush();
        }
    }
}
