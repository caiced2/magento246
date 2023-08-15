<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\CatalogStagingGraphQl\Model\Resolver;

use Magento\Catalog\Model\Category;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\GraphQl\Config\Element\Field;
use Magento\Framework\GraphQl\Query\Resolver\ValueFactory;
use Magento\Framework\GraphQl\Query\ResolverInterface;
use Magento\Framework\GraphQl\Schema\Type\ResolveInfo;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * Resolve staged status of category for preview queries
 */
class CategoryStagingStatus implements ResolverInterface
{
    /**
     * @var VersionManager
     */
    private $versionManager;

    /**
     * @var ValueFactory
     */
    private $valueFactory;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var array
     */
    private $stagedCategoryIds = [];

    /**
     * @param VersionManager $versionManager
     * @param ValueFactory $valueFactory
     * @param DateTime $dateTime
     * @param UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        VersionManager $versionManager,
        ValueFactory $valueFactory,
        DateTime $dateTime,
        UpdateRepositoryInterface $updateRepository
    ) {
        $this->versionManager = $versionManager;
        $this->valueFactory = $valueFactory;
        $this->dateTime = $dateTime;
        $this->updateRepository = $updateRepository;
    }

    /**
     * @inheritdoc
     */
    public function resolve(Field $field, $context, ResolveInfo $info, array $value = null, array $args = null)
    {
        if (!$this->versionManager->isPreviewVersion()) {
            return false;
        }
        /** @var Category $category */
        $category = $value['model'];

        if ($this->isStagedCategoryVersion($category)) {
            $this->stagedCategoryIds[$category->getId()] = true;
        }

        return $this->valueFactory->create(function () use ($category) {
            $isStaged = $this->stagedCategoryIds[$category->getId()] ?? false;
            return $isStaged;
        });
    }

    /**
     * Check if this is staged version of the category
     *
     * @param Category $category
     * @return bool
     */
    private function isStagedCategoryVersion(Category $category)
    {
        $currentTime = $this->dateTime->gmtTimestamp();
        $currentCategoryVersion = $category->getData('created_in');
        if ($currentCategoryVersion < $currentTime) {
            return false;
        }

        try {
            $categoryUpdate = $this->updateRepository->get($currentCategoryVersion);
        } catch (NoSuchEntityException $e) {
            return false;
        }

        if ($categoryUpdate->getIsRollback()) {
            return false;
        }
        return true;
    }
}
