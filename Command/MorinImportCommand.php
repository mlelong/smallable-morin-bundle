<?php
/**
 * Created by PhpStorm.
 * User: c.chiha
 * Date: 19/03/15
 * Time: 12:42
 */

namespace Smallable\Logistics\MorinBundle\Command;


use Symfony\Component\Console\Input\InputArgument;
use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Input\InputInterface;

class MorinImportCommand extends ContainerAwareCommand {

    protected function configure()
    {
        $this
            ->setName('morin:import')
            ->setDescription('Morin Import Command')
            ->addArgument('arg', InputArgument::OPTIONAL, 'Interface Name');
        ;
    }

    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $arg = $oInput->getArgument('arg');

        switch($arg)
        {
            case '37m':
                $Morin37E = $this->getContainer()->get('morin.37m');
                $Morin37E->process($oOutput);
                break;
        }


    }
} 