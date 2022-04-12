<?php

namespace Omniva\Shipping\Setup;
 
use Magento\Framework\Setup\UninstallInterface;
use Magento\Framework\Setup\SchemaSetupInterface;
use Magento\Framework\Setup\ModuleContextInterface;
 
class Uninstall implements UninstallInterface
{
    public function uninstall(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $setup->startSetup();
 
        $setup->getConnection()->dropColumn($setup->getTable('quote_address'), 'omniva_int_terminal');
        $setup->getConnection()->dropColumn($setup->getTable('sales_order_address'), 'omniva_int_terminal');
        $setup->getConnection()->dropTable($setup->getTable('omniva_int_terminals'));
        $setup->getConnection()->dropTable($setup->getTable('omniva_int_orders'));
        //$setup->getConnection()->dropColumn($setup->getTable('sales_order'), 'manifest_generation_date');
 
        $setup->endSetup();
    }
}