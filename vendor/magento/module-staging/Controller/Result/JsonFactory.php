<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Controller\Result;

use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Message\MessageInterface;
use Magento\Framework\ObjectManagerInterface;

class JsonFactory extends \Magento\Framework\Controller\Result\JsonFactory
{
    /**
     * @var ManagerInterface
     */
    protected $messageManager;

    /**
     * @param ObjectManagerInterface $objectManager
     * @param ManagerInterface $messageManager
     * @param string $instanceName
     */
    public function __construct(
        ObjectManagerInterface $objectManager,
        ManagerInterface $messageManager,
        $instanceName = \Magento\Framework\Controller\Result\Json::class
    ) {
        $this->messageManager = $messageManager;
        parent::__construct($objectManager, $instanceName);
    }

    /**
     * Create JSON object with messages
     *
     * @param array $resultData
     * @param array $data
     * @return Json
     */
    public function create(array $data = [], $resultData = [])
    {
        $model = parent::create($data);
        $messages = '';
        /** @var MessageInterface $message */
        foreach ($this->messageManager->getMessages(true)->getItems() as $message) {
            $messages .= $message->toString() . '<br/>';
        }
        $resultData['messages'] = $messages;
        $model->setData($resultData);
        return $model;
    }
}
