<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Model;

/**
 * Class \Magento\GiftRegistry\Model\Logging
 *
 * Model for logging event related to Gift Registry, active only if Magento_Logging module is enabled
 */
class Logging
{
    /**
     * @var \Magento\Framework\App\RequestInterface|null
     */
    protected $request = null;

    /**
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(\Magento\Framework\App\RequestInterface $request)
    {
        $this->request = $request;
    }

    /**
     * Custom handler for giftregistry type save action
     *
     * @param array $config
     * @param \Magento\Logging\Model\Event $eventModel
     * @param \Magento\Logging\Model\Processor $processor
     * @return \Magento\Logging\Model\Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchTypeSave($config, $eventModel, $processor)
    {
        $typeData = $this->request->getParam('type');
        $typeId = isset($typeData['type_id']) ? $typeData['type_id'] : __('New');
        return $eventModel->setInfo($typeId);
    }

    /**
     * Custom handler for giftregistry share email action
     *
     * @param array $config
     * @param \Magento\Logging\Model\Event $eventModel
     * @param \Magento\Logging\Model\Processor $processor
     * @return \Magento\Logging\Model\Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchShare($config, $eventModel, $processor)
    {
        $emails = $this->request->getParam('emails', '');
        if ($emails) {
            $processor->addEventChanges(
                $processor->createChanges('share', [], ['emails' => $emails])
            );
        }

        $message = $this->request->getParam('message', '');
        if ($message) {
            $processor->addEventChanges(
                $processor->createChanges('share', [], ['message' => $message])
            );
        }

        return $eventModel;
    }
}
