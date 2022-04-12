<?php

namespace Omniva\Shipping\Controller\Adminhtml\Order;

use Magento\Backend\App\Action\Context;

/**
 * Class MassManifest
 */
class CallOmniva extends \Magento\Framework\App\Action\Action
{

    protected $omniva_carrier;

    public function __construct(
            Context $context, 
            \Omniva\Shipping\Model\Carrier $omniva_carrier) {
        $this->omniva_carrier = $omniva_carrier;
        parent::__construct($context);
    }

    public function execute() {

        $result = $this->omniva_carrier->callOmniva();
        if ($result) {
            $text = __('Omniva courier called');
            $this->messageManager->addSuccess($text);
        } else {
            $text = __('Failed to call Omniva courier');
            $this->messageManager->addWarning($text);
        }
        $this->_redirect($this->_redirect->getRefererUrl());
        return;
    }

}
