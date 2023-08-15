<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model\Block;

use Magento\Cms\Model\Block;
use Magento\Cms\Model\BlockFactory;
use Magento\Framework\DataObject;
use Magento\Staging\Model\Entity\RetrieverInterface;

class Retriever implements RetrieverInterface
{
    /**
     * @var BlockFactory
     */
    protected $blockFactory;

    /**
     * @param BlockFactory $blockFactory
     */
    public function __construct(
        BlockFactory $blockFactory
    ) {
        $this->blockFactory = $blockFactory;
    }

    /**
     * @param string $entityId
     * @return Block
     */
    public function getEntity($entityId)
    {
        /** @var Block $entity */
        $entity = $this->blockFactory->create();
        $entity->load($entityId);
        return $entity;
    }
}
