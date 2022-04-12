<?php

namespace Omniva\Shipping\Model;

use Omniva\Shipping\Api\ParcelTerminalManagementInterface;
use Omniva\Shipping\Api\Data\ParcelTerminalInterfaceFactory;
use Omniva\Shipping\Model\Carrier;

class ParcelTerminalManagement implements ParcelTerminalManagementInterface
{

    protected $parcelTerminalFactory;
    protected $omnivaCarrier;

    /**
     * OfficeManagement constructor.
     * @param OfficeInterfaceFactory $officeInterfaceFactory
     */
    public function __construct(ParcelTerminalInterfaceFactory $parcelTerminalInterfaceFactory, Carrier $omnivaCarrier) {
        $this->parcelTerminalFactory = $parcelTerminalInterfaceFactory;
        $this->omnivaCarrier = $omnivaCarrier;
    }

    /**
     * Get offices for the given postcode and city
     *
     * @param string $postcode
     * @param string $limit
     * @param string $country
     * @param string $group
     * @return \Omniva\Shipping\Api\Data\OfficeInterface[]
     */
    public function fetchParcelTerminals($group, $city, $country) {
        $result = array();

        $locationsArray = $this->omnivaCarrier->getTerminals($country);
        if ($group) {
            foreach ($locationsArray as $loc_data) {
                if ($country != $loc_data['A0_NAME'] || $loc_data['TYPE'] == 1) {
                    continue;
                }
                $comment_language = 'lit';
                if ($country == "LV") {
                    $comment_language = 'lav';
                }
                if ($country == "EE") {
                    $comment_language = 'est';
                }
                $parcelTerminal = $this->parcelTerminalFactory->create();
                $parcelTerminal->setZip($loc_data['ZIP']);
                $parcelTerminal->setName($loc_data['NAME']);
                $parcelTerminal->setLocation($loc_data['A2_NAME']);
                $parcelTerminal->setX($loc_data['X_COORDINATE']);
                $parcelTerminal->setY($loc_data['Y_COORDINATE']);
                $terminalArray = array(
                    'zip' => $loc_data['ZIP'],
                    'name' => $loc_data['NAME'],
                    'location' => $loc_data['A2_NAME'],
                    'x' => $loc_data['X_COORDINATE'],
                    'y' => $loc_data['Y_COORDINATE'],
                    'comment' => $loc_data['comment_' . $comment_language],
                    'city' => $loc_data['A1_NAME'],
                );
                if (!isset($result[$loc_data['A1_NAME']])) {
                    $city_object = array('name' => $loc_data['A1_NAME'], 'terminals' => array());
                    $result[$loc_data['A1_NAME']] = $city_object;
                }
                $result[$loc_data['A1_NAME']]['terminals'][] = $terminalArray;
            }
        } else {
            foreach ($locationsArray as $loc_data) {
                if ($country != $loc_data['A0_NAME'] || $loc_data['TYPE'] == 1) {
                    continue;
                }
                $parcelTerminal = $this->parcelTerminalFactory->create();
                $parcelTerminal->setZip($loc_data['ZIP']);
                $parcelTerminal->setName($loc_data['NAME']);
                $parcelTerminal->setLocation($loc_data['A2_NAME']);
                $parcelTerminal->setX($loc_data['X_COORDINATE']);
                $parcelTerminal->setY($loc_data['Y_COORDINATE']);
                $result[] = $parcelTerminal;
            }
        }
        return $result;
    }

}
