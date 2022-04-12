<?php

namespace Omniva\Shipping\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;

class SaveServicesAjax extends \Magento\Sales\Controller\Adminhtml\Order
{

    protected $omniva_carrier;

    public function __construct(
        Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        \Magento\Framework\App\Response\Http\FileFactory $fileFactory,
        \Magento\Framework\Translate\InlineInterface $translateInline,
        \Magento\Framework\View\Result\PageFactory $resultPageFactory,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        \Magento\Framework\View\Result\LayoutFactory $resultLayoutFactory,
        \Magento\Framework\Controller\Result\RawFactory $resultRawFactory,
        OrderManagementInterface $orderManagement,
        OrderRepositoryInterface $orderRepository,
        LoggerInterface $logger,
        \Omniva\Shipping\Model\Carrier $omniva_carrier
    ) {
        $this->omniva_carrier = $omniva_carrier;
        parent::__construct($context, $coreRegistry, $fileFactory, $translateInline, $resultPageFactory, $resultJsonFactory, $resultLayoutFactory, $resultRawFactory, $orderManagement, $orderRepository, $logger);
    }

    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */

    public function execute()
    {
        $order = $this->_initOrder();
        if ($order) {
            $params = $this->getRequest()->getParams();
            $services = array();
            if (isset($params['omniva_services'])){
                $services = $params['omniva_services'];
            }
            $resultJson = $this->resultJsonFactory->create();
            $omniva_order = $this->omniva_carrier->getOmnivaOrder($order);
            $omniva_order->setServices(json_encode($services));
            $omniva_order->setEori($params['omniva_eori'] ?? null);
            $omniva_order->save();
            return $resultJson->setData([
                'messages' => 'Successfully.' ,
                'error' => false
            ]);
        }
        return false;
    }
}