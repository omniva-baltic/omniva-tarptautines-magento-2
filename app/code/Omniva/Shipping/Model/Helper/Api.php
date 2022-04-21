<?php

namespace Omniva\Shipping\Model\Helper;

use OmnivaApi\API as Omniva_api;
use Magento\Variable\Model\VariableFactory;
use Omniva\Shipping\Model\TerminalFactory;

class Api {
    
    private $omniva_api;
    private $prefix = 'omniva_global_api';
    private $variableFactory;
    private $terminalFactory;
    
    public function __construct(VariableFactory $variableFactory, TerminalFactory $terminalFactory) {
        $this->variableFactory = $variableFactory;
        $this->terminalFactory = $terminalFactory;
    }
    
    public function setup($secret, $api_url) {
        $this->omniva_api = new Omniva_api($secret, false, false);
        $this->omniva_api->setUrl($api_url . "/api/v1/");
    }
    
    public function get_services(){
        try {
            $cache_name = $this->prefix . '_services';
            $data = $this->get_cache($cache_name);
            if ($data === false) {
                $data = $this->omniva_api->listAllServices();
                $this->set_cache($cache_name, $data, 1800);
            }
        } catch (\Exception $e) {
            $data = [];
        }
        return $data;
    }
    
    public function get_countries(){
        try {
            $cache_name = $this->prefix . '_countries';
            $data = $this->get_cache($cache_name);
            if ($data === false) {
                $data = $this->omniva_api->listAllCountries();
                $this->set_cache($cache_name, $data, 1800);
            }
        } catch (\Exception $e) {
            $data = [];
        }
        return $data;
    }

    public function update_terminals(){
        try {
            $this->omniva_api->setTimeout(30);
            $data = $this->omniva_api->getTerminals('ALL');
        } catch (\Exception $e) {
            $data = [];
        }
            
        if (isset($data->parcel_machines) && is_array($data->parcel_machines)) {
            $terminals = $this->terminalFactory->create()->getCollection();
            $terminals->walk('delete');
            foreach ($data->parcel_machines as $terminal) {
                $model = $this->terminalFactory->create();
                $data = array( 
                    'terminal_id' => $terminal->id,
                    'name' => $terminal->name ?? "", 
                    'city' => $terminal->city ?? "", 
                    'country_code' => $terminal->country_code,
                    'address' => $terminal->address,
                    'zip' => $terminal->zip,
                    'x_cord' => $terminal->x_cord,
                    'y_cord' => $terminal->y_cord,
                    'comment' => $terminal->comment ?? "",
                    'identifier' => $terminal->identifier,
                );
                $model->setData($data);
                $model->save();
            }
        }
    }
    
    public function get_terminals($country_code = "ALL"){
        
        try {
            $terminals = $this->terminalFactory->create()->getCollection();
            if ($country_code != "ALL") {
                $terminals->addFieldToFilter('country_code', $country_code);
            }
            if (!$terminals->count()){
                $this->update_terminals();
                $terminals = $this->terminalFactory->create()->getCollection();
                if ($country_code != "ALL") {
                    $terminals->addFieldToFilter('country_code', $country_code);
                }
            }
            return $terminals;
        } catch (\Exception $e) {
            $data = [];
        }
        return [];
    }
    
    public function get_terminal_address($terminal_id){
        try {
            $terminal = $this->terminalFactory->create()->getCollection()->addFieldToFilter('terminal_id', $terminal_id)->getFirstItem();
            if ($terminal) {
                return $terminal->getName() . ', ' . $terminal->getAddress() . ', ' . $terminal->getCity() . ', ' . $terminal->getCountryCode();
            }
        } catch (\Exception $e) {
        }
        return "";
    }
    
    public function get_terminal($terminal_id){
        try {
            $terminal = $this->terminalFactory->create()->getCollection()->addFieldToFilter('terminal_id', $terminal_id)->getFirstItem();
            return $terminal;
        } 
        catch (\Exception $e) {
        }
        return null;
    }
    
    public function get_offers($sender, $receiver, $parcels){
        return $this->omniva_api->getOffers($sender, $receiver, $parcels);
    }
    
    public function create_order($order){
        return $this->omniva_api->generateOrder($order);
    }
    
    public function cancel_order($shipment_id){
        return $this->omniva_api->cancelOrder($shipment_id);
    }
    
    public function get_label($shipment_id){
        return $this->omniva_api->getLabel($shipment_id);
    }
    
    public function generate_manifest($cart_id){
        return $this->omniva_api->generateManifest($cart_id);
    }
    
    public function generate_latest_manifest(){
        return $this->omniva_api->generateManifestLatest();
    }
    
    private function get_cache($name) {
        $var = $this->variableFactory->create();
        $var->loadByCode($name);
        if ($var->getId()) {
            $data = @json_decode($var->getPlainValue());
            if (is_object($data) && $data->expires > time()) {
                return $data->data;
            }
        }
        return false;
    }
    
    private function set_cache($name, $value, $expires) {
        $data = json_encode([
            'data' => $value,
            'expires' => time() + $expires
        ]);
        $var = $this->variableFactory->create();
        $var->loadByCode($name);
        if (!$var->getId()) {
            $var->setData(['code' => $name, 'plain_value' => $data]);
        } else {
            $var->addData(['plain_value' => $data]);
        }
        $var->save();
    }
}
