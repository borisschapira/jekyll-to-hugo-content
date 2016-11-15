<?php

namespace ConsoleDI\Command;

use KzykHys\FrontMatter\FrontMatter;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;
use Symfony\Component\Finder\SplFileInfo;

class BlogImproveFrontMatterCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('blog:improve-front-matter')
            ->addArgument('path', InputArgument::REQUIRED, 'The path to the root folder of contents')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $path = $input->getArgument('path');

        $re = '/.*\/*(?P<section>citoyen|papa|default)\/(?P<year>[0-9]+)\/\k<year>-(?P<month>[0-9]+)-(?P<day>[0-9]+)-(?P<title>.*)\/\k<year>-\k<month>-\k<day>-\k<title>.md/';

        $finder = new Finder();
        $finder->files()->in($path)->name('*.md');;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {
            // Dump the absolute path
            //$output->writeln($file->getRealPath());

            // Dump the relative path to the file, omitting the filename
            $output->writeln($file->getRealPath());

            // Dump the relative path to the file
            preg_match_all($re, $file->getRelativePathname(), $matches);

            if($matches) {
                $document = FrontMatter::parse(file_get_contents($file->getRealPath()));

                /**$output->writeln($matches['section'][0] . ' ' . $matches['day'][0] .'/'.$matches['month'][0].'/'.$matches['year'][0]. ' '. $matches['title'][0]);**/

                $document['date']= $matches['year'][0] .'-'.$matches['month'][0].'-'.$matches['day'][0];
                $document['section']= $matches['section'][0];
                $document['type']= "post";

                file_put_contents($file->getRealPath(), FrontMatter::dump($document));
            }
            break;
        }
    }
}
