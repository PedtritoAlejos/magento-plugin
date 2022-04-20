<?php

namespace DUna\Payments\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Store\Model\ScopeInterface;

class Data extends AbstractHelper
{
    /**
     * constant
     */
    const XML_PATH_DUNA = 'duna/';
    const MODE_PRODUCTION = 2;
    const MODE_STAGING = 1;

    /**
     * @param $field
     * @param $storeId
     * @return mixed
     */
    public function getConfigValue($field, $storeId = null)
    {
        return $this->scopeConfig->getValue(
            $field, ScopeInterface::SCOPE_STORE, $storeId
        );
    }

    /**
     * @param $code
     * @param $storeId
     * @return mixed
     */
    public function getGeneralConfig($code, $storeId = null)
    {
        return $this->getConfigValue(self::XML_PATH_DUNA .'config/'. $code, $storeId);
    }

    /**
     * @return string
     */
    public function getEnv(): string
    {
        $mode = $this->getGeneralConfig('mode');
        if ($mode == self::MODE_PRODUCTION) {
            $env = 'production';
        }
        if ($mode == self::MODE_STAGING) {
            $env = 'staging';
        }
        return $env;
    }
}
