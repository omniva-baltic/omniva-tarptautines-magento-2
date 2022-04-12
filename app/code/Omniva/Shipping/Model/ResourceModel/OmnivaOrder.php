<?php

namespace Omniva\Shipping\Model\ResourceModel;

class OmnivaOrder extends \Magento\Framework\Model\ResourceModel\Db\AbstractDb {

    public function __construct(
            \Magento\Framework\Model\ResourceModel\Db\Context $context
    ) {
        parent::__construct($context);
    }

    protected function _construct() {
        $this->_init('omniva_int_orders', 'id');
    }

    protected function _afterSave(\Magento\Framework\Model\AbstractModel $object) {
        
    }

}