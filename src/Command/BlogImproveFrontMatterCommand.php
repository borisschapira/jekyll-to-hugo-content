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
            ->addArgument('src', InputArgument::REQUIRED, 'The path to the root folder of contents')
            ->addArgument('dest', InputArgument::OPTIONAL, 'The path to the root folder of contents');
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $src = $input->getArgument('src');
        $destination = $input->getArgument('dest');


        $re = '/(?P<lang>fr|en)\/(?P<section>citoyen|papa|default)\/(?P<year>[0-9]+)\/\k<year>-(?P<month>[0-9]+)-(?P<day>[0-9]+)-(?P<title>.*)\/\k<year>-\k<month>-\k<day>-\k<title>.md/';

        $finder = new Finder();
        $finder->files()->in($src)->name('*.md');;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {

            // Dump the relative path to the file
            preg_match($re, $file->getRelativePathname(), $matches);

            //var_dump($matches);

            if (count($matches) > 10) {

                // Dump the relative path to the file, omitting the filename
                $output->writeln('Source file: ' . $file->getRealPath());

                $document = FrontMatter::parse(file_get_contents($file->getRealPath()));

                // $output->writeln($matches['section'] . ' ' . $matches['day'] .'/'.$matches['month'].'/'.$matches['year']. ' '. $matches['title']);

                $document['date'] = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'];
                $document['section'] = $matches['section'];
                $document['lang'] = $matches['lang'];
                $document['type'] = 'post';

                if ($destination == null) {
                    $output->writeln('Updating file.');
                    file_put_contents($file->getRealPath(), FrontMatter::dump($document));
                } else {
                    $destinationPathTemplate = $destination . '/content/' . $matches['section'] . '/' . $matches['year'] . '/' . $matches['month']. '/' . $matches['title']. '/';

                    $urlTemplate = ($matches['section']=='default'?'':('/' .$matches['section'])) . '/' . $matches['year'] . '/' . $matches['month']. '/' . $matches['title']. '/';

                    //$output->writeln($destinationPathTemplate);
                    $output->writeln('Writing file to: ' . $destinationPathTemplate . 'index.md');

                    if (!file_exists($destinationPathTemplate)) {
                        mkdir($destinationPathTemplate, 0777, true);
                    }

                    $dump = FrontMatter::dump($document);
                    $fixedDump = str_replace('{{ page.url }}', $urlTemplate, $dump);
                    file_put_contents($destinationPathTemplate . 'index.md', $fixedDump);

                    $insideFinder = new Finder();
                    $insideFinder->files()->in($file->getPath())->notName('*.md');

                    /** @var SplFileInfo $ressource */
                    foreach ($insideFinder as $ressource) {
                        $output->writeln('Additional ressource : ' . $ressource->getRealPath());
                        copy($ressource->getRealPath(), $destinationPathTemplate . $ressource->getRelativePathname());
                    }

                }
                $output->writeln('');
            }
        }
    }
}
