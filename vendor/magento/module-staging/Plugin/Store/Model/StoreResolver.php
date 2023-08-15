<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Plugin\Store\Model;

use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Plugin for store resolver.
 */
class StoreResolver
{
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var \Magento\Staging\Model\VersionManager
     */
    private $versionManager;

    /**
     * @var \Magento\Store\Api\StoreRepositoryInterface
     */
    private $storeRepository;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     * @param \Magento\Staging\Model\VersionManager $versionManager
     * @param \Magento\Store\Api\StoreRepositoryInterface $storeRepository
     */
    public function __construct(
        \Magento\Framework\App\RequestInterface $request,
        \Magento\Staging\Model\VersionManager $versionManager,
        \Magento\Store\Api\StoreRepositoryInterface $storeRepository
    ) {
        $this->request = $request;
        $this->versionManager = $versionManager;
        $this->storeRepository = $storeRepository;
    }

    /**
     * Around plugin for GetCurrentStoreId
     *
     * @param \Magento\Store\Api\StoreResolverInterface $subject
     * @param \Closure $proceed
     *
     * @return string
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function aroundGetCurrentStoreId(
        \Magento\Store\Api\StoreResolverInterface $subject,
        \Closure $proceed
    ) {
        if ($this->versionManager->isPreviewVersion()) {
            $storeCode = $this->request->getParam(
                \Magento\Store\Model\StoreManagerInterface::PARAM_NAME
            );

            if ($storeCode) {
                try {
                    $store = $this->storeRepository->get($storeCode);

                    if ($store->isActive()) {
                        return $store->getId();
                    }
                } catch (NoSuchEntityException $e) {
                    return $proceed();
                }
            }
        }

        return $proceed();
    }
}
