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

namespace AppBundle\Command\Processing;

use AppBundle\Entity\AuContainer;
use AppBundle\Entity\Deposit;
use BagIt;

/**
 * Take a processed bag and reserialize it.
 */
class OrganizeCommand extends AbstractProcessingCmd
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('pln:organize');
        $this->setDescription('Organize the deposits into archival units.');
        parent::configure();
    }

    /**
     * {@inheritdoc}
     */
    protected function processDeposit(Deposit $deposit)
    {
        $auContainer = $this->em->getRepository('AppBundle:AuContainer')->getOpenContainer();
        if ($auContainer === null) {
            $auContainer = new AuContainer();
            $this->em->persist($auContainer);
        }
        $deposit->setAuContainer($auContainer);
        $auContainer->addDeposit($deposit);
        if ($auContainer->getSize() > $this->container->getParameter('pln_maxAuSize')) {
            $auContainer->setOpen(false);
            $this->em->flush($auContainer);
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function failureLogMessage()
    {
        return 'Organize failed.';
    }

    /**
     * {@inheritdoc}
     */
    public function nextState()
    {
        return 'organized';
    }

    /**
     * {@inheritdoc}
     */
    public function processingState()
    {
        return 'virus-checked';
    }

    /**
     * {@inheritdoc}
     */
    public function successLogMessage()
    {
        return 'Organize succeeded.';
    }

    /**
     * {@inheritdoc}
     */
    public function errorState()
    {
        return 'organize-error';
    }
}
