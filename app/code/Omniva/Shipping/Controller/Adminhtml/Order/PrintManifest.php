<?php

namespace Omniva\Shipping\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Sales\Api\OrderManagementInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\InputException;
use Psr\Log\LoggerInterface;
use Omniva\Shipping\Model\OmnivaOrderFactory;

class PrintManifest extends \Magento\Sales\Controller\Adminhtml\Order
{

    protected $omniva_carrier;
    protected $omnivaOrderFactory;

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
        \Omniva\Shipping\Model\Carrier $omniva_carrier,
		OmnivaOrderFactory $omnivaOrderFactory
    ) {
        $this->omniva_carrier = $omniva_carrier;
		$this->omnivaOrderFactory = $omnivaOrderFactory;
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
        try {
            $labels = false;
            if (isset($params['labels']) && $params['labels'] == 1) {
                $labels = true;
            }
            if (!empty($params['omniva_global_manifest'])){
                $response = $this->omniva_carrier->generateManifest($params['omniva_global_manifest']);
            } else {
                $response = $this->omniva_carrier->generateManifest();
            }
            
            if (!$response->cart_id) {
                throw new \Exception('Bad response from server. No cart id returned');
            }
            $orders = $this->omnivaOrderFactory->create()->getCollection() ->addFieldToSelect('*');
            $orders->addFieldToFilter('cart_id', array(['eq' => $response->cart_id]));
            
            foreach ($orders as $order){
                $date = $order->getManifestDate();
                if (!$date) {
                    $order->setManifestDate(date('Y-m-d H:i:s') );
                    $order->save();
                }
            }
            if ($labels) {
                if (!$response->labels){
                    throw new \Exception('Labels PDF not received from server');
                }
                $pdf = base64_decode($response->labels);
            } else {
                if (!$response->manifest){
                    throw new \Exception('Manifest PDF not received from server');
                }
                $pdf = base64_decode($response->manifest);
            }
            header('Content-type:application/pdf');
            header('Content-disposition: inline; filename="'.$response->cart_id.'"');
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