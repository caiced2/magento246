<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\Plugin\Catalog\Controller\Adminhtml\Category;

use Magento\Backend\Model\Session;
use Magento\Catalog\Controller\Adminhtml\Category\Save as SaveController;
use Magento\Catalog\Model\CategoryFactory;
use Magento\Framework\Serialize\Serializer\Json;
use Psr\Log\LoggerInterface;
use Magento\Catalog\Model\AbstractModel;

/**
 * Stores category product positions in session
 */
class SavePlugin
{
    /**
     * @var CategoryFactory
     */
    private $categoryFactory;

    /**
     * @var Json
     */
    private $jsonManager;

    /**
     * @var Session
     */
    private $session;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param CategoryFactory $categoryFactory
     * @param Json $jsonManager
     * @param Session $session
     * @param LoggerInterface $logger
     */
    public function __construct(
        CategoryFactory $categoryFactory,
        Json $jsonManager,
        Session $session,
        LoggerInterface $logger
    ) {
        $this->categoryFactory = $categoryFactory;
        $this->jsonManager = $jsonManager;
        $this->session = $session;
        $this->logger = $logger;
    }

    /**
     * Stores category product positions in session
     *
     * @param SaveController $subject
     * @return void
     */
    public function beforeExecute(SaveController $subject)
    {
        $productPositions = $subject->getRequest()->getParam('vm_category_products');
        $catalogId = (int) $subject->getRequest()->getParam('entity_id');
        $urlKey = (string) $subject->getRequest()->getParam('url_key');

        if ($productPositions && !$this->validUrlKey($urlKey, $catalogId)) {
            try {
                $productPositions = $this->jsonManager->unserialize($productPositions);
                $this->session->setCategoryProductPositions($productPositions);
            } catch (\InvalidArgumentException $e) {
                $this->logger->warning($e->getMessage());
            }
        }
    }

    /**
     * Checks an url key value
     *
     * @param string $urlKey
     * @param int $catalogId
     * @return bool
     */
    private function validUrlKey(string $urlKey, int $catalogId) : bool
    {
        $catalog = $this->categoryFactory->create()->loadByAttribute('url_key', $urlKey, '');
        if ($catalog instanceof AbstractModel) {
            return ((int) $catalog->getEntityId()) === $catalogId;
        }
        return true;
    }
}
