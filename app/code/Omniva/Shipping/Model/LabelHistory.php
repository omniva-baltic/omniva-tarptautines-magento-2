<?php

namespace Omniva\Shipping\Model;

class LabelHistory extends \Magento\Framework\Model\AbstractModel implements \Magento\Framework\DataObject\IdentityInterface
{

    const CACHE_TAG = 'omniva_label_history';

    protected function _construct() {
        $this->_init('Omniva\Shipping\Model\ResourceModel\LabelHistory');
    }

    public function getIdentities() {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

}
