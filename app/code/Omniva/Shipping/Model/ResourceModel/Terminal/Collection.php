<?php

namespace Omniva\Shipping\Model\ResourceModel\Terminal;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected $_idFieldName = 'id';
    protected $_eventPrefix = 'omniva';
    protected $_eventObject = 'terminal_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() {
        $this->_init('Omniva\Shipping\Model\Terminal', 'Omniva\Shipping\Model\ResourceModel\Terminal');
    }

}