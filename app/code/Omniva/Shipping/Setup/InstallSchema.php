<?php

namespace Omniva\Shipping\Setup;

use Magento\Framework\Setup\InstallSchemaInterface;
use Magento\Framework\Setup\ModuleContextInterface;
use Magento\Framework\Setup\SchemaSetupInterface;

/**
 * @codeCoverageIgnore
 */
class InstallSchema implements InstallSchemaInterface
{

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function install(SchemaSetupInterface $setup, ModuleContextInterface $context)
    {
        $installer = $setup;
        $installer->startSetup();

        if ($installer->getConnection()->tableColumnExists('quote_address', 'omniva_int_terminal') === false) {
            $installer->getConnection()->addColumn(
                $installer->getTable('quote_address'),
                'omniva_int_terminal',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Omniva International Parcel Terminal',
                ]
            );
        }

        if ($installer->getConnection()->tableColumnExists('sales_order_address', 'omniva_int_terminal') === false) {
            $installer->getConnection()->addColumn(
                $installer->getTable('sales_order_address'),
                'omniva_int_terminal',
                [
                    'type' => \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    'nullable' => true,
                    'comment' => 'Omniva International Parcel Terminal',
                ]
            );
        }

        if (!$installer->tableExists('omniva_int_terminals')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('omniva_int_terminals')
            )
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'ID'
                )
                ->addColumn(
                    'terminal_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['nullable => false'],
                    'Terminal id'
                )
                ->addColumn(
                    'name',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Name'
                )
                ->addColumn(
                    'city',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'City'
                )
                ->addColumn(
                    'country_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    10,
                    [],
                    'Country code'
                )
                ->addColumn(
                    'address',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Address'
                )
                ->addColumn(
                    'zip',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    [],
                    'ZIP'
                )
                ->addColumn(
                    'x_cord',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    20,
                    [],
                    'X'
                )
                ->addColumn(
                    'y_cord',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    20,
                    [],
                    'Y'
                )
                ->addColumn(
                    'comment',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Comment'
                )
                ->addColumn(
                    'identifier',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    50,
                    [],
                    'Identifier'
                )
                ->setComment('Omniva international terminals list');
            $installer->getConnection()->createTable($table);
     
        }

        if (!$installer->tableExists('omniva_int_orders')) {
            $table = $installer->getConnection()->newTable(
                $installer->getTable('omniva_int_orders')
            )
                ->addColumn(
                    'id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    [
                        'identity' => true,
                        'nullable' => false,
                        'primary' => true,
                        'unsigned' => true,
                    ],
                    'ID'
                )->addColumn(
                    'order_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_INTEGER,
                    null,
                    ['nullable => false'],
                    'Order id'
                )
                ->addColumn(
                    'service_code',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Service code'
                )
                ->addColumn(
                    'services',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Services'
                )
                ->addColumn(
                    'eori',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'EORI'
                )
                ->addColumn(
                    'shipment_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Shipment id'
                )
                ->addColumn(
                    'cart_id',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Cart id'
                )
                ->addColumn(
                    'identifier',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Identifier'
                )
                ->addColumn(
                    'tracking_numbers',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TEXT,
                    255,
                    [],
                    'Trackings'
                )
                ->addColumn(
                    'manifest_date',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => true, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Manifest date'
                )
                ->addColumn(
                    'created_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT],
                    'Created At'
                )->addColumn(
                    'updated_at',
                    \Magento\Framework\DB\Ddl\Table::TYPE_TIMESTAMP,
                    null,
                    ['nullable' => false, 'default' => \Magento\Framework\DB\Ddl\Table::TIMESTAMP_INIT_UPDATE],
                    'Updated At'
                )
                ->setComment('Omniva international orders');
            $installer->getConnection()->createTable($table);
        }

        $setup->endSetup();
    }
}
