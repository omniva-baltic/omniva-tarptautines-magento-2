<?php
namespace Omniva\Shipping\Plugin\Config\Field;

use Magento\Config\Model\Config\Structure\Data as StructureData;
use Magento\Framework\Module\ModuleListInterface;
use Omniva\Shipping\Model\Carrier;

class Data
{
    
    protected $omnivaCarrier;

    protected $services;

    protected $groups = [];

    public function __construct(ModuleListInterface $moduleList, Carrier $omnivaCarrier)
    {
        $this->_moduleList = $moduleList;
        $this->omnivaCarrier = $omnivaCarrier;
        $this->services = $this->omnivaCarrier->getServices();
        $this->orderGroups();
    }
    public function beforeMerge(StructureData $object, array $config)
    {
       $moduleList = $this->_moduleList->getNames();
       foreach ($moduleList as $name)
       {
          if (strpos($name, 'Omniva_Shipping') === false)
          {
               continue;
           }
           $this->moduleslist[] = $name;
        }
        if (!isset($config['config']['system']['sections']['carriers']["children"]))
        {
            return [$config];
        }
        $sections = $config['config']['system']['sections']['carriers']["children"];
        /*
        echo "<pre>";
        var_dump($sections); 
        echo "</pre>";
        exit;
        */
        
        foreach ($sections as $sectionId => $section)
        {
            
            if ($sectionId == 'omnivaglobal')
            {
                /*
                echo "<pre>";
                var_dump($config['config']['system']['sections']['carriers']["children"][$sectionId]['children']); 
                echo "</pre>";
                exit;
                */
                foreach ($this->moduleslist as $moduleName)
                {
                    foreach ($this->groups as $group) {
                        $dynamicGroups = $this->getGroups($moduleName, $section['id'], $group);
                        //var_dump($dynamicGroups); 
                        if (!empty($dynamicGroups))
                        {
                            $config['config']['system']['sections']['carriers']["children"][$sectionId]['children'] += $dynamicGroups;
                        }
                    }
                }
            }
        }
        //exit;
        return [$config];
    }
    protected function getGroups($moduleName, $sectionName, $group)
    {
        $group_id = $group['code']. "_service_group";
        $path = "carriers/omnivaglobal";
        
        $fields = $this->getFields($path, $group_id, $moduleName);

        return [
            $group_id => [
                'id'            => $group_id,
                'label'         => $group['name'] . ' service',
                'showInDefault' => '1',
                'showInWebsite' => '0',
                'showInStore'   => '0',
                'sortOrder'     => 1,
                '_elementType'  => 'group',
                'path'          => $path,
                'children'      => $fields
            ]
        ];
    }
    protected function getFields($path, $group_id, $moduleName)
    {
        return [
            'active'        => [
                'id'            => 'active',
                'label'         => 'Active',
                'source_model'  => 'Magento\Config\Model\Config\Source\Yesno',
                'type'          => 'select',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'sortOrder'     =>  1,
                'translate'     => 'label',
                'module_name'   => $moduleName,
                'validate'      => '',
                '_elementType'  => 'field',
                'path'          => $path . '/' . $group_id
            ],
            'title'        => [
                'id'            => 'title',
                'label'         => 'Title',
                'type'          => 'text',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'sortOrder'     =>  1,
                'translate'     => 'label',
                'module_name'   => $moduleName,
                'validate'      => '',
                '_elementType'  => 'field',
                'path'          => $path . '/' . $group_id
            ],
            'services_order'        => [
                'id'            => 'services_order',
                'label'         => 'Services order',
                'source_model'  => 'Omniva\Shipping\Model\Source\ServicesOrder',
                'type'          => 'select',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'sortOrder'     =>  1,
                'translate'     => 'label',
                'module_name'   => $moduleName,
                'validate'      => 'required-entry',
                '_elementType'  => 'field',
                'path'          => $path . '/' . $group_id
            ],
            'price_type'        => [
                'id'            => 'price_type',
                'label'         => 'Price type',
                'source_model'  => 'Omniva\Shipping\Model\Source\PriceType',
                'type'          => 'select',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'sortOrder'     =>  1,
                'translate'     => 'label',
                'module_name'   => $moduleName,
                'validate'      => '',
                '_elementType'  => 'field',
                'path'          => $path . '/' . $group_id,
                'comment'       => 'Select price type for services'
            ],
            'price_value'        => [
                'id'            => 'price_value',
                'label'         => 'Price value',
                'type'          => 'text',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'sortOrder'     =>  1,
                'translate'     => 'label',
                'module_name'   => $moduleName,
                'validate'      => 'validate-number validate-zero-or-greater',
                '_elementType'  => 'field',
                'path'          => $path . '/' . $group_id
            ],
            'free_shipping_amount' => [
                'id'            => 'free_shipping_amount',
                'label'         => 'Free shipping cart amount',
                'type'          => 'text',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'sortOrder'     =>  1,
                'translate'     => 'label',
                'module_name'   => $moduleName,
                'validate'      => 'validate-number validate-zero-or-greater',
                '_elementType'  => 'field',
                'path'          => $path . '/' . $group_id,
                'comment'       => 'Enter 0 to disable'
            ],
            'couriers'        => [
                'id'            => 'couriers',
                'label'         => 'Couriers',
                'source_model'  => 'Omniva\Shipping\Model\Source\Services',
                'type'          => 'multiselect',
                'frontend_model' => 'Omniva\Shipping\Block\Adminhtml\System\Config\Services',
                'showInDefault' => '1',
                'showInWebsite' => '1',
                'showInStore'   => '1',
                'sortOrder'     =>  1,
                'translate'     => 'label',
                'module_name'   => $moduleName,
                'validate'      => '',
                '_elementType'  => 'field',
                'path'          => $path . '/' . $group_id,
                'comment'       => 'Select couriers to enable them'
            ],
        ];
    }

    private function orderGroups() {
        foreach ($this->services as $service) {
            $group_name = $service->service_type;
            $group_code = str_ireplace([' ','-'],'_', strtolower($service->service_type));
            if ($service->delivery_to_address == false) {
                $group_name = 'Terminals';
                $group_code = 'terminals';
            }
            if (!isset($this->groups[$group_code])) {
                $this->groups[$group_code] = ['code' => $group_code, 'name' => $group_name, 'services' => []];
            }
            $this->groups[$group_code]['services'][] = $service;
        }
    }
}