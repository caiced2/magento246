<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Model\Product\Locator;

class StagingLocator implements \Magento\Catalog\Model\Locator\LocatorInterface
{
    /**
     * @var \Magento\Catalog\Api\Data\ProductInterface
     */
    private $product;

    /**
     * @var \Magento\Store\Api\Data\StoreInterface
     */
    private $store;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @var \Magento\Staging\Api\UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var string
     */
    private $requestFieldName;

    /**
     * @var \Magento\Catalog\Api\ProductRepositoryInterface
     */
    private $productRepository;

    /**
     * @var \Magento\Store\Model\StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var \Magento\Framework\Registry
     */
    private $registry;

    /**
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param string $requestFieldName
     */
    public function __construct(
        \Magento\Framework\Registry $registry,
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Staging\Model\VersionManager $versionManager,
        \Magento\Staging\Api\UpdateRepositoryInterface $updateRepository,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        $requestFieldName
    ) {
        $this->registry = $registry;
        $this->request = $request;
        $this->versionManager = $versionManager;
        $this->updateRepository = $updateRepository;
        $this->productRepository = $productRepository;
        $this->storeManager = $storeManager;
        $this->requestFieldName = $requestFieldName;
    }

    /**
     * {@inheritDoc}
     */
    public function getProduct()
    {
        if ($this->product) {
            return $this->product;
        }

        $updateId = $this->request->getParam('update_id', null);
        $entityId = $this->request->getParam($this->requestFieldName, null);

        if (null !== $updateId && null !== $entityId) {
            try {
                $update = $this->updateRepository->get($updateId);
                $this->versionManager->setCurrentVersionId($update->getId());
            } catch (\Magento\Framework\Exception\NoSuchEntityException $e) {
            }
            // Retrieve product update according provided version
            $this->product = $this->productRepository->getById($entityId, true, $this->getStore()->getId());
        } else {
            // Retrieve current product version from registry or load from repository
            $this->product = $this->registry->registry('current_product');
            if (null == $this->product) {
                $this->product = $this->productRepository->getById($entityId, true, $this->getStore()->getId());
            }
        }
        return $this->product;
    }

    /**
     * {@inheritDoc}
     */
    public function getStore()
    {
        $this->store = $this->registry->registry('current_store');

        if (!$this->store) {
            $this->store = $this->storeManager->getStore((int)$this->request->getParam('store', 0));
        }
        return $this->store;
    }

    /**
     * {@inheritDoc}
     */
    public function getWebsiteIds()
    {
        return $this->getProduct()->getWebsiteIds();
    }

    /**
     * {@inheritDoc}
     */
    public function getBaseCurrencyCode()
    {
        return $this->getStore()->getBaseCurrencyCode();
    }
}
