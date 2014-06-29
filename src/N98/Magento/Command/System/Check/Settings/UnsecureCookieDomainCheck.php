<?php

namespace N98\Magento\Command\System\Check\Settings;

use N98\Magento\Command\System\Check\StoreCheck;
use N98\Magento\Command\System\Check\ResultCollection;

class UnsecureCookieDomainCheck implements StoreCheck
{
    /**
     * @param ResultCollection $results
     * @param \Mage_Core_Model_Store $store
     */
    public function check(ResultCollection $results, \Mage_Core_Model_Store $store)
    {
        $result = $results->createResult();
        $errorMessage = 'Cookie Domain and Unsecure BaseURL (http) does not match';

        $cookieDomain = \Mage::getStoreConfig('web/cookie/cookie_domain', $store);

        if (!empty($cookieDomain)) {
            $result->setIsValid(strpos(parse_url($cookieDomain, PHP_URL_HOST), $cookieDomain));
        }

        if ($result->isValid()) {
            $result->setMessage('<info>Cookie Domain (unsecure) of Store: <comment>' . $store->getCode() . '</comment> OK');
        } else {
            $result->setMessage('<error>Cookie Domain (unsecure) <comment>Store: ' . $store->getCode() . '</comment> ' . $errorMessage . '</error>');
        }
    }
}