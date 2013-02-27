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
    private $satis_dir = 'satis';
    private $paths = array();

    protected function configure()
    {
        $this
            ->setName('build')
            ->setDescription('Builds a PEAR repository from the given Satis definition.')
            ->addArgument('file', InputArgument::OPTIONAL, 'The Satis definition to load', 'satis.json')
            ->addArgument('output-dir', InputArgument::OPTIONAL, 'Where the PEAR repository should be built', 'pear')
            ->addArgument('satis-dir', InputArgument::OPTIONAL, 'Where the temporary Satis repository should be built', 'satis')
            ->addOption('no-html-output', null, InputOption::VALUE_NONE, 'Turn off HTML view')
            ->setHelp('The <info>build</info> command builds a PEAR repository from the given Satis definition.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Build the Satis repository first.
        parent::execute($input, $output);

        // Build the Satis repository.
        $output->writeln('<info>Building Satis repository.</info>');
        $this->satis_dir = $input->getArgument('satis-dir');
        $output->write($this->runBin('satis', 'build', array('satis.json', $this->satis_dir)));

        // Get information about the Satis repository.
        $this->output_dir = $input->getArgument('output-dir');
        $output->writeln('<info>Reading Satis information.</info>');
        $this->file = $input->getArgument('file');
        $json = new JsonFile($this->file);
        $satis = $json->read();

        // Create the Pirum definition file.
        $output->writeln('<info>Creating Pirum definition file.</info>');
        $this->satisToPirum($satis);

        // Build the PEAR repository using Pirum.
        $output->writeln('<info>Building PEAR repository.</info>');
        $output->write($this->runBin('pirum', 'build', array($this->output_dir)));

        $this->addReleases($output);
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
     * Retrieves the path to an executable.
     */
    protected function getBinPath($bin) {
        // Cache the path.
        if (!isset($this->paths[$bin])) {
            // Pirum can exist in the vendor bin, or straight "pirum".
            $base = dirname(dirname(dirname(__DIR__)));
            $options = array(
              $base . '/vendor/bin/' . $bin,
              dirname(dirname($base)) . '/bin/' . $bin,
              $bin,
            );
            foreach ($options as $file) {
                if (file_exists($file)) {
                    return $this->bin[$bin] = $file;
                }
            }
            throw new \RuntimeException('Could not find ' . $bin . '.');
        }
        return $this->bin[$bin];
    }

    /**
     * Runs the given Pirum command with the provided argument.
     */
    protected function runBin($bin, $command, $arguments = array()) {
        $path = $this->getBinPath($bin);
        $process = new Process($path . ' ' . $command . ' ' . implode(' ', $arguments));
        $process->run();
        return $process->getOutput();
    }

    /**
     * Add the releases to the PEAR repository.
     *
     * @param array $packages
     *     The packages to add to the PEAR repository.
     */
    protected function addReleases(OutputInterface $output) {
        $file = new JsonFile($this->satis_dir.'/packages.json');
        $data = $file->read();
        foreach ($data['packages'] as $name => $package) {
            $output->writeln('Processing package: ' . $name);
            foreach ($package as $version => $info) {
                $output->writeln('Processing version: ' . $version);
            }
        }
    }
}
