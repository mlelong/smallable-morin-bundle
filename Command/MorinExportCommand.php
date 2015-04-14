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

class MorinExportCommand extends ContainerAwareCommand
{

    protected function configure()
    {
        $this
            ->setName('morin:export')
            ->setDescription('Morin Export Command')
            ->addArgument('arg', InputArgument::OPTIONAL, 'Interface name');;
    }

    protected function execute(InputInterface $oInput, OutputInterface $oOutput)
    {
        $arg = $oInput->getArgument('arg');
        if (in_array($arg, array('37e', '79b'))){
            $MorinService = $this->getContainer()->get('morin.'.$arg);
            $MorinService->process();
        }

    }
} 