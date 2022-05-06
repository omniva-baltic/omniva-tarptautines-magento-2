<?php

namespace Omniva\Shipping\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;

class PrintLabel extends \Magento\Sales\Controller\Adminhtml\Order
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
        $params = $this->getRequest()->getParams();
        if (isset($params['shipment_id'])){
            try {
                $response = $this->omniva_carrier->getOmnivaShipmentLabel($params['shipment_id']);
                $pdf = base64_decode($response->base64pdf);
                header('Content-type:application/pdf');
                header('Content-disposition: inline; filename="'.$params['shipment_id'].'"');
                header('content-Transfer-Encoding:binary');
                header('Accept-Ranges:bytes');
                echo $pdf;
                exit;
            } catch (\Exception $e) {
                echo $e->getMessage();
                exit;
            }
        }
    }
}