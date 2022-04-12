<?php

namespace Omniva\Shipping\Api;

interface ParcelTerminalManagementInterface
{

    /**
     * Find parcel terminals for the customer
     *
     * @param string $postcode
     * @param string $city
     * @param string $country
     * @return \Omniva\Shipping\Api\Data\ParcelTerminalInterface[]
     */
    public function fetchParcelTerminals($group, $city, $country );
}