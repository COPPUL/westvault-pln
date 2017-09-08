<?php

namespace AppBundle\Command\Shell;

use AppBundle\Entity\Institution;
use AppBundle\Entity\Whitelist;
use AppBundle\Services\Ping;
use Exception;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpKernel\Tests\Logger;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Ping all the institutions in the database and whitelist those that respond and
 * that are running a sufficiently recent version of OJS.
 */
class PingWhitelistCommand extends ContainerAwareCommand
{
    /**
     * @var Logger
     */
    protected $logger;

    /**
     * @var Ping
     */
    protected $ping;

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:ping-whitelist');
        $this->setDescription('Find institutions running a sufficiently new version of OJS and whitelist them.');
        $this->addArgument('minVersion', InputArgument::OPTIONAL, 'Minimum version required to whitelist.');
        $this->addOption(
            'dry-run',
            'd',
            InputOption::VALUE_NONE,
            'Do not update the whitelist - report only.'
        );
        $this->addOption(
            'all',
            'a',
            InputOption::VALUE_NONE,
            'Ping all institutions, including whitelisted/blacklisted.'
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
        $this->ping = $container->get('ping');
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
        $router = $this->getContainer()->get('router');
        $bwlist = $this->getContainer()->get('blackwhitelist');
        $ping = $this->getContainer()->get('ping');
        $ping->setLogger($this->logger);

        /*
         * @var Institution[]
         */
        $institutions = $em->getRepository('AppBundle:Institution')->findAll();
        $minVersion = $input->getArgument('minVersion');
        if (!$minVersion) {
            $minVersion = $this->getContainer()->getParameter('min_ojs_version');
        }
        $all = $input->getOption('all');

        $count = count($institutions);
        $i = 0;

        foreach ($institutions as $institution) {
            ++$i;
            $fmt = sprintf('%5d', $i);

            $url = $router->generate('institution_show', array('id' => $institution->getId()), UrlGeneratorInterface::ABSOLUTE_URL);
            $uuid = $institution->getUuid();
            if (!$all && $institution->getStatus() === 'ping-error') {
                $this->logger->notice("{$fmt}/{$count} - skipped (previous ping-error) - - {$institution->getUrl()}");
                continue;
            }

            if (!$all && $bwlist->isWhitelisted($uuid)) {
                $this->logger->notice("{$fmt}/{$count} - skipped (whitelisted) - - {$institution->getUrl()}");
                continue;
            }
            if (!$all && $bwlist->isBlacklisted($uuid)) {
                $this->logger->notice("{$fmt}/{$count} - skipped (blacklisted) - - {$institution->getUrl()}");
                continue;
            }

            try {
                $response = $ping->ping($institution);
            } catch (Exception $e) {
                $this->logger->error("Ping - HTTP ERROR: {$e->getMessage()} - {$institution->getUrl()} - {$url}");
                $institution->setStatus('ping-error');
                $em->flush($institution);
                continue;
            }
            if ($response->getHttpStatus() !== 200) {
                $this->logger->error("Ping - HTTP {$response->getHttpStatus()} - - {$institution->getUrl()} - {$url} - {$response->getError()}");
                $institution->setStatus('ping-error');
                $em->flush($institution);
                continue;
            }
            if (!$response->getOjsRelease()) {
                $this->logger->warning("Ping - HTTP {$response->getHttpStatus()} - no version number found - {$institution->getUrl()} - {$url}");
                $institution->setStatus('ping-error');
                $em->flush($institution);
                continue;
            }
            $this->logger->notice("Ping - {$response->getHttpStatus()} - {$response->getOjsRelease()} - {$institution->getUrl()} - {$url}");

            if (version_compare($response->getOjsRelease(), $minVersion, '<')) {
                continue;
            }
            if ($input->getOption('dry-run')) {
                continue;
            }
            if ($bwlist->isWhitelisted($uuid) || $bwlist->isBlacklisted($uuid)) {
                continue;
            }
            $whitelist = new Whitelist();
            $whitelist->setUuid($institution->getUuid());
            $whitelist->setComment("{$institution->getUrl()} added automatically by ping-whitelist command.");
            $em->persist($whitelist);
            $em->flush();
        }
    }
}
