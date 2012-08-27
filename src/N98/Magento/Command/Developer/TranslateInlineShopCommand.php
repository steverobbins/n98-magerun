<?php

namespace N98\Magento\Command\Developer;

use N98\Magento\Command\AbstractMagentoStoreConfigCommand;

class TranslateInlineShopCommand extends AbstractMagentoStoreConfigCommand
{
    /**
     * @var string
     */
    protected $configPath = 'dev/translate_inline/active';

    /**
     * @var string
     */
    protected $toggleComment = 'Inline Translation';

    /**
     * @var string
     */
    protected $commandName = 'dev:translate:shop';

    /**
     * @var string
     */
    protected $commandDescription = 'Toggle inline translation tool for shop';
}