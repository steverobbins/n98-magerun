<?php

namespace N98\Magento\Command\Cache;

use Symfony\Component\Console\Tester\CommandTester;
use N98\Magento\Command\PHPUnit\TestCase;

class ViewCommandTest extends TestCase
{
    public function testExecute()
    {
        $application = $this->getApplication();
        $application->add(new ListCommand());
        $command = $this->getApplication()->find('cache:view');

        \Mage::app()->getCache()->save('TEST n98-magerun', 'n98-magerun-unittest');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array(
                'command' => $command->getName(),
                'id'      => 'n98-magerun-unittest'
            )
        );

        $this->assertRegExp('/TEST n98-magerun/', $commandTester->getDisplay());
    }
}