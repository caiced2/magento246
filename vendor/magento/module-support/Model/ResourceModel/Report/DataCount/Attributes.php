<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\DataCount;

/**
 * Class Attributes
 */
class Attributes
{
    /**
     * @var \Magento\Eav\Model\ConfigFactory
     */
    protected $eavConfigFactory;

    /**
     * @param \Magento\Eav\Model\ConfigFactory $eavConfigFactory
     */
    public function __construct(
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory
    ) {
        $this->eavConfigFactory = $eavConfigFactory;
    }

    /**
     * Collect catalog attributes information
     *
     * @param string $type
     * @param \Magento\Framework\Model\ResourceModel\Db\AbstractDb $resource
     * @return array
     * @throws \Exception
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getAttributesCount($type, $resource)
    {
        $connection = $resource->getConnection();
        $attributesData = $this->generateAttributesData($type, $connection);

        /** @var \Magento\Eav\Model\Entity\Type $entityType */
        $entityType = $this->eavConfigFactory->create()->getEntityType($attributesData['entityTypeCode']);
        $entityTypeId = (int)$entityType->getId();

        $eavTableName = $resource->getTable('eav_attribute');
        $flagColumns = implode(', ', $attributesData['flagColumns']);
        $info = $connection->fetchAll(
            "SELECT ea.`backend_type`, ea.`is_user_defined`, {$flagColumns}
            FROM `{$attributesData['eavMainTable']}` `main_table`
            INNER JOIN `{$eavTableName}` ea ON
                (ea.`attribute_id` = main_table.`attribute_id` AND ea.`entity_type_id` = '{$entityTypeId}')"
        );

        $result = $this->generateAttributesResult($info, $attributesData['title']);
        return $result;
    }

    /**
     * Generate Catalog Attributes information
     *
     * @param string $type
     * @param \Magento\Framework\DB\Adapter\AdapterInterface $connection
     * @return array
     * @throws \Exception
     */
    protected function generateAttributesData($type, \Magento\Framework\DB\Adapter\AdapterInterface $connection)
    {
        $data = [];

        switch ($type) {
            case 'customer':
                $data['title'] = 'Customer Attributes';
                $data['entityTypeCode'] = \Magento\Customer\Api\CustomerMetadataInterface::ENTITY_TYPE_CUSTOMER;
                $data['flagColumns'] = [
                    'main_table.`is_system`',
                    'main_table.`is_used_for_customer_segment`',
                    'main_table.`is_visible`',
                ];
                $data['eavMainTable'] = $connection->getTableName('customer_eav_attribute');
                break;
            case 'customer_address':
                $data['title'] = 'Customer Address Attributes';
                $data['entityTypeCode'] = \Magento\Customer\Api\AddressMetadataInterface::ENTITY_TYPE_ADDRESS;
                $data['flagColumns'] = [
                    'main_table.`is_system`',
                    'main_table.`is_used_for_customer_segment`',
                    'main_table.`is_visible`',
                ];
                $data['eavMainTable'] = $connection->getTableName('customer_eav_attribute');
                break;
            case 'category':
                $data['title'] = 'Category Attributes';
                $data['entityTypeCode'] = \Magento\Catalog\Api\Data\CategoryAttributeInterface::ENTITY_TYPE_CODE;
                $data['flagColumns'] = [
                    'main_table.`is_visible_on_front`',
                    'main_table.`is_used_for_promo_rules`'
                ];
                $data['eavMainTable'] = $connection->getTableName('catalog_eav_attribute');
                break;
            case 'product':
                $data['title'] = 'Product Attributes';
                $data['entityTypeCode'] = \Magento\Catalog\Api\Data\ProductAttributeInterface::ENTITY_TYPE_CODE;
                $data['flagColumns'] = [
                    'main_table.`is_visible_on_front`',
                    'main_table.`is_searchable`',
                    'main_table.`is_filterable`',
                    'main_table.`is_used_for_promo_rules`'
                ];
                $data['eavMainTable'] = $connection->getTableName('catalog_eav_attribute');
                break;
            default:
                throw new \Exception(
                    'getAttributesInfo() doesn\'t support specified attributes entity type: "' . (string)$type . '".'
                    . ' Count data can\'t be retrieved.'
                );
                break;
        }

        return $data;
    }

    /**
     * Generate Catalog Attributes result
     *
     * @param array $info
     * @param string $title
     * @return array
     */
    protected function generateAttributesResult(array $info, $title)
    {
        $result = [];

        if ($info) {
            list($byType, $extra) = $this->generateTypeAndExtra($info);

            $extra1 = $extra2 = '';
            foreach ($extra as $key => $num) {
                $extra1 .= $key . ': ' . $num . '; ';
            }
            foreach ($byType as $key => $num) {
                $extra2 .= $key . ': ' . $num . '; ';
            }
            $result[] = [$title, sizeof($info), 'Attributes Flags: ' . $extra1];
            $result[] = ['', '', 'Attributes Types: ' . $extra2];
        } else {
            $result[] = [$title, 0];
        }

        return $result;
    }

    /**
     * Generate arrays by type and extra information
     *
     * @param array $info
     * @return array
     */
    protected function generateTypeAndExtra(array $info)
    {
        $byType = $extra = [];

        foreach ($info as $data) {
            foreach (array_keys($data) as $key) {
                if ($key == 'backend_type') {
                    if (!isset($byType[$data[$key]])) {
                        $byType[$data[$key]] = 0;
                    }
                    $byType[$data[$key]]++;
                } else {
                    if (!isset($extra[$key])) {
                        $extra[$key] = 0;
                    }
                    if ($data[$key]) {
                        $extra[$key]++;
                    }
                }
            }
        }

        return [$byType, $extra];
    }
}
