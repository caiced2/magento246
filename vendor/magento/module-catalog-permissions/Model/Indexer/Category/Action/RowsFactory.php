<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogPermissions\Model\Indexer\Category\Action;

/**
 * Factory class for \Magento\CatalogPermissions\Model\Indexer\Category\Action\Rows
 * @api
 * @since 100.0.2
 */
class RowsFactory
{
    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * Instance name to create
     *
     * @var string
     */
    protected $instanceName;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string $instanceName
     */
    public function __construct(
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Magento\CatalogPermissions\Model\Indexer\Category\Action\Rows::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName = $instanceName;
    }

    /**
     * Create class instance with specified parameters
     *
     * @param array $data
     * @throws \InvalidArgumentException
     * @return \Magento\CatalogPermissions\Model\Indexer\AbstractAction
     */
    public function create(array $data = [])
    {
        /** @var \Magento\CatalogPermissions\Model\Indexer\AbstractAction $instance */
        $instance = $this->objectManager->create($this->instanceName, $data);
        if (!$instance instanceof \Magento\CatalogPermissions\Model\Indexer\AbstractAction) {
            throw new \InvalidArgumentException(
                $this->instanceName . ' is not instance of \Magento\CatalogPermissions\Model\Indexer\AbstractAction'
            );
        }
        return $instance;
    }
}
