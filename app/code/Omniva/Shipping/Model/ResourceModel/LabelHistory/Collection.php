<?php

namespace Omniva\Shipping\Model\ResourceModel\LabelHistory;

class Collection extends \Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection {

    protected $_idFieldName = 'labelhistory_id';
    protected $_eventPrefix = 'omniva';
    protected $_eventObject = 'label_history_collection';

    /**
     * Define resource model
     *
     * @return void
     */
    protected function _construct() {
        $this->_init('Omniva\Shipping\Model\LabelHistory', 'Omniva\Shipping\Model\ResourceModel\LabelHistory');
    }

}