<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableCheckout\Model;

use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;

/**
 * Merges messages from the operations queue.
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class Merger implements \Magento\Framework\MessageQueue\MergerInterface
{
    /**
     * @var \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory
     */
    private $mergedMessageFactory;

    /**
     * @param \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory $mergedMessageFactory
     */
    public function __construct(MergedMessageInterfaceFactory $mergedMessageFactory)
    {
        $this->mergedMessageFactory = $mergedMessageFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(MergedMessageInterfaceFactory::class);
    }

    /**
     * @inheritdoc
     */
    public function merge(array $messageList)
    {
        $result = [];
        $originalMessagesIds = [];
        foreach ($messageList as $topicName => $topicMessages) {
            foreach ($topicMessages as $messageId => $message) {
                $originalMessagesIds[] = $messageId;
                $mergedMessage = $this->mergedMessageFactory->create(
                    [
                        'mergedMessage' => $message,
                        'originalMessagesIds' => $originalMessagesIds,
                    ]
                );
                $result[$topicName] = [$mergedMessage];
            }
        }

        return $result;
    }
}
