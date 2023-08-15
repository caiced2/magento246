<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogStaging\Model;

use Magento\CatalogStaging\Api\ProductStagingInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Class ProductStaging
 */
class ProductStaging implements ProductStagingInterface
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
     * ProductStaging constructor.
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
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $version
     * @param array $arguments
     * @return bool
     * @throws ValidatorException
     */
    public function schedule(\Magento\Catalog\Api\Data\ProductInterface $product, $version, $arguments = [])
    {
        $previous = isset($arguments['origin_in']) ? $arguments['origin_in'] : null;
        if (!$this->campaignValidator->canBeScheduled($product, $version, $previous)) {
            throw new ValidatorException(
                __('Future Update already exists in this time range. Set a different range and try again.')
            );
        }
        $arguments['created_in'] = $version;
        $arguments['store_id'] = $this->storeManager->getStore()->getId();
        return (bool)$this->entityManager->save($product, $arguments);
    }

    /**
     * @param \Magento\Catalog\Api\Data\ProductInterface $product
     * @param string $version
     * @return bool
     */
    public function unschedule(\Magento\Catalog\Api\Data\ProductInterface $product, $version)
    {
        return (bool)$this->entityManager->delete(
            $product,
            [
                'store_id' => $this->storeManager->getStore()->getId(),
                'created_in' => $version
            ]
        );
    }
}
