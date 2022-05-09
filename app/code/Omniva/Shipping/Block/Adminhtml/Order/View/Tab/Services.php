<?php

namespace Omniva\Shipping\Block\Adminhtml\Order\View\Tab;


class Services extends \Magento\Backend\Block\Template implements \Magento\Backend\Block\Widget\Tab\TabInterface
{
    /**
     * Template
     *
     * @var string
     */
    protected $_template = 'order/view/tab/services.phtml';

    protected $omniva_carrier;
    
    protected $shipping_helper;
   
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $coreRegistry = null;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Framework\Registry $registry,
        \Omniva\Shipping\Model\Carrier $omniva_carrier,
        \Omniva\Shipping\Model\Helper\ShippingMethod $shipping_helper,
        array $data = []
    ) {
        $this->coreRegistry = $registry;
        $this->omniva_carrier = $omniva_carrier;
        $this->shipping_helper = $shipping_helper;
        parent::__construct($context, $data);
    }

    /**
     * Retrieve order model instance
     *
     * @return \Magento\Sales\Model\Order
     */
    public function getOrder()
    {
        return $this->coreRegistry->registry('current_order');
    }   

    public function getOmnivaOrder() {
        return $this->omniva_carrier->getOmnivaOrder($this->getOrder());
    }

    public function getServices() {
        return array(
            array(
                'title' => 'C.O.D.',
                'value' => 'cod'
            )
        );
    }
    
    public function isOmnivaMethod($order)
      {
        $order_shipping_method = strtolower($order->getData('shipping_method'));
        return stripos($order_shipping_method, 'omnivaglobal_') !== false;
      }

    /**
     * {@inheritdoc}
     */
    public function getTabLabel()
    {
        return __('Omniva international');
    }

    /**
     * {@inheritdoc}
     */
    public function getTabTitle()
    {
        return __('Omniva international');
    }

    /**
     * {@inheritdoc}
     */
    public function canShowTab()
    {
        // For me, I wanted this tab to always show
        // You can play around with the ACL settings 
        // to selectively show later if you want
        //return true;
        return $this->isOmnivaMethod($this->getOrder());
    }

    /**
     * {@inheritdoc}
     */
    public function isHidden()
    {
        // For me, I wanted this tab to always show
        // You can play around with conditions to
        // show the tab later
        return false;
    }

    /**
     * Get Tab Class
     *
     * @return string
     */
    public function getTabClass()
    {
        // I wanted mine to load via AJAX when it's selected
        // That's what this does
        //return 'ajax only';
        return '';
    }

    /**
     * Get Class
     *
     * @return string
     */
    public function getClass()
    {
        return $this->getTabClass();
    }

    /**
     * View URL getter
     *
     * @param int $orderId
     * @return string
     */
    public function getViewUrl($orderId)
    {
        return $this->getUrl('omnivaservices/*/*', ['order_id' => $orderId]);
    }

    public function getTerminalName() {
        $order = $this->getOrder();
        
        if (stripos($order->getData('shipping_method'), 'omnivaglobal_') !== false && stripos($order->getData('shipping_method'), '_terminal') !== false) {
            return $this->getTerminal($order);
        }
        return false;
    }

    public function getCurrentTerminal() {
        //$orderRepository = new \Magento\Sales\Model\OrderRepository();
        $order_id = $this->getRequest()->getParam('order_id');
        $order = $this->getOrder();
        
        if (stripos($order->getData('shipping_method'), 'omnivaglobal_') !== false && stripos($order->getData('shipping_method'), '_terminal') !== false) {
            return $this->getTerminalId($order);
        }
        return false;
    }

    public function getTerminalId($order) {
        $shippingAddress = $order->getShippingAddress();
        $terminal_id = $shippingAddress->getOmnivaIntTerminal();
        return $terminal_id;
    }

    public function getTerminal($order) {
        $shippingAddress = $order->getShippingAddress();
        $terminal_id = $shippingAddress->getOmnivaIntTerminal();
        
        $parcel_terminal = $this->omniva_carrier->getTerminalAddress($terminal_id);
        return $parcel_terminal;
    }

    public function getTerminals($order = false) {
        $parcel_terminals = $this->omniva_carrier->getTerminals($this->getReceiverCountry($order)); //$this->getAddress()->getCountryId());
        return $parcel_terminals;
    }

    public function getReceiverCountry($order) {
        if (!$order) {
            return 'LT';
        }
        $shippingAddress = $order->getShippingAddress();
        $country = $shippingAddress->getCountryId();
        return $country ?? 'LT';
    }

    public function showTrackings($order){
        if ($order->getTrackingNumbers()) {
            $trackings = json_decode($order->getTrackingNumbers());
            return implode(', ', $trackings);
        } else {
            return '-';
        }
    }
}