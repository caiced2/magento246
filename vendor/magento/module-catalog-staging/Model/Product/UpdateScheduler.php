<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product;

/**
 * UpdateScheduler is responsible for creating update for product.
 */
class UpdateScheduler
{
    /**
     * @var \Magento\Staging\Controller\Adminhtml\Entity\Update\Service
     */
    private $updateService;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @var \Magento\CatalogStaging\Api\ProductStagingInterface
     */
    private $productStaging;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @param \Magento\Staging\Controller\Adminhtml\Entity\Update\Service $updateService
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param \Magento\CatalogStaging\Api\ProductStagingInterface $productStaging
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     */
    public function __construct(
        \Magento\Staging\Controller\Adminhtml\Entity\Update\Service $updateService,
        \Magento\Staging\Model\VersionManager $versionManager,
        \Magento\CatalogStaging\Api\ProductStagingInterface $productStaging,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
    ) {
        $this->updateService = $updateService;
        $this->versionManager = $versionManager;
        $this->productStaging = $productStaging;
        $this->productRepository = $productRepository;
    }

    /**
     * Schedule update for product.
     *
     * @param string $sku
     * @param array $stagingData
     * @param int $storeId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function schedule($sku, array $stagingData, $storeId = 0)
    {
        $update = $this->updateService->createUpdate($stagingData);
        $this->versionManager->setCurrentVersionId($update->getId());
        $product = $this->productRepository->get(
            $sku,
            true,
            $storeId
        );
        $this->productStaging->schedule(
            $product,
            $update->getId()
        );

        return true;
    }
}
