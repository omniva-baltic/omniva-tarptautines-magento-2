<?php

namespace Omniva\Shipping\Model;

class Terminal extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{

    const CACHE_TAG = 'omniva_int_terminal';

    protected function _construct() {
        $this->_init('Omniva\Shipping\Model\ResourceModel\Terminal');
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
