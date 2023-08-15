<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Reward\Model\SalesRule;

use Magento\Framework\EntityManager\MetadataPool;
use Magento\Framework\EntityManager\Operation\ExtensionInterface;
use Magento\Reward\Helper\Data as Helper;
use Magento\Reward\Model\ResourceModel\Reward;
use Magento\SalesRule\Api\Data\RuleInterface;
use Magento\SalesRule\Model\Rule;

/**
 * An extension handler to write Reward points for Sales Rule
 *
 * Handler writes Reward Points value into related table for Sales Rule from extension attributes
 */
class SaveHandler implements ExtensionInterface
{
    /**
     * @var Helper
     */
    private $helper;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var Reward
     */
    private $rewardResource;

    /**
     * @param Helper $helper
     * @param MetadataPool $metadataPool
     * @param Reward $rewardResource
     */
    public function __construct(
        Helper $helper,
        MetadataPool $metadataPool,
        Reward $rewardResource
    ) {
        $this->helper = $helper;
        $this->metadataPool = $metadataPool;
        $this->rewardResource = $rewardResource;
    }

    /**
     * Stores Reward Points value from Sales Rule extension attributes
     *
     * @param Rule|object $entity
     * @param array $arguments
     * @return Rule|object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function execute($entity, $arguments = [])
    {
        /** @var $entity Rule */
        if (!$this->helper->isEnabled()) {
            return $entity;
        }

        $attributes = $entity->getExtensionAttributes() ?: [];
        $metadata = $this->metadataPool->getMetadata(RuleInterface::class);
        if ($entity->getData($metadata->getLinkField())) {
            $pointsDelta = $attributes['reward_points_delta'] ?? $entity->getRewardPointsDelta();
            if ($pointsDelta !== null) {
                $this->rewardResource->saveRewardSalesrule(
                    $entity->getData($metadata->getLinkField()),
                    (int)$pointsDelta
                );
            }
        }

        return $entity;
    }
}
