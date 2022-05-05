<?php
namespace Omniva\Shipping\Model\Quote;

use Magento\Quote\Model\Quote\Address;
use Omniva\Shipping\Model\Carrier;

class AddressPlugin
{
    /**
     * Hook into setShippingMethod.
     * As this is magic function processed by __call method we need to hook around __call
     * to get the name of the called method. after__call does not provide this information.
     *
     * @param Address $subject
     * @param callable $proceed
     * @param string $method
     * @param mixed $vars
     * @return Address
     */
    public function around__call($subject, $proceed, $method, $vars)
    {
    	
        $result = $proceed($method, $vars);

        if ($method == 'setShippingMethod'
            && stripos($vars[0], Carrier::CODE) !== false && stripos($vars[0], "_terminal") !== false 
            && $subject->getExtensionAttributes()
            && $subject->getExtensionAttributes()->getOmnivaIntTerminal()
        ) {
            $subject->setOmnivaIntTerminal($subject->getExtensionAttributes()->getOmnivaIntTerminal());
        }
        elseif (
            $method == 'setShippingMethod'
            && (stripos($vars[0], Carrier::CODE) === false || stripos($vars[0], "_terminal") === false)
        ) {
            //reset office when changing shipping method
            $subject->setOmnivaIntTerminal(0);
        }
        return $result;

    }
}