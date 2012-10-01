<?php

/*
 * This file is part of Compear.
 *
 * (c) Rob Loach <robloach@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Compear\Console;

use Composer\Satis\Console\Application as BaseApplication;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Compear\Compear;
use Compear\Command;

/**
 * @author Rob Loach <robloach@gmail.com>
 */
class Application extends BaseApplication
{
    /**
     * {@inheritDoc}
     */
    public function __construct()
    {
        parent::__construct();
        $this->setName(Compear::NAME);
        $this->setVersion(Compear::VERSION);
    }

    /**
     * Initializes all the Compear commands
     */
    protected function registerCommands()
    {
        $this->add(new Command\BuildCommand());
    }
}
