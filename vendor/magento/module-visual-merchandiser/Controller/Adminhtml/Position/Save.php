<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\VisualMerchandiser\Controller\Adminhtml\Position;

use Magento\Framework\App\Action\HttpPostActionInterface as HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Serialize\SerializerInterface;

class Save extends \Magento\VisualMerchandiser\Controller\Adminhtml\Position implements HttpPostActionInterface
{
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * @param \Magento\Backend\App\Action\Context $context
     * @param \Magento\VisualMerchandiser\Model\Position\Cache $cache
     * @param \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory
     * @param SerializerInterface|null $serializer
     */
    public function __construct(
        \Magento\Backend\App\Action\Context $context,
        \Magento\VisualMerchandiser\Model\Position\Cache $cache,
        \Magento\Framework\Controller\Result\JsonFactory $resultJsonFactory,
        SerializerInterface $serializer = null
    ) {
        parent::__construct($context, $cache, $resultJsonFactory);
        $this->serializer = $serializer ?: ObjectManager::getInstance()->get(SerializerInterface::class);
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultJsonFactory->create();

        $cacheKey = $this->getRequest()->getParam(
            \Magento\VisualMerchandiser\Model\Position\Cache::POSITION_CACHE_KEY
        );

        $positions = $this->getRequest()->getParam('positions', false) ?
            $this->getRequest()->getParam('positions', false) : [];
        $decodedPositions = $this->serializer->unserialize($positions);

        $this->cache->saveData(
            $cacheKey,
            $decodedPositions,
            $this->getRequest()->getParam('sort_order', null)
        );

        $resultJson->setData([]);
        return $resultJson;
    }
}
