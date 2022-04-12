<?php

namespace Omniva\Shipping\Model\ResourceModel\OmnivaOrder;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'omniva';
    protected $_eventObject = 'order_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() {
        $this->_init('Omniva\Shipping\Model\OmnivaOrder', 'Omniva\Shipping\Model\ResourceModel\OmnivaOrder');
    }

}