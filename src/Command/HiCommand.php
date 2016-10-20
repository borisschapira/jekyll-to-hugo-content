<?php

namespace ConsoleDI\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class HiCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('say:hi')
            ->addArgument('name', InputArgument::REQUIRED, 'The person you want to say hi!')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $name = $input->getArgument('name');
        $output->writeln(sprintf('Hi %s!', $name));
    }
}
