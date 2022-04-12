<?php

namespace Omniva\Shipping\Block\Adminhtml\Order\Create\Shipping\Method;

use Magento\Quote\Model\Quote\Address\Rate;
use Omniva\Shipping\Model\Carrier;

/**
 * Class Form
 * @package MagePal\CustomShippingRate\Block\Adminhtml\Order\Create\Shipping\Method
 */
class Form extends \Magento\Sales\Block\Adminhtml\Order\Create\Shipping\Method\Form
{
    
    protected $omnivaCarrier;
    
    public function __construct(
        Carrier $omnivaCarrier
    ) {
        $this->omnivaCarrier = $omnivaCarrier;
        parent::contruct();
    }
    
    public function getCurrentTerminal(){
        return $this->getAddress()->getOmnivaIntTerminal();
    }
    
    public function getTerminals()
    {
        $rate = $this->getActiveMethodRate();
        $parcel_terminals = $this->omnivaCarrier->getTerminals($this->getAddress()->getCountryId());
        return $parcel_terminals;
    } 
    
}