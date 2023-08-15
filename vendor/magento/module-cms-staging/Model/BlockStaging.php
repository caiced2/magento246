<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CmsStaging\Model;

use Magento\Cms\Api\Data\BlockInterface;
use Magento\CmsStaging\Api\BlockStagingInterface;
use Magento\Framework\EntityManager\EntityManager;
use Magento\Framework\Exception\ValidatorException;
use Magento\Staging\Model\ResourceModel\Db\CampaignValidator;

/**
 * Class BlockStaging
 */
class BlockStaging implements BlockStagingInterface
{
    /**
     * @var EntityManager
     */
    private $entityManager;

    /**
     * @var CampaignValidator
     */
    private $campaignValidator;

    /**
     * BlockStaging constructor.
     *
     * @param EntityManager $entityManager
     * @param CampaignValidator $campaignValidator
     */
    public function __construct(
        EntityManager $entityManager,
        CampaignValidator $campaignValidator
    ) {
        $this->entityManager = $entityManager;
        $this->campaignValidator = $campaignValidator;
    }

    /**
     * @param BlockInterface $block
     * @param string $version
     * @param array $arguments
     * @return bool
     * @throws \Exception
     */
    public function schedule(\Magento\Cms\Api\Data\BlockInterface $block, $version, $arguments = [])
    {
        $previous = isset($arguments['origin_in']) ? $arguments['origin_in'] : null;
        if (!$this->campaignValidator->canBeScheduled($block, $version, $previous)) {
            throw new ValidatorException(
                __('Future Update already exists in this time range. Set a different range and try again.')
            );
        }
        $arguments['created_in'] = $version;
        return (bool)$this->entityManager->save($block, $arguments);
    }

    /**
     * @param BlockInterface $block
     * @param string $version
     * @return bool
     */
    public function unschedule(\Magento\Cms\Api\Data\BlockInterface $block, $version)
    {
        return (bool)$this->entityManager->delete(
            $block,
            [
                'created_in' => $version
            ]
        );
    }
}
