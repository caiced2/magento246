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
 * An extension handler to read Reward points for Sales Rule
 *
 * Handler reads Reward Points value from related table for Sales Rule and puts it into extension attributes
 */
class ReadHandler implements ExtensionInterface
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
     * Fill Sales Rule extension attributes with related Reward Points value
     *
     * @param Rule|object $entity
     * @param array $arguments
     * @return Rule
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
            $data = $this->rewardResource->getRewardSalesrule(
                $entity->getData($metadata->getLinkField())
            );
            $points = $data['points_delta'] ?? 0;
            $attributes['reward_points_delta'] = $points;
            $entity->setRewardPointsDelta($points);
        }
        $entity->setExtensionAttributes($attributes);

        return $entity;
    }
}
