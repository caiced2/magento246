<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Mview\View;

use Magento\Framework\Mview\View\SubscriptionFactory as FrameworkSubscriptionFactory;

class SubscriptionFactory extends FrameworkSubscriptionFactory
{
    /**
     * @var array
     * @deprecated 100.1.0
     */
    private $stagingEntityTables = ['catalog_product_entity', 'catalog_category_entity'];

    /**
     * @var array
     * @deprecated 100.1.0
     */
    private $versionTables;

    /**
     * @var string[]
     */
    private $subscriptionModels = [];

    /**
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param \Magento\CatalogStaging\Model\VersionTables $versionTables
     * @param array $subscriptionModels
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        \Magento\CatalogStaging\Model\VersionTables $versionTables,
        $subscriptionModels = []
    ) {
        parent::__construct($objectManager);
        $this->versionTables = $versionTables;
        $this->subscriptionModels = $subscriptionModels;
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data = [])
    {
        if (isset($data['tableName']) && isset($this->subscriptionModels[$data['tableName']])) {
            $data['subscriptionModel'] = $this->subscriptionModels[$data['tableName']];
        }
        return parent::create($data);
    }

    /**
     * @param array $data
     * @return bool
     * @deprecated 100.1.10
     */
    protected function isStagingTable(array $data = [])
    {
        if (empty($data['tableName']) || in_array($data['tableName'], $this->stagingEntityTables)) {
            return false;
        }
        if (in_array($data['tableName'], $this->versionTables->getVersionTables())) {
            return true;
        }
        return false;
    }
}
