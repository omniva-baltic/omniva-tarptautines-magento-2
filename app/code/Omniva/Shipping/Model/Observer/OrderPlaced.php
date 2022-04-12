<?php

namespace Omniva\Shipping\Model\Observer;

use Magento\Framework\Event\Observer as EventObserver;
use Magento\Framework\Event\ObserverInterface;

class OrderPlaced implements ObserverInterface
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    protected $omniva_carrier;

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectmanager
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectmanager,
        \Omniva\Shipping\Model\Carrier $omniva_carrier
        )
    {
        $this->_objectManager = $objectmanager;
        $this->omniva_carrier = $omniva_carrier;
    }

    public function execute(EventObserver $observer)
    {

        $order = $observer->getEvent()->getOrder();
        //get/create omniva order
        $omniva_order = $this->omniva_carrier->getOmnivaOrder($order);
        return $this;
    }

}