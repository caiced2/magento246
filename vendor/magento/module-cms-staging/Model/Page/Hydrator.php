<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model\Page;

use Magento\Staging\Model\Entity\HydratorInterface;
use Magento\Backend\App\Action\Context;
use Magento\Cms\Controller\Adminhtml\Page\PostDataProcessor;
use Magento\Cms\Model\Page;
use Magento\Staging\Model\Entity\RetrieverInterface;
use Magento\Framework\EntityManager\MetadataPool;
use Magento\Cms\Api\Data\PageInterface;

class Hydrator implements HydratorInterface
{
    /**
     * @var Context
     */
    protected $context;

    /**
     * @var MetadataPool
     */
    private $metadataPool;

    /**
     * @var PostDataProcessor
     */
    protected $postDataProcessor;

    /**
     * @var RetrieverInterface
     */
    protected $entityRetriever;

    /**
     * @param Context $context
     * @param PostDataProcessor $postDataProcessor
     * @param RetrieverInterface $entityRetriever
     * @param MetadataPool $metadataPool
     */
    public function __construct(
        Context $context,
        PostDataProcessor $postDataProcessor,
        RetrieverInterface $entityRetriever,
        MetadataPool $metadataPool
    ) {
        $this->context = $context;
        $this->postDataProcessor = $postDataProcessor;
        $this->entityRetriever = $entityRetriever;
        $this->metadataPool = $metadataPool;
    }

    /**
     * @inheritDoc
     */
    public function hydrate(array $data)
    {
        $data = $this->postDataProcessor->filter($data);
        if (isset($data['is_active']) && $data['is_active'] === 'true') {
            $data['is_active'] = Page::STATUS_ENABLED;
        }
        if (empty($data['page_id'])) {
            $data['page_id'] = null;
        }

        $model = null;
        if (isset($data['page_id'])) {
            /** @var Page $model */
            $model = $this->entityRetriever->getEntity($data['page_id']);
            if ($model) {
                $entityMetadata = $this->metadataPool->getMetadata(PageInterface::class);
                $linkField = $entityMetadata->getLinkField();
                $data[$linkField] = $model->getData($linkField);
                $data['created_in'] = $model->getCreatedIn();
                $data['updated_in'] = $model->getUpdatedIn();
            }
        }
        $model->setData($data);

        $this->context->getEventManager()->dispatch(
            'cms_page_prepare_save',
            ['page' => $model, 'request' => $this->context->getRequest()]
        );

        if ($this->postDataProcessor->validate($data)) {
            return $model;
        }
        return false;
    }
}
