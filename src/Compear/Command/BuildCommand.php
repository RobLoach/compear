<?php

/*
 * This file is part of Compear.
 *
 * (c) Rob Loach <robloach@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Compear\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Composer\Satis\Command\BuildCommand as BaseCommand;
use Composer\Json\JsonFile;
use Symfony\Component\Process\Process;
use Composer\Repository\FilesystemRepository;

/**
 * @author Rob Loach <robloach@gmail.com>
 */
class BuildCommand extends BaseCommand
{
    private $file = 'satis.json';
    private $output_dir = 'pear';
    private $pirum_path = NULL;

    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds a PEAR repository from the given Satis definition.')
            ->addArgument('file', InputArgument::OPTIONAL, 'The Satis definition to load', 'satis.json')
            ->addArgument('output-dir', InputArgument::OPTIONAL, 'Where the PEAR repository should be built', 'pear')
            ->addOption('no-html-output', null, InputOption::VALUE_NONE, 'Turn off HTML view')
            ->setHelp(<<<EOT
The <info>build</info> command builds a PEAR repository
from the given Satis definition.

<info>php compear init</info>

EOT
            )
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Build the Satis repository first.
        parent::execute($input, $output);

        // Get information about the Satis repository.
        $this->file = $input->getArgument('file');
        $this->output_dir = $input->getArgument('output-dir');
        $json = new JsonFile($this->file);
        $satis = $json->read();

        // Create the Pirum definition file.
        $this->satisToPirum($satis);

        // Build the PEAR repository using Pirum.
        $pirum_path = $this->getPirumPath();
        $output->write($this->runPirum('build'));

        $this->addReleases();
    }

    /**
     * Create a Pirum definition file from the given Satis definition.
     */
    protected function satisToPirum($satis) {
        $pirum['server']['name'] = parse_url($satis['homepage'],  PHP_URL_HOST);
        $pirum['server']['summary'] = $satis['name'];
        $pirum['server']['alias'] = strtolower($satis['name']);
        $pirum['server']['url'] = $satis['homepage'];
        $xml = $this->arrayToXml($pirum);
        $pirumfile = $this->output_dir.'/pirum.xml';
        file_put_contents($pirumfile, '<?xml version="1.0" encoding="UTF-8" ?>'.$xml);
    }

    /**
     * Translates an associative array to XML.
     *
     * @param array $array
     *     The associative array.
     */
    protected function arrayToXml($array) {
        $output = '';
        foreach($array as $tag => $val) {
            if (!is_array($val)) {
                $output .= PHP_EOL.'<'.$tag.'>'.htmlentities($val).'</'.$tag.'>';
            } else {
                $output .= PHP_EOL.'<'.$tag.'>'.$this->arrayToXml($val);
                $output .= PHP_EOL.'</'.$tag.'>';
            }
        }
       
        return $output;
    }

    /**
     * Retrieves the path to the Pirum executable.
     */
    protected function getPirumPath() {
        // Cache the Pirum path.
        if (!isset($this->pirum_path)) {
            // Pirum can exist in the vendor bin, or straight "pirum".
            $base = dirname(dirname(dirname(__DIR__)));
            $options = array(
              $base . '/vendor/bin/pirum',
              dirname(dirname($base)) . '/bin/pirum',
              'pirum',
            );
            foreach ($options as $file) {
                if (file_exists($file)) {
                    return $this->pirum_path = $file;
                }
            }
            throw new \RuntimeException('Could not find Pirum.');
        }
        return $this->pirum_path;
    }

    /**
     * Runs the given Pirum command with the provided argument.
     */
    protected function runPirum($command, $argument = '') {
        $pirum = $this->getPirumPath();
        $process = new Process($pirum.' '.$command.' '.$this->output_dir .' '.$argument);
        $process->run();
        return $process->getOutput();
    }

    /**
     * Add the releases to the PEAR repository.
     *
     * @param array $packages
     *     The packages to add to the PEAR repository.
     */
    protected function addReleases() {
        /*$file = new JsonFile($this->output_dir.'/packages.json');
        $repo = new FilesystemRepository($file);
        foreach ($repo->getPackages() as $package) {
            echo 'hi';
        }*/
    }
}
