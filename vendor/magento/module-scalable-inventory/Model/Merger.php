<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\ScalableInventory\Model;

use Magento\Framework\MessageQueue\MergerInterface;
use Magento\ScalableInventory\Api\Counter\ItemsInterface;
use Magento\ScalableInventory\Model\Counter\ItemsBuilder;
use Magento\Framework\MessageQueue\MergedMessageInterfaceFactory;

/**
 * Merges messages from the operations queue.
 *
 * @deprecated split database solution is deprecated and will be removed
 */
class Merger implements MergerInterface
{
    /**
     * @var \Magento\ScalableInventory\Model\Counter\ItemsBuilder
     */
    private $itemsBuilder;

    /**
     * @var \Magento\Framework\MessageQueue\MergedMessageInterfaceFactory
     */
    private $mergedMessageFactory;

    /**
     * @param ItemsBuilder $itemsBuilder
     * @param MergedMessageInterfaceFactory $mergedMessageFactory [optional]
     */
    public function __construct(
        ItemsBuilder $itemsBuilder,
        MergedMessageInterfaceFactory $mergedMessageFactory = null
    ) {
        $this->itemsBuilder = $itemsBuilder;
        $this->mergedMessageFactory = $mergedMessageFactory
            ?: \Magento\Framework\App\ObjectManager::getInstance()->get(MergedMessageInterfaceFactory::class);
    }

    /**
     * {@inheritdoc}
     */
    public function merge(array $messageList)
    {
        $arguments = [];
        $originalMessagesIds = [];

        foreach ($messageList as $topic => $messages) {
            /** @var ItemsInterface[] $messages */
            foreach ($messages as $messageId => $message) {
                $items = $message->getItems();
                $operator = $message->getOperator();
                $websiteId = $message->getWebsiteId();
                foreach ($items as $item) {
                    $productId = $item->getProductId();
                    $qty = $item->getQty();
                    if (isset($arguments[$topic][$operator][$websiteId][$item->getProductId()])) {
                        $arguments[$topic][$operator][$websiteId][$productId] += $qty;
                    } else {
                        $arguments[$topic][$operator][$websiteId][$productId] = $qty;
                    }
                    $originalMessagesIds[$topic][$operator][$websiteId][$messageId] = $messageId;
                }
            }
        }

        $mergedMessages = [];
        foreach ($arguments as $topic => $args) {
            foreach ($args as $operator => $argumentsByOperator) {
                foreach ($argumentsByOperator as $websiteId => $argumentByWebsiteId) {
                    $mergedMessage = $this->itemsBuilder->build($argumentByWebsiteId, $websiteId, $operator);
                    $mergedMessages[$topic][] = $this->mergedMessageFactory->create(
                        [
                            'mergedMessage' => $mergedMessage,
                            'originalMessagesIds' => $originalMessagesIds[$topic][$operator][$websiteId]
                        ]
                    );
                }
            }
        }

        return $mergedMessages;
    }
}
