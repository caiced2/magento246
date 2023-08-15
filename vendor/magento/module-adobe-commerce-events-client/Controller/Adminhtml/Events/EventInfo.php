<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdobeCommerceEventsClient\Controller\Adminhtml\Events;

use Magento\AdobeCommerceEventsClient\Block\Events\EventPayload;
use Magento\Backend\App\Action;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\Result\Raw;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\View\Result\Page;

/**
 * Event info Post controller
 */
class EventInfo extends Action implements HttpPostActionInterface
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    public const ADMIN_RESOURCE = 'Magento_AdobeCommerceEventsClient::event_list';

    /**
     * @inheritDoc
     */
    public function execute(): ResultInterface
    {
        /** @var Raw $result */
        $result = $this->resultFactory->create(ResultFactory::TYPE_RAW);
        /** @var Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        /** @var EventPayload $block */
        $block = $resultPage->getLayout()
            ->createBlock(EventPayload::class);

        $html = $block->setTemplate('Magento_AdobeCommerceEventsClient::events/event_payload.phtml')
            ->setData('data', ['event' => $this->getRequest()->getParam('event')])
            ->toHtml();

        $result->setContents($html);

        return $result;
    }
}
