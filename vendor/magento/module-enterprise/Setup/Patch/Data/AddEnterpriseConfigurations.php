<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Enterprise\Setup\Patch\Data;

use Magento\Framework\Setup\Patch\PatchVersionInterface;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Framework\Setup\Patch\SchemaPatchInterface;
use Magento\Framework\Setup\Patch\PatchRevertableInterface;
use Magento\Framework\Setup\ModuleDataSetupInterface;

/**
 * Patch is mechanism, that allows to do atomic upgrade data changes
 */
class AddEnterpriseConfigurations implements
    DataPatchInterface,
    PatchVersionInterface
{
    /**
     * @var ModuleDataSetupInterface $moduleDataSetup
     */
    private $moduleDataSetup;

    /**
     * @param ModuleDataSetupInterface $moduleDataSetup
     */
    public function __construct(ModuleDataSetupInterface $moduleDataSetup)
    {
        $this->moduleDataSetup = $moduleDataSetup;
    }

    /**
     * Do Upgrade
     *
     * @return void
     */
    public function apply()
    {
        $map = [
            'admin/cms/magento_banner' => 'Magento_Banner::magento_banner',
            'admin/catalog/events' => 'Magento_CatalogEvent::events',
            'admin/catalog/magento_catalogpermissions' =>
                'Magento_CatalogPermissions::catalog_magento_catalogpermissions',
            'admin/system/config/magento_catalogpermissions' =>
                'Magento_CatalogPermissions::magento_catalogpermissions',
            'admin/sales/magento_advancedcheckout' => 'Magento_AdvancedCheckout::magento_advancedcheckout',
            'admin/sales/magento_advancedcheckout/update' => 'Magento_AdvancedCheckout::update',
            'admin/sales/magento_advancedcheckout/view' => 'Magento_AdvancedCheckout::view',
            'admin/cms/page/delete_revision' => 'Magento_VersionsCms::delete_revision',
            'admin/cms/hierarchy' => 'Magento_VersionsCms::hierarchy',
            'admin/cms/page/publish_revision' => 'Magento_VersionsCms::publish_revision',
            'admin/cms/page/save_revision' => 'Magento_VersionsCms::save_revision',
            'admin/customer/attributes' => 'Magento_CustomerCustomAttributes::attributes',
            'admin/customer/attributes/customer_address_attributes' =>
                'Magento_CustomerCustomAttributes::customer_address_attributes',
            'admin/customer/attributes/customer_attributes' => 'Magento_CustomerCustomAttributes::customer_attributes',
            'admin/customer/customersegment' => 'Magento_CustomerSegment::customersegment',
            'admin/report/customers/segment' => 'Magento_CustomerSegment::segment',
            'admin/system/config/giftcard' => 'Magento_GiftCard::giftcard',
            'admin/customer/giftcardaccount' => 'Magento_GiftCardAccount::customer_giftcardaccount',
            'admin/system/config/giftcardaccount' => 'Magento_GiftCardAccount::giftcardaccount',
            'admin/customer/magento_giftregistry' => 'Magento_GiftRegistry::customer_magento_giftregistry',
            'admin/system/config/magento_giftregistry' => 'Magento_GiftRegistry::magento_giftregistry',
            'admin/sales/magento_giftwrapping' => 'Magento_GiftWrapping::magento_giftwrapping',
            'admin/system/convert/enterprise_scheduled_operation' =>
                'Magento_ScheduledImportExport::magento_scheduled_operation',
            'admin/system/config/magento_invitation' => 'Magento_Invitation::config_magento_invitation',
            'admin/customer/magento_invitation' => 'Magento_Invitation::magento_invitation',
            'admin/report/magento_invitation/customer' => 'Magento_Invitation::magento_invitation_customer',
            'admin/report/magento_invitation/general' => 'Magento_Invitation::general',
            'admin/report/magento_invitation/order' => 'Magento_Invitation::order',
            'admin/report/magento_invitation' => 'Magento_Invitation::report_magento_invitation',
            'admin/system/magento_logging/backups' => 'Magento_Logging::backups',
            'admin/system/magento_logging' => 'Magento_Logging::magento_logging',
            'admin/system/magento_logging/events' => 'Magento_Logging::magento_logging_events',
            'admin/system/config/logging' => 'Magento_Logging::logging',
            'admin/catalog/products/read_product_price/edit_product_price' =>
                'Magento_PricePermissions::edit_product_price',
            'admin/catalog/products/edit_product_status' => 'Magento_PricePermissions::edit_product_status',
            'admin/catalog/products/read_product_price' => 'Magento_PricePermissions::read_product_price',
            'admin/promo/catalog/edit' => 'Magento_PromotionPermissions::edit',
            'admin/promo/magento_reminder/edit' => 'Magento_PromotionPermissions::magento_reminder_edit',
            'admin/promo/quote/edit' => 'Magento_PromotionPermissions::quote_edit',
            'admin/promo/magento_reminder' => 'Magento_Reminder::magento_reminder',
            'admin/system/config/magento_reward' => 'Magento_Reward::magento_reward',
            'admin/customer/rates' => 'Magento_Reward::rates',
            'admin/customer/manage/reward_balance' => 'Magento_Reward::reward_balance',
            'admin/sales/order/actions/create/reward_spend' => 'Magento_Reward::reward_spend',
            'admin/sales/magento_rma' => 'Magento_Rma::magento_rma',
            'admin/sales/magento_rma/rma_attribute' => 'Magento_Rma::rma_attribute',
            'admin/sales/magento_rma/rma_manage' => 'Magento_Rma::rma_manage',
            'admin/sales/archive/orders/add' => 'Magento_SalesArchive::add',
            'admin/sales/archive' => 'Magento_SalesArchive::archive',
            'admin/sales/archive/creditmemos' => 'Magento_SalesArchive::creditmemos',
            'admin/sales/archive/invoices' => 'Magento_SalesArchive::invoices',
            'admin/sales/archive/orders' => 'Magento_SalesArchive::orders',
            'admin/sales/archive/orders/remove' => 'Magento_SalesArchive::remove',
            'admin/sales/archive/shipments' => 'Magento_SalesArchive::shipments',
            'admin/catalog/targetrule' => 'Magento_TargetRule::targetrule',
            'admin/report/customers/wishlist' => 'Magento_MultipleWishlist::wishlist',
        ];

        $tableName = $this->moduleDataSetup->getTable('authorization_rule');
        $connection = $this->moduleDataSetup->getConnection();

        if ($tableName) {
            $select = $connection->select();
            $select->from($tableName, [])->columns(['resource_id' => 'resource_id'])->group('resource_id');

            foreach ($connection->fetchCol($select) as $oldKey) {
                /**
                 * If used ACL key is converted previously or we haven't map for specified ACL resource item
                 * than go to the next item
                 */
                if (in_array($oldKey, $map) || false == isset($map[$oldKey])) {
                    continue;
                }

                /** Update rule ACL key from xpath format to identifier format */
                $connection->update($tableName, ['resource_id' => $map[$oldKey]], ['resource_id = ?' => $oldKey]);
            }
        }
        $tableName = $this->moduleDataSetup->getTable('authorization_rule');
        $condition = $connection->prepareSqlCondition(
            'resource_id',
            [['like' => '%content_staging%'], ['like' => '%enterprise_staging%']]
        );
        $connection->delete($tableName, $condition);
        $tableName = $this->moduleDataSetup->getTable('authorization_rule');
        if ($tableName) {
            $connection = $this->moduleDataSetup->getConnection();
            $remove = ['Magento_Rma::rma_manage'];
            $connection->delete($tableName, ['resource_id IN (?)' => $remove]);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getAliases()
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    public static function getDependencies()
    {
        return [

        ];
    }

    /**
     * {@inheritdoc}
     */
    public static function getVersion()
    {
        return '2.0.0';
    }
}
