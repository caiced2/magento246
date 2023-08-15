<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\Report\Group\Data;

use Magento\Framework\EntityManager\MetadataPool;

/**
 * General abstract class of Data Report
 */
abstract class AbstractDataGroup extends \Magento\Support\Model\Report\Group\AbstractSection
{
    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $connection;

    /**
     * @var \Magento\Framework\Module\ModuleResource
     */
    protected $resource;

    /**
     * @var \Magento\Eav\Model\Config
     */
    protected $eavConfig;

    /**
     * @var MetadataPool
     */
    protected $metadataPool;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param \Magento\Framework\Module\ModuleResource $resource
     * @param \Magento\Eav\Model\ConfigFactory $eavConfigFactory
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        \Magento\Framework\Module\ModuleResource $resource,
        \Magento\Eav\Model\ConfigFactory $eavConfigFactory,
        MetadataPool $metadataPool
    ) {
        $this->resource = $resource;
        $this->connection = $resource->getConnection();
        $this->eavConfig = $eavConfigFactory->create();
        $this->metadataPool = $metadataPool;
        parent::__construct($logger);
    }
}
