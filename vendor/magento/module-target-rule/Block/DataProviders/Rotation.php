<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\TargetRule\Block\DataProviders;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\TargetRule\Helper\Data;
use Magento\TargetRule\Model\Source\Rotation as SourceRotation;

/**
 * Rotation mode data provider.
 */
class Rotation implements ArgumentInterface
{
    /**
     * @var Data
     */
    private $targetRuleData;

    /**
     * Rotation constructor.
     *
     * @param Data $targetRuleData
     */
    public function __construct(Data $targetRuleData)
    {
        $this->targetRuleData = $targetRuleData;
    }

    /**
     * Check if rotation mode is set to "weighted random".
     *
     * @param string $type
     * @return bool
     */
    public function isWeightedRandom(string $type): bool
    {
        $rotation = $this->targetRuleData->getRotationMode($type);
        return $rotation === SourceRotation::ROTATION_WEIGHTED_RANDOM;
    }
}
