<?php

namespace ConsoleDI\Command;

use KzykHys\FrontMatter\Document;
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


        $re = '/(?P<lang>fr|en)\/(?P<section>citoyen|papa|web)\/(?P<year>[0-9]+)\/\k<year>-(?P<month>[0-9]+)-(?P<day>[0-9]+)-(?P<title>.*)\/\k<year>-\k<month>-\k<day>-\k<title>.md/';

        $finder = new Finder();
        $finder->files()->in($src)->name('*.md');;

        /** @var SplFileInfo $file */
        foreach ($finder as $file) {

            // Dump the relative path to the file
            preg_match($re, $file->getRelativePathname(), $matches);

            //var_dump($matches);

            if (count($matches) > 10) {

                // Dump the relative path to the file, omitting the filename
                $output->writeln('Source file: ' . $file->getRealPath() . ' (' . $file->getBasename('.md') . ')');

                /** @var Document $document */
                $document = FrontMatter::parse(file_get_contents($file->getRealPath()));

                // $output->writeln($matches['section'] . ' ' . $matches['day'] .'/'.$matches['month'].'/'.$matches['year']. ' '. $matches['title']);

                $document['date'] = $matches['year'] . '-' . $matches['month'] . '-' . $matches['day'];
                $document['publishDate'] = $document['date'];
                $document['lang'] = $matches['lang'];
                $document['type'] = 'post';
                if ( $document['lang'] == 'fr' ) {
                    $document['locale'] = 'fr_FR';
                } else if ( $document['lang'] == 'en' ) {
                    $document['locale'] = 'en_US';
                }

                if ($destination != null) {
                    $document['slug'] = substr($file->getBasename('.md'), 11);
                    $document['section'] = $matches['section'];
                    $document[$matches['year']] = [$matches['month']];
                } else {
                    $document->offsetUnset('section');

                    if (!isset($document['categories'])) {
                        $document['categories'] = array();
                    }
                    $categories = $document['categories'];
                    $categories[] = $matches['section'];
                    $document['categories'] = array_unique($categories);
                }

                if ($destination == null) {
                    $output->writeln('Updating file.');
                    file_put_contents($file->getRealPath(), FrontMatter::dump($document));
                } else {
                    $destinationPathTemplate = $destination . '/content/' . $matches['section'] . '/' . $matches['year'] . '/' . $matches['month'] . '/' . substr($file->getBasename('.md'), 11) . '/';
                    $filesDestinationPathTemplate = $destination . '/static/files/' . $matches['year'] . '/' . $matches['month'] . '/' . substr($file->getBasename('.md'), 11) . '/';

                    $urlTemplate = ($matches['section'] == 'web' ? '' : ('/' . $matches['section'])) . '/' . $matches['year'] . '/' . $matches['month'] . '/' . substr($file->getBasename('.md'), 11) . '/';

                    $filename = isset($document["i18n-key"]) ? $document["i18n-key"] : 'index';
                    $output->writeln('Writing file to: ' . $destinationPathTemplate . $filename . '.' . $matches['lang'] . '.md');

                    if (!file_exists($destinationPathTemplate)) {
                        mkdir($destinationPathTemplate, 0777, true);
                    }

                    $dump = FrontMatter::dump($document);
                    $dump = str_replace('{{ page.url }}', '{{<fileFolder>}}', $dump);
                    $dump = str_replace('<!-- more -->', '<!--more-->', $dump);
                    file_put_contents($destinationPathTemplate . $filename . '.' . $matches['lang'] . '.md', $dump);

                    $insideFinder = new Finder();
                    $insideFinder->files()->in($file->getPath())->notName('*.md');

                    if (count($insideFinder)>0) {

                        if (!file_exists($filesDestinationPathTemplate)) {
                            mkdir($filesDestinationPathTemplate, 0777, true);
                        }

                        /** @var SplFileInfo $ressource */
                        foreach ($insideFinder as $ressource) {
                            $output->writeln('Additional ressource : ' . $ressource->getRealPath());
                            copy($ressource->getRealPath(), $filesDestinationPathTemplate . $ressource->getRelativePathname());
                            copy($ressource->getRealPath(), $destinationPathTemplate . $ressource->getRelativePathname());
                        }
                    }

                }
                $output->writeln('');
            }
        }
    }
}
