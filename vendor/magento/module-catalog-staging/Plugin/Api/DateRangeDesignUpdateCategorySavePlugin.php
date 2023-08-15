<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogStaging\Plugin\Api;

use Magento\Catalog\Api\CategoryRepositoryInterface;
use Magento\Catalog\Api\Data\CategoryInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionManager;

/**
 * Category Save Plugin updates date range
 */
class DateRangeDesignUpdateCategorySavePlugin
{
    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var string
     */
    private static $designFromKey = 'custom_design_from';

    /**
     * @var string
     */
    private static $designToKey = 'custom_design_to';

    /**
     * DateRangeDesignUpdateCategorySavePlugin constructor.
     *
     * @param UpdateRepositoryInterface $updateRepository
     */
    public function __construct(
        UpdateRepositoryInterface $updateRepository
    ) {
        $this->updateRepository = $updateRepository;
    }

    /**
     * Before save
     *
     * @param CategoryRepositoryInterface $subject
     * @param CategoryInterface $category
     * @return void
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function beforeSave(CategoryRepositoryInterface $subject, CategoryInterface $category)
    {
        $createdIn = $category->getCreatedIn();
        $updatedIn = $category->getUpdatedIn();

        if (null != $createdIn && $createdIn != VersionManager::MIN_VERSION) {
            $designUpdate = $this->updateRepository->get($createdIn);
            $category->setCustomAttribute(
                self::$designFromKey,
                $designUpdate->getStartTime()
            );
        }

        if (null != $updatedIn && $updatedIn != VersionManager::MAX_VERSION) {
            if (isset($designUpdate) && $designUpdate->getRollbackId() != $updatedIn) {
                return;
            }
            $category->setCustomAttribute(
                self::$designToKey,
                $this->updateRepository->get($updatedIn)->getStartTime()
            );
        }
    }
}
