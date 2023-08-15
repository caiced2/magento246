<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Event\Queue;

use Magento\Framework\MessageQueue\MergerInterface;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;

/**
 * Merges messages from the event queue.
 */
class Merger implements MergerInterface
{
    /**
     * @var MergedMessageInterfaceFactory
     */
    private MergedMessageInterfaceFactory $mergedMessageFactory;

    /**
     * @param MergedMessageInterfaceFactory $mergedMessageFactory
     */
    public function __construct(
        MergedMessageInterfaceFactory $mergedMessageFactory
    ) {
        $this->mergedMessageFactory = $mergedMessageFactory;
    }

    /**
     * Merges messages from the queue into single message for priority event batch consumer.
     *
     * @param array $messages
     * @return array
     */
    public function merge(array $messages): array
    {
        $mergedMessages = [];

        foreach ($messages as $topic => $topicMessages) {
            $mergedMessages[$topic][] = $this->mergedMessageFactory->create(
                [
                    'mergedMessage' => implode(', ', $topicMessages),
                    'originalMessagesIds' => array_keys($topicMessages)
                ]
            );
        }

        return $mergedMessages;
    }
}
