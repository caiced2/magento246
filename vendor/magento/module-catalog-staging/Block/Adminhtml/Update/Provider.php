<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Block\Adminhtml\Update;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\CatalogStaging\Ui\Component\Listing\Column\Product\UrlProvider;

class Provider implements EntityProviderInterface
{
    /**
     * @var ProductRepositoryInterface
     */
    protected $productRepository;

    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var UrlProvider
     */
    private $previewUrlProvider;

    /**
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $ruleRepository
     * @param VersionManager $versionManager
     * @param UrlProvider $previewUrlProvider
     */
    public function __construct(
        RequestInterface $request,
        ProductRepositoryInterface $ruleRepository,
        VersionManager $versionManager,
        UrlProvider $previewUrlProvider
    ) {
        $this->productRepository = $ruleRepository;
        $this->request = $request;
        $this->versionManager = $versionManager;
        $this->previewUrlProvider = $previewUrlProvider;
    }

    /**
     * @return int|null
     */
    public function getId()
    {
        try {
            $product = $this->productRepository->getById($this->request->getParam('id'));
            return $product->getId();
        } catch (NoSuchEntityException $e) {
        }
        return null;
    }

    /**
     * @param int $updateId
     * @return null
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($updateId)
    {
        try {
            $oldUpdateId = $this->versionManager->getCurrentVersion()->getId();
            $this->versionManager->setCurrentVersionId($updateId);
            $product = $this->productRepository->getById($this->request->getParam('id'));
            $url = $this->previewUrlProvider->getUrl($product->getData());
            $this->versionManager->setCurrentVersionId($oldUpdateId);

            return $url;
        } catch (NoSuchEntityException $e) {
        }

        return null;
    }
}
