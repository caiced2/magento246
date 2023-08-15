<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model\Block;

use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Framework\Message\ManagerInterface;
use Magento\Cms\Model\Block;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Cms\Api\Data\BlockInterface;
use Magento\Framework\EntityManager\MetadataPool;

class Hydrator implements HydratorInterface
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var RetrieverInterface
     */
    protected $entityRetriever;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @param ManagerInterface $messageManager
     * @param RetrieverInterface $entityRetriever
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        ManagerInterface $messageManager,
        RetrieverInterface $entityRetriever,
        MetadataPool $metadataPool
    ) {
        $this->messageManager = $messageManager;
        $this->entityRetriever = $entityRetriever;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $data)
    {
        if (isset($data['is_active']) && $data['is_active'] === 'true') {
            $data['is_active'] = Block::STATUS_ENABLED;
        }
        if (empty($data['block_id'])) {
            $data['block_id'] = null;
        }

        $model = null;
        if (isset($data['block_id'])) {
            /** @var Block $model */
            $model = $this->entityRetriever->getEntity($data['block_id']);
            if ($model) {
                $entityMetadata = $this->metadataPool->getMetadata(BlockInterface::class);
                $linkField = $entityMetadata->getLinkField();
                $data[$linkField] = $model->getData($linkField);
                $data['created_in'] = $model->getCreatedIn();
                $data['updated_in'] = $model->getUpdatedIn();
            }
        }
        if (!$model || !$model->getId() && $data['block_id']) {
            $this->messageManager->addError(__('This block no longer exists.'));
            return false;
        }

        $model->setData($data);
        return $model;
    }
}
