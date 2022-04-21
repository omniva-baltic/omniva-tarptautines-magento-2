<?php

namespace Omniva\Shipping\Cron;

use Omniva\Shipping\Model\Carrier;
use Omniva\Shipping\Model\OmnivaOrderFactory;

class OrderCheck
{
    protected $omnivaCarrier;
    protected $omnivaOrderFactory;

	public function __construct(
		Carrier $omnivaCarrier,
		OmnivaOrderFactory $omnivaOrderFactory
	) {
		$this->omnivaCarrier = $omnivaCarrier;
		$this->omnivaOrderFactory = $omnivaOrderFactory;
	}

	public function execute() {
		$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/cron.log');
		$logger = new \Zend\Log\Logger();
		$logger->addWriter($writer);
		try {
			$orders = $this->omnivaOrderFactory->create()->getCollection() ->addFieldToSelect('*');
			$orders->addFieldToFilter('shipment_id', array(['notnull' => true]))->addFieldToFilter('tracking_numbers', array(['null' => true]));
			$logger->info('Found orders: ' . count($orders));
			
			foreach ($orders as $order) {
				$response = $this->omnivaCarrier->getOmnivaOrderLabel($order);
				$logger->info(json_encode($response));
			}
		} catch (\Throwable $e) {
			$logger->info($e->getMessage());
		}

		return $this;

	}
}
