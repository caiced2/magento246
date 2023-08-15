<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model;

use Magento\CatalogStaging\Api\CategoryStagingInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;
use Magento\Framework\Exception\ValidatorException;

/**
 * Class CategoryStaging
 */
class CategoryStaging implements CategoryStagingInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * CategoryStaging constructor.
     *
     * @param EntityManager $entityManager
     * @param StoreManagerInterface $storeManager
     * @param CampaignValidator $campaignValidator
     */
    public function __construct(
        EntityManager $entityManager,
        StoreManagerInterface $storeManager,
        CampaignValidator $campaignValidator
    ) {
        $this->entityManager = $entityManager;
        $this->storeManager = $storeManager;
        $this->campaignValidator = $campaignValidator;
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     * @param string $version
     * @param array $arguments
     * @return bool
     * @throws \Exception
     */
    public function schedule(\Magento\Catalog\Api\Data\CategoryInterface $category, $version, $arguments = [])
    {
        $previous = isset($arguments['origin_in']) ? $arguments['origin_in'] : null;
        if (!$this->campaignValidator->canBeScheduled($category, $version, $previous)) {
            throw new ValidatorException(
                __('Future Update already exists in this time range. Set a different range and try again.')
            );
        }
        $arguments['store_id'] = $this->storeManager->getStore()->getId();
        $arguments['created_in'] = $version;
        $category->setDataChanges(true);
        return (bool)$this->entityManager->save($category, $arguments);
    }

    /**
     * @param \Magento\Catalog\Api\Data\CategoryInterface $category
     * @param string $version
     * @return bool
     */
    public function unschedule(\Magento\Catalog\Api\Data\CategoryInterface $category, $version)
    {
        return (bool)$this->entityManager->delete(
            $category,
            [
                'store_id' => $this->storeManager->getStore()->getId(),
                'created_in' => $version
            ]
        );
    }
}
