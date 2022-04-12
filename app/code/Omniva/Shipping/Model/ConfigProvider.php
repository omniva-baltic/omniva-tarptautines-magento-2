<?php

namespace Omniva\Shipping\Model;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class SampleConfigProvider
 */
class ConfigProvider implements ConfigProviderInterface
{
    protected $scopeConfig;

    public function __construct(
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
    ) {
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Retrieve assoc array of checkout configuration
     *
     * @return array
     */
    public function getConfig()
    {

        $config = [
            'omnivaGlobalData' => [
                'distance' => $this->scopeConfig->getValue('carriers/omniva_global/omniva_methods_group/terminal_distance') ?? 2,
                'apiUrl' => $this->scopeConfig->getValue('carriers/omniva_global/production_webservices_url'),
            ]
        ];

        return $config;
    }
}
