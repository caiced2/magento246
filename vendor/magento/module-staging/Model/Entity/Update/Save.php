<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Model\Entity\Update;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface;
use Magento\Staging\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Staging\Model\Entity\Update\Action\Pool;
use Psr\Log\LoggerInterface;

class Save
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @var JsonFactory
     */
    protected $jsonFactory;

    /**
     * @var Pool
     */
    protected $actionPool;

    /**
     * @var string
     */
    protected $entityName;

    /**
     * @var LoggerInterface
     */
    protected $logger;

    /**
     * @param ManagerInterface $messageManager
     * @param JsonFactory $jsonFactory
     * @param Pool $actionPool
     * @param LoggerInterface $logger
     * @param string $entityName
     */
    public function __construct(
        ManagerInterface $messageManager,
        JsonFactory $jsonFactory,
        Pool $actionPool,
        LoggerInterface $logger,
        $entityName
    ) {
        $this->messageManager = $messageManager;
        $this->jsonFactory = $jsonFactory;
        $this->actionPool = $actionPool;
        $this->logger = $logger;
        $this->entityName = $entityName;
    }

    /**
     * Execute actions
     *
     * @param array $params
     * @return Json
     */
    public function execute(array $params)
    {
        $error = true;
        try {
            $action = $this->actionPool->getAction($this->entityName, 'save', $this->getActionType($params));
            $executor = $this->actionPool->getExecutor($action);
            $error = !$executor->execute($params);
            $this->messageManager->addSuccess(
                __('You saved this %1 update.', __($this->entityName))
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addError($e->getMessage());
            $this->logger->critical($e);
        } catch (\Exception $e) {
            $this->messageManager->addException(
                $e,
                __('Something went wrong while saving the %1.', $this->entityName)
            );
            $this->logger->critical($e);
        }

        /** @var Json $resultJson */
        return $this->jsonFactory->create([], ['error' => $error]);
    }

    /**
     * Retrieve staging mode
     *
     * @param array $params
     * @return string
     * @throws LocalizedException
     */
    protected function getActionType(array $params)
    {
        if (!isset($params['stagingData']['mode'])) {
            throw new LocalizedException(__("The 'mode' value is unexpected."));
        }
        return $params['stagingData']['mode'];
    }
}
