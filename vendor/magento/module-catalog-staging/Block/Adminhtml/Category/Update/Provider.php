<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Block\Adminhtml\Category\Update;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Staging\Ui\Component\Listing\Column\Entity\UrlProviderInterface;

class Provider implements EntityProviderInterface
{
    /**
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var CategoryRepositoryInterface
     */
    protected $categoryRepository;

    /**
     * @var VersionManager
     */
    protected $versionManager;

    /**
     * Provider constructor.
     * @param RequestInterface $request
     * @param CategoryRepositoryInterface $categoryRepository
     * @param VersionManager $versionManager
     */
    public function __construct(
        RequestInterface $request,
        CategoryRepositoryInterface $categoryRepository,
        VersionManager $versionManager
    ) {
        $this->request = $request;
        $this->categoryRepository = $categoryRepository;
        $this->versionManager = $versionManager;
    }

    /**
     * {@inheritdoc}
     */
    public function getId()
    {
        try {
            return $this->getCategory()->getId();
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getUrl($updateId)
    {
        return null;
    }

    /**
     * @return \Magento\Catalog\Api\Data\CategoryInterface
     */
    protected function getCategory()
    {
        $category = $this->categoryRepository->get(
            $this->request->getParam('id'),
            $this->request->getParam('store')
        );
        return $category;
    }
}
