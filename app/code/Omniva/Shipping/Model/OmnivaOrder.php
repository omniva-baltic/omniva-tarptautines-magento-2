<?php

namespace Omniva\Shipping\Model;

class OmnivaOrder extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{

    const CACHE_TAG = 'omniva_int_order';

    protected function _construct() {
        $this->_init('Omniva\Shipping\Model\ResourceModel\OmnivaOrder');
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
