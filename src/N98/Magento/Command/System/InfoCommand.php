<?php

namespace N98\Magento\Command\System;

use N98\Magento\Command\AbstractMagentoCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class InfoCommand extends AbstractMagentoCommand
{
    /**
     * @var array
     */
    protected $infos;

    protected function configure()
    {
        $this
            ->setName('sys:info')
            ->setDescription('Prints infos about the current magento system.');
    }

    /**
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     * @return int|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->detectMagento($output, true);

        $this->writeSection($output, 'Magento System Information');

        $this->initMagento();
        $this->infos['Version'] = \Mage::getVersion();
        $this->infos['Edition'] = ($this->_magentoEnterprise ? 'Enterprise' : 'Community');

        $config = \Mage::app()->getConfig();
        $this->_addCacheInfos();

        $this->infos['Session'] = $config->getNode('global/session_save');

        $this->infos['Crypt Key'] = $config->getNode('global/crypt/key');
        $this->infos['Install Date'] = $config->getNode('global/install/date');

        foreach ($this->infos as $key => $value) {
            $output->writeln(str_pad($key, 25, ' ') . ': ' . $value);
        }
    }

    protected function _addCacheInfos()
    {
        $this->infos['Cache Backend'] = get_class(\Mage::app()->getCache()->getBackend());

        switch (get_class(\Mage::app()->getCache()->getBackend())) {
            case 'Zend_Cache_Backend_File':
                $cacheDir = \Mage::app()->getConfig()->getOptions()->getCacheDir();
                $this->infos['Cache Directory'] = $cacheDir;
                break;

            default:
        }
    }
}