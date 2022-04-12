<?php
/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Omniva\Shipping\Model\Source;

class Generic implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @var \Magento\Fedex\Model\Carrier
     */
    protected $_shippingOmniva;

    /**
     * Carrier code
     *
     * @var string
     */
    protected $_code = '';

    /**
     * @param \Magento\Fedex\Model\Carrier $shippingOmniva
     */
    public function __construct(\Omniva\Shipping\Model\Carrier $shippingOmniva)
    {
        $this->_shippingOmniva = $shippingOmniva;
    }

    /**
     * Returns array to be used in multiselect on back-end
     *
     * @return array
     */
    public function toOptionArray()
    {
        $configData = $this->_shippingOmniva->getCode($this->_code);
        $arr = [];
        foreach ($configData as $code => $title) {
            $arr[] = ['value' => $code, 'label' => $title];
        }
        return $arr;
    }
}
