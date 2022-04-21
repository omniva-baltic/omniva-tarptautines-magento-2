<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
// @codingStandardsIgnoreFile

namespace Omniva\Shipping\Model;

use Magento\Framework\Module\Dir;
use Magento\Framework\Xml\Security;
use Magento\Quote\Model\Quote\Address\RateRequest;
use Magento\Shipping\Model\Carrier\AbstractCarrierOnline;
use Magento\Shipping\Model\Rate\Result;
use Magento\Shipping\Model\Tracking\Result as TrackingResult;

use Omniva\Shipping\Model\Helper\Api;
use Omniva\Shipping\Model\Helper\Helper;
use OmnivaApi\Sender;
use OmnivaApi\Receiver;
use OmnivaApi\Parcel;
use OmnivaApi\Item;
use OmnivaApi\Order as ApiOrder;

use Omniva\Shipping\Model\LabelHistoryFactory;
use Omniva\Shipping\Model\TerminalFactory;
use Omniva\Shipping\Model\OmnivaOrderFactory;

/**
 * Omniva shipping implementation
 *
 * @author Magento Core Team <core@magentocommerce.com>
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Carrier extends AbstractCarrierOnline implements \Magento\Shipping\Model\Carrier\CarrierInterface
{

    /**
     * Code of the carrier
     *
     * @var string
     */
    const CODE = 'omnivaglobal';


    /**
     * Code of the carrier
     *
     * @var string
     */
    protected $_code = self::CODE;

    /**
     * Rate request data
     *
     * @var RateRequest|null
     */
    protected $_request = null;

    /**
     * Rate result data
     *
     * @var Result|TrackingResult
     */
    protected $_result = null;

    /**
     * Path to locations xml
     *
     * @var string
     */
    protected $_locationFile;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    protected $_storeManager;

    /**
     * @var \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory
     */
    protected $_productCollectionFactory;

    /**
     * @var \Magento\Framework\Xml\Parser
     */
    private $XMLparser;
    protected $configWriter;

    /**
     * Session instance reference
     * 
     */
    protected $_checkoutSession;
    protected $variableFactory;
    protected $labelhistoryFactory;
    protected $terminalFactory;
    protected $omnivaOrderFactory;
    protected $shipping_helper;
    protected $api;
    protected $logger;

    /**
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory
     * @param \Psr\Log\LoggerInterface $logger
     * @param Security $xmlSecurity
     * @param \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory
     * @param \Magento\Shipping\Model\Rate\ResultFactory $rateFactory
     * @param \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory
     * @param \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory
     * @param \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory
     * @param \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory
     * @param \Magento\Directory\Model\RegionFactory $regionFactory
     * @param \Magento\Directory\Model\CountryFactory $countryFactory
     * @param \Magento\Directory\Model\CurrencyFactory $currencyFactory
     * @param \Magento\Directory\Helper\Data $directoryData
     * @param \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Module\Dir\Reader $configReader
     * @param \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory
     * @param array $data
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
            \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
            \Magento\Quote\Model\Quote\Address\RateResult\ErrorFactory $rateErrorFactory,
            \Psr\Log\LoggerInterface $logger,
            Security $xmlSecurity,
            \Magento\Shipping\Model\Simplexml\ElementFactory $xmlElFactory,
            \Magento\Shipping\Model\Rate\ResultFactory $rateFactory,
            \Magento\Quote\Model\Quote\Address\RateResult\MethodFactory $rateMethodFactory,
            \Magento\Shipping\Model\Tracking\ResultFactory $trackFactory,
            \Magento\Shipping\Model\Tracking\Result\ErrorFactory $trackErrorFactory,
            \Magento\Shipping\Model\Tracking\Result\StatusFactory $trackStatusFactory,
            \Magento\Directory\Model\RegionFactory $regionFactory,
            \Magento\Directory\Model\CountryFactory $countryFactory,
            \Magento\Directory\Model\CurrencyFactory $currencyFactory,
            \Magento\Directory\Helper\Data $directoryData,
            \Magento\CatalogInventory\Api\StockRegistryInterface $stockRegistry,
            \Magento\Store\Model\StoreManagerInterface $storeManager,
            \Magento\Framework\Module\Dir\Reader $configReader,
            \Magento\Catalog\Model\ResourceModel\Product\CollectionFactory $productCollectionFactory,
            \Magento\Framework\Xml\Parser $parser,
            \Magento\Framework\App\Config\Storage\WriterInterface $configWriter,
            \Magento\Checkout\Model\Session $checkoutSession,
            \Magento\Variable\Model\VariableFactory $variableFactory,
            LabelHistoryFactory $labelhistoryFactory,
            TerminalFactory $terminalFactory,
            OmnivaOrderFactory $omnivaOrderFactory,
            \Omniva\Shipping\Model\Helper\ShippingMethod $shipping_helper,
            Api $api,
            array $data = []
    ) {
        $this->_checkoutSession = $checkoutSession;

        $this->_storeManager = $storeManager;
        $this->_productCollectionFactory = $productCollectionFactory;
        $this->XMLparser = $parser;
        $this->variableFactory = $variableFactory;
        $this->labelhistoryFactory = $labelhistoryFactory;
        $this->terminalFactory = $terminalFactory;
        $this->omnivaOrderFactory = $omnivaOrderFactory;
        $this->shipping_helper = $shipping_helper;
        $this->api = $api;
        $this->logger = $logger;
        
        parent::__construct(
                $scopeConfig,
                $rateErrorFactory,
                $logger,
                $xmlSecurity,
                $xmlElFactory,
                $rateFactory,
                $rateMethodFactory,
                $trackFactory,
                $trackErrorFactory,
                $trackStatusFactory,
                $regionFactory,
                $countryFactory,
                $currencyFactory,
                $directoryData,
                $stockRegistry,
                $data
        );
        
        $this->api->setup($this->getConfigData('secret') ?? 'no_token', $this->getConfigData('production_webservices_url'));
    }

    /**
     * Collect and get rates
     *
     * @param RateRequest $request
     * @return Result|bool|null
     */
    public function collectRates(RateRequest $request) {
        if (!$this->getConfigFlag('active')) {
            return false;
        }
        //$this->api->get_terminals();
        $services_limit = $this->getConfigData('omniva_methods_group/services_limit') ?? 1;
        $courier_title = $this->getConfigData('omniva_methods_group/courier_title') ?? 'Courier';
        $terminal_title = $this->getConfigData('omniva_methods_group/terminal_title') ?? 'Terminal';
        
        $services = $this->getServices();
        $result = $this->_rateFactory->create();
        
        $packageValue = $request->getPackageValueWithDiscount();
        $packageWeight = $request->getPackageWeight();
        $this->_updateFreeMethodQuote($request);
        
        $free_shipping_amount = $this->getConfigData('omniva_price_group/free_shipping_amount');
        $is_free_shipping = $free_shipping_amount && $free_shipping_amount < $packageValue ? true : false;
        
        try {
            $offers = $this->filter_enabled_offers($this->get_offers($request));
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage());
            return $result;
        }
        $this->sort_offers($offers);
        $this->set_offers_price($offers);
        //$this->logger->debug(json_encode($offers));
        
        $current_service = 0;
        if ($this->getConfigData('omniva_methods_group/courier_active') && (!$this->getConfigData('omniva_methods_group/courier_max_weight') || $this->getConfigData('omniva_methods_group/courier_max_weight') > $packageWeight )) {
            foreach ($offers as $offer) {
                if ($this->is_offer_terminal($offer)) {
                    continue;
                }
                $method = $this->_rateMethodFactory->create();

                $method->setCarrier('omnivaglobal');
                $method->setCarrierTitle('Omniva');

                $method->setMethod($offer->service_code);
                $method->setMethodTitle($courier_title. ' (' . $offer->delivery_time . ')');
                $amount = $offer->price;
                $method->setPrice($is_free_shipping ? 0 : $amount);
                $method->setCost($is_free_shipping ? 0 : $amount);

                $result->append($method);
                $current_service++;
                if ($services_limit <= $current_service) {
                     break;
                }
            }
        }
        
        if ($this->getConfigData('omniva_methods_group/terminal_active') && (!$this->getConfigData('omniva_methods_group/terminal_max_weight') || $this->getConfigData('omniva_methods_group/terminal_max_weight') > $packageWeight )) {
            foreach ($offers as $offer) {
                if (!$this->is_offer_terminal($offer)) {
                    continue;
                }
                $method = $this->_rateMethodFactory->create();

                $method->setCarrier('omnivaglobal');
                $method->setCarrierTitle('Omniva');
                $offer->parcel_terminal_type = 'w2s_inpost';
                $method->setMethod($offer->service_code . '_' . $offer->parcel_terminal_type . '_terminal');
                $method->setMethodTitle($terminal_title. ' (' . $offer->delivery_time . ')');
                $amount = $offer->price;
                $method->setPrice($is_free_shipping ? 0 : $amount);
                $method->setCost($is_free_shipping ? 0 : $amount);

                $result->append($method);
                break;
            }
        }
        return $result;
    }
    
    private function filter_enabled_offers($offers) {
        //$config = $this->get_config();
       // $own_login = isset($config['own_login']) && $config['own_login'] == 'yes' ? true : false;
        $filtered_offers = [];
        $selected_services = explode(',',$this->getConfigData('omniva_methods_group/services'));
        $this->logger->debug(json_encode($selected_services));
        foreach ($offers as $offer) {
            $this->logger->debug(json_encode($offer));
            if (in_array($offer->service_code, $selected_services)) {
                //check if has own login and info is entered in settings
                //if (!$this->is_own_login_ok($offer)) {
                //    continue;
                //}
                $filtered_offers[] = $offer;
            }
        }
        return $filtered_offers;
    }
    
    private function sort_offers(&$offers) {
        $sort_by = $this->getConfigData('omniva_methods_group/services_order');
        if ($sort_by == "fastest") {
            usort($offers, function ($v, $k) {
                return $this->get_offer_delivery($k) <= $this->get_offer_delivery($v);
            });
        } elseif ($sort_by == "cheapest") {
            usort($offers, function ($v, $k) {
                return $k->price <= $v->price;
            });
        }
    }
    
    private function set_offers_price(&$offers) {
        $type = $this->getConfigData('omniva_price_group/price_type');
        $value = $this->getConfigData('omniva_price_group/price_value');

        foreach ($offers as $offer) {
            $offer->org_price = $offer->price;
            $offer->price = $this->calculate_price($offer->price, $type, $value);
        }
    }
    
    private function calculate_price($price, $type, $value) {
        if (!$value) {
            return $price;
        }
        if ($type == "fixed") {
            $price = $value;
        } else if ($type == "addition_percent") {
            $price += round($price * $value / 100, 2);
        } else if ($type == "addition_eur") {
            $price += $value;
        }
        return $price;
    }
    
    private function get_offer_delivery($offer) {
        $re = '/^[^\d]*(\d+)/';
        preg_match($re, $offer->delivery_time, $matches, PREG_OFFSET_CAPTURE, 0);
        return $matches[0] ?? 1;
    }

    
    private function get_offers($request) {
        $parcels = $this->get_parcels($request);
        return $this->api->get_offers($this->get_sender(), $this->get_receiver($request), $parcels);
    }
    
    private function get_parcels($request) {
        $parcels = [];
            $items = $request->getAllItems();
            foreach ($items as $item) {
                $product = $item->getProduct();
                $parcel = new Parcel();
                $parcel->setUnitWeight($product->getData('weight') ?? $this->getConfigData('omniva_product_group/product_weight'));
                $parcel->setHeight($product->getData('ts_dimensions_height') ?? $this->getConfigData('omniva_product_group/product_height'));
                $parcel->setWidth($product->getData('ts_dimensions_width') ?? $this->getConfigData('omniva_product_group/product_width'));
                $parcel->setLength($product->getData('ts_dimensions_length') ?? $this->getConfigData('omniva_product_group/product_length'));
                $parcel->setAmount((int)($item->getQty() ?? $item->getQtyOrdered()));
                $parcels[] = $parcel->generateParcel();
            }
        return $parcels;
    }
    
    private function get_items($request) {
        $items = [];
        $order_items = $request->getAllItems();
        foreach ($order_items as $id => $data) {
            $product = $data->getProduct();
            $item = new Item();
            $item->setItemAmount((int) ($data->getQty() ?? $data->getQtyOrdered()));
            $item->setDescription($product->getName());
            $item->setItemPrice($product->getFinalPrice() ?? $product->getPrice());
            $item->setCountryId($this->get_country_id($this->getConfigData('omniva_company_group/company_countrycode')));
            $items[] = $item->generateItem();
        }
        return $items;
    }
    
    private function is_offer_terminal($offer) {
        $services = $this->api->get_services();
        foreach ($services as $service) {
            if ($offer->service_code == $service->service_code) {
                if ($service->delivery_to_address == false) {
                    return true;
                }
                return false;
            }
        }
        return false;
    }
    
    private function get_sender() {
        $send_off = 'courier';

        $sender = new Sender($send_off);
        $sender->setCompanyName($this->getConfigData('omniva_company_group/cod_company'));
        $sender->setContactName($this->getConfigData('omniva_company_group/cod_company'));
        $sender->setStreetName($this->getConfigData('omniva_company_group/company_address'));
        $sender->setZipcode($this->getConfigData('omniva_company_group/company_postcode'));
        $sender->setCity($this->getConfigData('omniva_company_group/company_city'));
        $sender->setCountryId($this->get_country_id($this->getConfigData('omniva_company_group/company_countrycode')));
        $sender->setPhoneNumber($this->getConfigData('omniva_company_group/company_phone'));
        return $sender;
    }
    
    public function get_receiver($request, $cart = true) {
        $send_off = 'courier';
        if ($cart) {
            $quote = $this->_checkoutSession->getQuote(); 
            $address = $quote->getShippingAddress();
            //create from object on order
            $receiver = new Receiver($send_off);
            $receiver->setCompanyName($address->getCompany() ?? "");
            $receiver->setContactName($address->getData("firstname") . ' ' . $address->getData("lastname"));
            $receiver->setStreetName($request->getDestStreet());
            $receiver->setZipcode($request->getDestPostcode());
            $receiver->setCity($request->getDestCity());
            $receiver->setCountryId($this->get_country_id($address->getCountryId()));
            $receiver->setPhoneNumber((string)$address->getTelephone());
            return $receiver;
        } else {
            $receiver = new Receiver($send_off);
            $receiver->setCompanyName($request->getRecipientContactCompanyName() ?? "");
            $receiver->setContactName($request->getRecipientContactPersonName());
            $receiver->setStreetName($request->getRecipientAddressStreet1());
            $receiver->setZipcode((string)$request->getRecipientAddressPostalCode());
            $receiver->setCity($request->getRecipientAddressCity());
            $receiver->setCountryId($this->get_country_id($request->getRecipientAddressCountryCode()));
            $receiver->setPhoneNumber((string)$request->getRecipientContactPhoneNumber());
            return $receiver;
        }
    }
    
    private function get_country_id($country_code) {
        foreach ($this->api->get_countries() as $id => $country) {
            if ($country->code == $country_code) {
                return $country->id;
            }
        }
    }

    /**
     * Get version of rates request
     *
     * @return array
     */
    public function getVersionInfo() {
        return ['ServiceId' => 'crs', 'Major' => '10', 'Intermediate' => '0', 'Minor' => '0'];
    }

    /**
     * Get configuration data of carrier
     *
     * @param string $type
     * @param string $code
     * @return array|false
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function getCode($type, $code = '') {

        $codes = [
            'method' => [
                'COURIER' => __('Courier'),
                'PARCEL_TERMINAL' => __('Parcel terminal')
            ],
            'services_order' => [
                'default' => __('Default'),
                'cheapest' => __('Cheapest first'),
                'fastest' => __('Fastest first')
            ],
            'price_type' => [
                'fixed' => __('Fixed price'),
                'addition_percent' => __('Addition %'),
                'addition_eur' => __('Addition Eur')
            ],
            'country' => [
                'EE' => __('Estonia'),
                'LV' => __('Latvia'),
                'LT' => __('Lithuania')
            ],
            'tracking' => [
            ],
            'terminal' => [],
        ];
        if ($type == 'terminal') {
            $locations = [];
            $locationsArray = [];
            foreach ($locationsArray as $loc_data) {
                $locations[$loc_data['ZIP']] = array(
                    'name' => $loc_data['NAME'],
                    'country' => $loc_data['A0_NAME'],
                    'x' => $loc_data['X_COORDINATE'],
                );
            }
            $codes['terminal'] = $locations;
        }


        if (!isset($codes[$type])) {
            return false;
        } elseif ('' === $code) {
            return $codes[$type];
        }

        if (!isset($codes[$type][$code])) {
            return false;
        } else {
            return $codes[$type][$code];
        }
    }
    
    public function getServices() {
        return $this->api->get_services();
    }

    public function getTerminalAddress($terminal_id) {
        return $this->api->get_terminal_address($terminal_id);
    }

    public function getTerminals($countryCode = 'ALL') {
        return $this->api->get_terminals($countryCode);
    }

    /**
     * Get tracking
     *
     * @param string|string[] $trackings
     * @return Result|null
     */
    public function getTracking($trackings) {

        $result = $this->_trackFactory->create();
        if (!is_array($trackings)) {
            $trackings = [$trackings];
        }
        $resultArr = [];
        try {
            $username = $this->getConfigData('account');
            $password = $this->getConfigData('password');
            
            $tracking = new Tracking();
            $tracking->setAuth($username, $password);

            $results = $tracking->getTracking($trackings);

            if (is_array($results)) {
                foreach ($results as $barcode => $tracking_data) {
                    $awbinfoData = [];
                    $packageProgress = [];

                    foreach ($tracking_data as $data) {
                        $shipmentEventArray = [];
                        $shipmentEventArray['activity'] = $data['state'];
                        $shipmentEventArray['deliverydate'] = $data['date']->format('Y-m-d'); //date("Y-m-d", strtotime((string)$awbinfo->eventDate));
                        $shipmentEventArray['deliverytime'] = $data['date']->format('H:i:s'); //date("H:i:s", strtotime((string)$awbinfo->eventDate));
                        $shipmentEventArray['deliverylocation'] = $data['event'];
                        $packageProgress[] = $shipmentEventArray;
                    }
                    $awbinfoData['progressdetail'] = $packageProgress;
                    $resultArr[$barcode] = $awbinfoData;
                }
            }

            if (!empty($resultArr)) {
                foreach ($resultArr as $trackNum => $data) {
                    $tracking = $this->_trackStatusFactory->create();
                    $tracking->setCarrier($this->_code);
                    $tracking->setCarrierTitle($this->getConfigData('title'));
                    $tracking->setTracking($trackNum);
                    $tracking->addData($data);
                    $result->append($tracking);
                }
            }
        } catch (\Exception $e) {
            
        }
        //$this->_getXMLTracking($trackings);

        return $result;
    }

    /**
     * Get allowed shipping methods
     *
     * @return array
     */
    public function getAllowedMethods() {
        $allowed = explode(',', $this->getConfigData('allowed_methods'));
        $arr = [];
        foreach ($allowed as $k) {
            $arr[$k] = $this->getCode('method', $k);
        }

        return $arr;
    }

    public function callOmniva() {
        try {
            $username = $this->getConfigData('account');
            $password = $this->getConfigData('password');
            
            $pickStart = $this->getConfigData('pick_up_time_start')?$this->getConfigData('pick_up_time_start'):'8:00';
            $pickFinish = $this->getConfigData('pick_up_time_finish')?$this->getConfigData('pick_up_time_finish'):'17:00';

            $name = $this->getConfigData('cod_company');
            $phone = $this->getConfigData('company_phone');
            $street = $this->getConfigData('company_address');
            $postcode = $this->getConfigData('company_postcode');
            $city = $this->getConfigData('company_city');
            $country = $this->getConfigData('company_countrycode');

            $address = new Address();
            $address
                    ->setCountry($country)
                    ->setPostcode($postcode)
                    ->setDeliverypoint($city)
                    ->setStreet($street);

            // Sender contact data
            $senderContact = new Contact();
            $senderContact
                    ->setAddress($address)
                    ->setMobile($phone)
                    ->setPersonName($name);

            $call = new CallCourier();
            $call->setAuth($username, $password);
            $call->setSender($senderContact);
            $call->setEarliestPickupTime($pickStart);
            $call->setLatestPickupTime($pickFinish);
            $call_result = $call->callCourier();
            if ($call_result) {
                return true;
            } else {
                return false;
            }
        } catch (\Exception $e) {
            
        }
        return false;
    }

    protected function getReferenceNumber($order_number) {
        $order_number = (string) $order_number;
        $kaal = array(7, 3, 1);
        $sl = $st = strlen($order_number);
        $total = 0;
        while ($sl > 0 and substr($order_number, --$sl, 1) >= '0') {
            $total += substr($order_number, ($st - 1) - $sl, 1) * $kaal[($sl % 3)];
        }
        $kontrollnr = ((ceil(($total / 10)) * 10) - $total);
        return $order_number . $kontrollnr;
    }

    /**
     * Receive tracking number and labels.
     *
     * @param Array $barcodes
     * @return \Magento\Framework\DataObject
     */
    protected function _getShipmentLabels($barcodes) {

        $result = new \Magento\Framework\DataObject();
        try {
            $username = $this->getConfigData('account');
            $password = $this->getConfigData('password');

            $label = new Label();
            $label->setAuth($username, $password);
            $labels = $label->downloadLabels($barcodes, false, 'S');
            if ($labels) {
                $result->setShippingLabelContent($labels);
                $result->setTrackingNumber(is_array($barcodes) ? $barcodes[0] : $barcodes);
            } else {
                $result->setErrors(sprintf(__('Labels not received for barcodes: %s'), implode(', ', $barcodes)));
            }
        } catch (\Exception $e) {
            $result->setErrors($e->getMessage());
        }
        return $result;
    }
    
    public function getLabels($barcodes) {
        try {
            $username = $this->getConfigData('account');
            $password = $this->getConfigData('password');

            $label = new Label();
            $label->setAuth($username, $password);
            $combine = $this->getConfigData('combine_labels');
            $labels = $label->downloadLabels($barcodes, $combine, 'I');
            if ($labels) {
                
            } else {
                
            }
        } catch (\Exception $e) {
            
        }
    }

    /**
     * Do shipment request to carrier web service, obtain Print Shipping Labels and process errors in response
     *
     * @param \Magento\Framework\DataObject $request
     * @return \Magento\Framework\DataObject
     * @throws \Exception
     */
    protected function _doShipmentRequest(\Magento\Framework\DataObject $request) {
        $barcodes = array();
        $this->_prepareShipmentRequest($request);
        $result = new \Magento\Framework\DataObject();

        try {
            $services = [];
            $order = $request->getOrderShipment()->getOrder();
            $omniva_order = $this->getOmnivaOrder($order);
            //check if we have already generated 
            if ($omniva_order->getShipmentId()) {
                //try to delete old shipment
                $response = $this->api->cancel_order($omniva_order->getShipmentId());
                $omniva_order->setShipmentId(null);
                $omniva_order->setCartId(null);
                $omniva_order->save();
            }
            $cod_amount = 0;
            $service_code = $omniva_order->getServiceCode();
            $sender = $this->get_sender();
            $receiver = $this->get_receiver($request, false);
            
            $shippingAddress = $order->getShippingAddress();
            $terminal = $this->api->get_terminal($shippingAddress->getOmnivaIntTerminal());
            if ($terminal !== null) {
                $receiver->setShippingType('terminal');
                $receiver->setZipcode($terminal->getZip());
            }
            if ($omniva_order->getEori()) {
                $receiver->setEori($omniva_order->getEori());
            }

            //set COD
            $payment_method = $order->getPayment()->getMethodInstance()->getCode();
            $is_cod = $payment_method == 'msp_cashondelivery';
            if ($is_cod) {
                $services[] = 'cod';
                $cod_amount = round($order->getGrandTotal(), 2);
            }
                
            $api_order = new ApiOrder();
            $api_order->setSender($sender);
            $api_order->setReceiver($receiver);
            $api_order->setServiceCode($service_code);
            $api_order->setParcels($this->get_parcels($order));
            $api_order->setItems($this->get_items($order));
            $api_order->setReference($order->getIncrementId());
            $api_order->setAdditionalServices($services, $cod_amount);
            $response = $this->api->create_order($api_order);

            
            

            if ($response->shipment_id || $response->cart_id) {
                $omniva_order->setShipmentId($response->shipment_id);
                $omniva_order->setCartId($response->cart_id);
                $omniva_order->save();

            } else {
                $result->setErrors(__('No saved barcodes received'));
            }
        } catch (\Throwable $e) {
            $this->logger->debug($e->getMessage() . ' on ' . $e->getLine() . ' in ' . $e->getFile());
            $result->setErrors($e->getMessage());
        }
        return $result;
    }

    /**
     * @param array|object $trackingIds
     * @return string
     */
    private function getTrackingNumber($trackingIds) {
        return is_array($trackingIds) ? array_map(
                        function ($val) {
                            return $val->TrackingNumber;
                        },
                        $trackingIds
                ) : $trackingIds->TrackingNumber;
    }

    /**
     * For multi package shipments. Delete requested shipments if the current shipment
     * request is failed
     *
     * @param array $data
     * @return bool
     */
    public function rollBack($data) {
        
    }

    /**
     * Return delivery confirmation types of carrier
     *
     * @param \Magento\Framework\DataObject|null $params
     * @return array
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getDeliveryConfirmationTypes(\Magento\Framework\DataObject $params = null) {
        return $this->getCode('delivery_confirmation_types');
    }

    /**
     * Recursive replace sensitive fields in debug data by the mask
     * @param array $data
     * @return string
     */
    protected function filterDebugData($data) {
        foreach (array_keys($data) as $key) {
            if (is_array($data[$key])) {
                $data[$key] = $this->filterDebugData($data[$key]);
            } elseif (in_array($key, $this->_debugReplacePrivateDataKeys)) {
                $data[$key] = self::DEBUG_KEYS_MASK;
            }
        }
        return $data;
    }
    
    public function createLabelHistory($order, $barcode, $services = '') {
        try {
            $model = $this->labelhistoryFactory->create();
            $data = [
                'order_id' => $order->getId(),
                'label_barcode' => $barcode,
                'services' => $services,
            ];
            $model->setData($data);
            $model->save();
            return true;
        } catch (\Exception $e) {
            
        }
        return false;
    }

    public function getOmnivaOrderLabel($order) {
        $response = $this->api->get_label($order->getShipmentId());
    }
    

    public function getOmnivaOrder($order) {
        $order_shipping_method = $order->getData('shipping_method');
        if (stripos($order_shipping_method, 'omnivaglobal') === false) {
            return false;
        }

        try {
            $model = $this->omnivaOrderFactory->create()->getCollection()->addFieldToFilter('order_id', $order->getId())->getFirstItem();
            if ($model->getId()) {
                return $model;
            }
            return $this->createOmnivaOrder($order);
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return false;
    }
    
    public function createOmnivaOrder($order) {
        try {
            $model = $this->omnivaOrderFactory->create();
            $data = [
                'order_id' => $order->getId(),
                'service_code' => $this->getOrderServiceCode($order) ,
                'identifier' => $this->getOrderIdentifier($order) ,
            ];
            $model->setData($data);
            $model->save();
            return $model;
        } catch (\Exception $e) {
            $this->logger->debug($e->getMessage());
        }
        return false;
    }

    private function getOrderIdentifier($order) {
        $order_shipping_method = $order->getData('shipping_method');
        if (stripos($order_shipping_method, 'omnivaglobal') !== false && stripos($order_shipping_method, '_terminal') !== false) {
            $data = explode('_', str_ireplace(['omnivaglobal_', '_terminal'], '', $order_shipping_method));
            array_splice($data, 0, 1);
            return implode('_', $data);
        }
        return null;
    }

    private function getOrderServiceCode($order) {
        $order_shipping_method = $order->getData('shipping_method');
        if (stripos($order_shipping_method, 'omnivaglobal') !== false) {
            $data = explode('_', str_ireplace(['omnivaglobal_', '_terminal'], '', $order_shipping_method));
            return $data[0];
        }
        return null;
    }

}
