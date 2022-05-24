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
        
        $this->api->setup($this->getConfigData('secret') ? $this->getConfigData('secret')  : 'no_token', $this->getConfigData('production_webservices_url'));
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
                
        $services = $this->getServices();
        $result = $this->_rateFactory->create();
        
        $packageValue = $request->getPackageValueWithDiscount();
        $packageWeight = $request->getPackageWeight();
        $this->_updateFreeMethodQuote($request);
        
        
        
        try {
            $offers = $this->filter_enabled_offers($this->get_offers($request), $services);
        } catch (\Exception $e) {
            $this->logger->error($e->getMessage() . ' in file ' . $e->getFile() . ' on line ' . $e->getLine());
            return $result;
        }
        $this->set_offers_price($offers);
        
        $grouped = $this->sort_offers($offers);
        //$this->logger->debug(json_encode($offers));
        
        $current_terminal = 0;
        foreach ($grouped as $group => $offers) {

            $free_shipping_amount = $this->getConfigData($group . '_service_group/free_shipping_amount');
            $is_free_shipping = $free_shipping_amount && $free_shipping_amount < $packageValue ? true : false;

            $title = $this->getConfigData($group . '_service_group/title');
            if (!$title) {
                $title = ucfirst(str_ireplace('_', '', $group));
            }

            foreach ($offers as $offer) {
                if (!$this->is_offer_terminal($offer)) {
                
                    $method = $this->_rateMethodFactory->create();

                    $method->setCarrier('omnivaglobal');
                    $method->setCarrierTitle('Omniva');

                    $method->setMethod($offer->service_code);
                    $method->setMethodTitle($title. ' (' . $offer->delivery_time . ')');
                    $amount = $offer->price;
                    $method->setPrice($is_free_shipping ? 0 : $amount);
                    $method->setCost($is_free_shipping ? 0 : $amount);

                    $result->append($method);
                } else {
                    if ($current_terminal == 0) {
                        $method = $this->_rateMethodFactory->create();

                        $method->setCarrier('omnivaglobal');
                        $method->setCarrierTitle('Omniva');
                        $method->setMethod($offer->service_code . '_' . $offer->parcel_terminal_type . '_terminal');
                        $method->setMethodTitle($title. ' (' . $offer->delivery_time . ')');
                        $amount = $offer->price;
                        $method->setPrice($is_free_shipping ? 0 : $amount);
                        $method->setCost($is_free_shipping ? 0 : $amount);

                        $result->append($method);
                        $current_terminal++;
                    }
                }
            }
        }
        return $result;
    }

    private function orderGroups($services) {
        $groups = [];
        foreach ($services as $service) {
            $group_name = $service->service_type;
            $group_code = str_ireplace([' ','-'],'_', strtolower($service->service_type));
            if ($service->delivery_to_address == false) {
                $group_name = 'Terminals';
                $group_code = 'terminals';
            }
            $groups[$group_code] = $group_name;
        }
        return $groups;
    }
    
    private function filter_enabled_offers($offers, $services) {
        $groups = $this->orderGroups($services);
        $selected_services = [];
        foreach ($groups as $code=>$group) {
            if ($this->getConfigData($code.'_service_group/active') == '1') {
                $group_services = explode(',',$this->getConfigData($code.'_service_group/couriers'));
                foreach ($group_services as $g_service) {
                    if ($g_service) {
                        $selected_services[$g_service] = $code;
                    }
                }
            }
        }
       // $own_login = isset($config['own_login']) && $config['own_login'] == 'yes' ? true : false;
        $filtered_offers = [];
        //$this->logger->debug(json_encode($selected_services));
       // $this->logger->debug(json_encode($this->getConfigData()));
        foreach ($offers as $offer) {
            //$this->logger->debug(json_encode($offer));
            if (isset($selected_services[$offer->service_code])) {
                //check if has own login and info is entered in settings
                //if (!$this->is_own_login_ok($offer)) {
                //    continue;
                //}
                $offer->group = $selected_services[$offer->service_code];
                $filtered_offers[] = $offer;
            }
        }
        return $filtered_offers;
    }
    
    private function sort_offers(&$offers) {
        $grouped = array();
        foreach ($offers as $offer) {
            if (!isset($grouped[$offer->group])) {
                $grouped[$offer->group] = [];
            }
            $grouped[$offer->group][] = $offer;
        }
        foreach ($grouped as $group => $grouped_offers) {
            $sort_by = $this->getConfigData($group . '_service_group/services_order');
            if ($sort_by == "fastest") {
                usort($grouped[$group], function ($v, $k) {
                    return $this->get_offer_delivery($k) <= $this->get_offer_delivery($v);
                });
            } elseif ($sort_by == "cheapest") {
                usort($grouped[$group], function ($v, $k) {
                    return $k->price <= $v->price;
                });
            }
        }
        return $grouped;
    }
    
    private function set_offers_price(&$offers) {

        foreach ($offers as $offer) {
            $offer->org_price = $offer->price;
            $type = $this->getConfigData($offer->group . '_service_group/price_type');
            $value = $this->getConfigData($offer->group . '_service_group/price_value');
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
        $has_disabled_cats = $this->check_parcels_cats($request);
        if ($has_disabled_cats){
            return [];
        }
        $parcels = $this->get_parcels($request);
        return $this->api->get_offers($this->get_sender(), $this->get_receiver($request), $parcels);
    }

    private function is_disabled_cat($cat, $disabled_cats = false) {
        if ($disabled_cats == false) {
            $disabled_cats = explode(',',$this->getConfigData('omniva_product_group/product_categories_disable'));
        }
        if (empty($disabled_cats)) {
            return false;
        }
        if (in_array($cat->getId(), $disabled_cats)) {
            return true;
        }
        if ($cat->getLevel() > 1) {
            $parent = $cat->getParentCategory();
            if ($parent) {
                $data = $this->is_disabled_cat($parent, $disabled_cats);
                if ($data == true) {
                    return true;
                }
            }
        }
        return false;
    }

    private function get_cats_product_data(&$c_weight, &$c_width, &$c_length, &$c_height, $cat) {
        if ($c_weight == null) {
            $c_weight = $cat->getProductWeight();
        }
        if ($c_width == null) {
            $c_width = $cat->getProductWidth();
        }
        if ($c_length == null) {
            $c_length = $cat->getProductLength();
        }
        if ($c_height == null) {
            $c_height = $cat->getProductHeight();
        }
        if ($c_weight && $c_width && $c_length && $c_height) {
            return true;
        }
        if ($cat->getLevel() > 1) {
            $parent = $cat->getParentCategory();
            if ($parent) {
                $this->get_cats_product_data($c_weight, $c_width, $c_length, $c_height, $parent);
            }
        }
    }

    private function check_parcels_cats($request) {
            $items = $request->getAllItems();
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                $product = $item->getProduct();
                $cats = $product->getCategoryCollection();
                foreach ($cats as $cat) {
                    if ($this->is_disabled_cat($cat)) {
                        return true;
                    }
                } 
            }  
        return false;
    }
    
    private function get_parcels($request) {
        $parcels = [];
            $items = $request->getAllItems();
            foreach ($items as $item) {
                if ($item->getParentItem()) {
                    continue;
                }
                $c_weight = null;
                $c_width = null;
                $c_length = null;
                $c_height = null;
                $product = $item->getProduct();
                $cats = $product->getCategoryCollection();
                foreach ($cats as $cat) {
                    $this->get_cats_product_data($c_weight, $c_width, $c_length, $c_height, $cat);
                    if ($c_weight && $c_width && $c_length && $c_height) {
                        break;
                    }
                } 
                $parcel = new Parcel();
                $parcel->setUnitWeight($product->getData('weight') ?? ($c_weight ? $c_weight : $this->getConfigData('omniva_product_group/product_weight')) );
                $parcel->setHeight($product->getData('ts_dimensions_height') ?? ($c_height ? $c_height : $this->getConfigData('omniva_product_group/product_height')) );
                $parcel->setWidth($product->getData('ts_dimensions_width') ?? ($c_width ? $c_width : $this->getConfigData('omniva_product_group/product_width')) );
                $parcel->setLength($product->getData('ts_dimensions_length') ?? ($c_length ? $c_length : $this->getConfigData('omniva_product_group/product_length')) );
                $parcel->setAmount((int)($item->getQty() ?? $item->getQtyOrdered()));
                $parcels[] = $parcel->generateParcel();
            }  
        return $parcels;
    }
    
    private function get_items($request) {
        $items = [];
        $order_items = $request->getAllItems();
        foreach ($order_items as $id => $data) {
            if ($data->getParentItem()) {
                continue;
            }
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
            
            $order = $request->getOrderShipment()->getOrder();
            $omniva_order = $this->getOmnivaOrder($order);
            $services = [];
            $cod_amount = round($order->getGrandTotal(), 2);
            $order_services = json_decode($omniva_order->getServices(), true);
            if (is_array($order_services)) {
                foreach ($order_services as $k=>$v) {
                    if ($k == 'cod_amount') {
                        $cod_amount = $v;
                    } else {
                        $services[] = $v;
                    }
                }
            }
            //check if we have already generated 
            if ($omniva_order->getShipmentId()) {
                //try to delete old shipment
                $response = $this->api->cancel_order($omniva_order->getShipmentId());
                $omniva_order->setShipmentId(null);
                $omniva_order->setCartId(null);
                $omniva_order->setTrackingNumbers(null);
                $omniva_order->save();
            }
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
                $receiver->setHsCode($omniva_order->getEori());
            }

            //set COD
            $payment_method = $order->getPayment()->getMethodInstance()->getCode();
            $is_cod = $payment_method == 'msp_cashondelivery';
            if ($is_cod) {
                $services[] = 'cod';
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
        return $this->api->get_label($order->getShipmentId());
    }

    public function getOmnivaShipmentLabel($shipment_id) {
        return $this->api->get_label($shipment_id);
    }

    public function generateManifest($cart_id = null) {
        if ($cart_id) {
            return $this->api->generate_manifest($cart_id);
        } else {
            return $this->api->generate_latest_manifest();
        }
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
