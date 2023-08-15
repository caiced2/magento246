<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CustomerSegment\Model;

/**
 * Class \Magento\CustomerSegment\Model\Logging
 *
 * Model for logging event related to Customer Segment, active only if Magento_Logging module is enabled
 */
class Logging
{
    /**
     * @var \Magento\CustomerSegment\Model\ResourceModel\Segment|null
     */
    protected $_resourceModel = null;

    /**
     * @var \Magento\Framework\App\RequestInterface|null
     */
    protected $_request = null;

    /**
     * @param \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceModel
     * @param \Magento\Framework\App\RequestInterface $request
     */
    public function __construct(
        \Magento\CustomerSegment\Model\ResourceModel\Segment $resourceModel,
        \Magento\Framework\App\RequestInterface $request
    ) {
        $this->_resourceModel = $resourceModel;
        $this->_request = $request;
    }

    /**
     * Handler for logging customer segment match
     *
     * @param array $config
     * @param \Magento\Logging\Model\Event $eventModel
     * @return \Magento\Logging\Model\Event
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function postDispatchCustomerSegmentMatch($config, $eventModel)
    {
        $segmentId = $this->_request->getParam('id');
        return $eventModel->setInfo(
            $segmentId ? __('Matching Customers of Segment %1 is added to messages queue.', $segmentId) : '-'
        );
    }
}
