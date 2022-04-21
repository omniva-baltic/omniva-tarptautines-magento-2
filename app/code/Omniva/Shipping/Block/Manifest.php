<?php

namespace Omniva\Shipping\Block;

use Omniva\Shipping\Model\Carrier;
use Omniva\Shipping\Model\LabelHistoryFactory;
use Omniva\Shipping\Model\OmnivaOrderFactory;
use Magento\Sales\Model\OrderFactory;

class Manifest extends \Magento\Framework\View\Element\Template
{

    protected $_orderCollectionFactory;
    private $productMetadata;
    protected $omnivaCarrier;
    protected $labelhistoryFactory;
    protected $omnivaOrderFactory;
    protected $orderFactory;

    public function __construct(
            \Magento\Framework\View\Element\Template\Context $context,
            \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
            \Magento\Framework\App\ProductMetadataInterface $productMetadata,
            Carrier $omnivaCarrier,
            LabelHistoryFactory $labelhistoryFactory,
            OmnivaOrderFactory $omnivaOrderFactory,
            OrderFactory $orderFactory
    ) {
        parent::__construct($context);
        $this->productMetadata = $productMetadata;
        $this->omnivaCarrier = $omnivaCarrier;
        $this->_orderCollectionFactory = $orderCollectionFactory;
        $this->labelhistoryFactory = $labelhistoryFactory;
        $this->omnivaOrderFactory = $omnivaOrderFactory;
        $this->orderFactory = $orderFactory;
    }

    public function getMagentoVersion() {
        return $this->productMetadata->getVersion();
    }

    public function getOrders() {
        $collection = $this->omnivaOrderFactory->create()->getCollection();
        return $collection;
    }
    
    public function getShippingMethod($order) {
        $order_shipping_method = strtolower($order->getData('shipping_method'));
        if ($order_shipping_method === 'omniva_courier') {
            return __('Courier');
        }
        if ($order_shipping_method === 'omniva_courier_plus') {
            return __('Courier Plus');
        }
        if ($order_shipping_method === 'omniva_parcel_terminal') {
            return __('Parcel terminal') . ': '. $this->getTerminal($order);
        }
        return '-';
    }

    public function getOrderTrackings($order) {
        if ($this->getMagentoVersion() < '2.3.0') {
            $old_version = true;
        } else {
            $old_version = false;
        }
        $barcode = '';
        /*
        foreach ($order->getShipmentsCollection() as $shipment) {
            foreach ($shipment->getAllTracks() as $tracknum) {
                $barcode .= '<a href = "'.$this->getUrl('omniva/omnivamanifest/printlabels' . ($old_version ? 'ov' : '')).'?barcode='.$tracknum->getNumber().'" target = "_blank">'.$tracknum->getNumber() . '</a> ';
            }
        }*/
        if (!$barcode) {
            return '-';
        }
        return $barcode;
    }
    
    public function getTerminal($order) {
        $shippingAddress = $order->getShippingAddress();
        $terminal_id = $shippingAddress->getOmnivaIntTerminal();
        $parcel_terminal = $this->omnivaCarrier->getTerminalAddress($terminal_id);
        return $parcel_terminal;
    }

    public function getOrderIncrement($omniva_order) {
        $orderModel = $this->orderFactory->create();
        $order = $orderModel->load($omniva_order->getOrderId());
        return $order->getIncrementId();
    }

    public function getOrderUrl($orderId) {
        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }
    
    public function getOrderHistory($order) {
        if ($this->getMagentoVersion() < '2.3.0') {
            $old_version = true;
        } else {
            $old_version = false;
        }
        $history = '';
        try {
            $history_items = $this->labelhistoryFactory->create()->getCollection() ->addFieldToSelect('*');
            $history_items->addFieldToFilter('order_id', array('eq' => $order->getId()));
            foreach ($history_items as $item) {
                $history .= '<a href = "'.$this->getUrl('omniva/omnivamanifest/printlabels' . ($old_version ? 'ov' : '')).'?barcode='.$item->getLabelBarcode().'" target = "_blank">' . $item->getLabelBarcode() . '</a> ';
                if ($item->getServices()) {
                    $history .= $item->getServices() . ' ';
                }
                $history .= $item->getCreatedAt() . '<br/>';
            }
        } catch (\Exception $e) {
            return $e->getMessage();
        }
        if (!$history) {
            return '-';
        }
        return $history;
    }

}
