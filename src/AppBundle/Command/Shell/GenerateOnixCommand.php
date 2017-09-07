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

use AppBundle\Entity\Institution;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Collections\Collection;
use Monolog\Logger;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Bundle\TwigBundle\TwigEngine;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Generate an ONIX-PH feed for all the deposits in the PLN.
 *
 * @see http://www.editeur.org/127/ONIX-PH/
 */
class GenerateOnixCommand extends ContainerAwareCommand
{
    /**
     * @var TwigEngine
     */
    private $templating;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Registry
     */
    protected $em;

    /**
     * Set the service container, and initialize the command.
     *
     * @param ContainerInterface $container
     */
    public function setContainer(ContainerInterface $container = null)
    {
        parent::setContainer($container);
        $this->templating = $container->get('templating');
        $this->logger = $container->get('logger');
        $this->em = $container->get('doctrine')->getManager();
    }

    /**
     * {@inheritdoc}
     */
    public function configure()
    {
        $this->setName('pln:onix');
        $this->setDescription('Generate ONIX-PH feed.');
        $this->addArgument('file', InputArgument::IS_ARRAY, 'File(s) to write the feed to.');
    }

    /**
     * Get the institutions to process.
     *
     * @return Collection|Institution[]
     */
    protected function getInstitutions()
    {
        $institutions = $this->em->getRepository('AppBundle:Institution')->findAll();

        return $institutions;
    }

    /**
     * Generate a CSV file at $filePath.
     *
     * @param type $filePath
     */
    protected function generateCsv($filePath)
    {
        $handle = fopen($filePath, 'w');
        $institutions = $this->getInstitutions();
        fputcsv($handle, array('Generated', date('Y-m-d')));
        fputcsv($handle, array(
            'ISSN',
            'Title',
            'Publisher',
            'Url',
            'Vol',
            'No',
            'Published',
            'Deposited',
        ));
        foreach ($institutions as $institution) {
            $deposits = $institution->getSentDeposits();
            if ($deposits->count() === 0) {
                continue;
            }
            foreach ($deposits as $deposit) {
                if ($deposit->getDepositDate() === null) {
                    continue;
                }
                fputcsv($handle, array(
                    $institution->getIssn(),
                    $institution->getTitle(),
                    $institution->getPublisherName(),
                    $institution->getUrl(),
                    $deposit->getVolume(),
                    $deposit->getIssue(),
                    $deposit->getPubDate()->format('Y-m-d'),
                    $deposit->getDepositDate()->format('Y-m-d'),
              ));
            }
        }
    }

    /**
     * Generate an XML file at $filePath.
     *
     * @param string $filePath
     */
    protected function generateXml($filePath)
    {
        $institutions = $this->getInstitutions();
        $onix = $this->templating->render('AppBundle:Onix:onix.xml.twig', array(
            'institutions' => $institutions,
        ));
        $fh = fopen($filePath, 'w');
        fwrite($fh, $onix);
        fclose($fh);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        ini_set('memory_limit', '512M');
        $files = $input->getArgument('file');
        if (!$files || count($files) === 0) {
            $fp = $this->getContainer()->get('filepaths');
            $files[] = $fp->getOnixPath('xml');
            $files[] = $fp->getOnixPath('csv');
        }

        foreach ($files as $file) {
            $this->logger->info("Writing {$file}");
            $ext = pathinfo($file, PATHINFO_EXTENSION);
            switch ($ext) {
                case 'xml':
                    $this->generateXml($file);
                    break;
                case 'csv':
                    $this->generateCsv($file);
                    break;
                default:
                    $this->logger->error("Cannot generate {$ext} ONIX format.");
                    break;
            }
        }
    }
}
