<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CmsStaging\Model\Page;

use Magento\Cms\Model\Page;
use Magento\Cms\Model\PageFactory;
use Magento\Framework\DataObject;
use Magento\Staging\Model\Entity\RetrieverInterface;

class Retriever implements RetrieverInterface
{
    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @param PageFactory $pageFactory
     */
    public function __construct(
        PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
    }

    /**
     * @param string $entityId
     * @return Page
     */
    public function getEntity($entityId)
    {
        /** @var Page $entity */
        $entity = $this->pageFactory->create();
        $entity->load($entityId);
        return $entity;
    }
}
