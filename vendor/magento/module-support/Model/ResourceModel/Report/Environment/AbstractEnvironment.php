<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Support\Model\ResourceModel\Report\Environment;

/**
 * Base class for subclasses of environment report
 */
abstract class AbstractEnvironment
{
    /**
     * @var array
     */
    protected $phpInfoCollection;

    /**
     * @var PhpInfo
     */
    protected $phpInfo;

    /**
     * @var \Magento\Framework\DB\Adapter\AdapterInterface
     */
    protected $resourceConnection;

    /**
     * @var \Psr\Log\LoggerInterface
     */
    protected $logger;

    /**
     * @param \Psr\Log\LoggerInterface $logger
     * @param PhpInfo $phpInfo
     * @param \Magento\Framework\Module\ModuleResource $resource
     */
    public function __construct(
        \Psr\Log\LoggerInterface $logger,
        PhpInfo $phpInfo,
        \Magento\Framework\Module\ModuleResource $resource
    ) {
        $this->logger = $logger;
        $this->phpInfo = $phpInfo;
        $this->phpInfoCollection = $this->phpInfo->getCollectPhpInfo();
        $this->resourceConnection = $resource->getConnection();
    }

    /**
     * Check array of phpinfo data
     *
     * @return bool
     */
    protected function checkPhpInfo()
    {
        return (is_array($this->phpInfoCollection) && !empty($this->phpInfoCollection));
    }
}
