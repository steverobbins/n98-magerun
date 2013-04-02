<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class FlushCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new FlushCommand());
        $command = $this->getApplication()->find('cache:flush');

        $commandTester = new CommandTester($command);
        $commandTester->execute(array('command' => $command->getName()));

        $this->assertRegExp('/Cache cleared/', $commandTester->getDisplay());
    }
}