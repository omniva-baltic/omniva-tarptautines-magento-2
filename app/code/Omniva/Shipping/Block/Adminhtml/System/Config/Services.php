<?php
/**
 * Copyright © 2016 MageWorx. All rights reserved.
 * See LICENSE.txt for license details.
 */

namespace Omniva\Shipping\Block\Adminhtml\System\Config;

use Magento\Framework\Data\Form\Element\AbstractElement;
use Omniva\Shipping\Model\Carrier;

class Services extends \Magento\Config\Block\System\Config\Form\Field
{
    protected $_template = 'Omniva_Shipping::system/config/services.phtml';

    protected $_values = null;

    protected $omnivaCarrier;

    /**
     * Checkbox constructor.
     * @param \Magento\Backend\Block\Template\Context $context
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        array $data = [],
        Carrier $omnivaCarrier
    ) {
        $this->omnivaCarrier = $omnivaCarrier;
        parent::__construct($context, $data);
    }
    /**
     * Retrieve element HTML markup.
     *
     * @param AbstractElement $element
     *
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        $this->setNamePrefix($element->getName())
            ->setHtmlId($element->getHtmlId());

        return $this->_toHtml();
    }

    public function getValues($id)
    {
        $services = $this->omnivaCarrier->getServices();
        $group = $this->getGroup($id);
        $values = [];
        $optionArray = [['value' => 'checkbox', 'label'=>$group]];//\Vendor\YourModule\Model\Config\Source\Checkbox::toOptionArray();
        foreach ($services as $service) {
            $group_code = str_ireplace([' ','-'],'_', strtolower($service->service_type));
            if ($service->delivery_to_address == false) {
                $group_code = 'terminals';
            }
            if ($group == $group_code) {
                $values[$service->service_code] = $service->name;
            }
        }
        return $values;
    }

    private function getGroup($id) {
        //carriers_omnivaglobal_express_service_group_couriers_checkbox
        return str_ireplace(['carriers_omnivaglobal_', '_service_group_couriers'], '', $id);
    }

    /**
     * Get checked value.
     * @param  $name
     * @return boolean
     */
    public function getIsChecked($name, $id)
    {
        $group = $this->getGroup($id);
        $path = 'carriers/omnivaglobal/' . $group . '_service_group/couriers';
        return in_array($name, $this->getCheckedValues($path));
    }
    /**
     *
     * Retrieve the checked values from config
     */
    public function getCheckedValues($path)
    {
            $data = $this->getConfigData();
            if (isset($data[$path])) {
                $data = $data[$path];
            } else {
                $data = '';
            }
            return explode(',', $data);
    }
}
