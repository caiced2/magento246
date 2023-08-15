<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AsyncOrder\Model\Order\Email\Sender;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Email\Container\OrderCommentIdentity;
use Magento\Sales\Model\Order\Email\Container\Template;
use Magento\Sales\Model\Order\Email\NotifySender;
use Magento\Sales\Model\Order\Address\Renderer;
use Magento\Framework\Event\ManagerInterface;
use Magento\Framework\DataObject;
use Magento\Sales\Model\Order\Email\SenderBuilderFactory;
use Psr\Log\LoggerInterface;

/**
 * Class for sending email for a rejected order
 */
class RejectedOrderSender extends NotifySender
{
    /**
     * @var ManagerInterface
     */
    private $eventManager;

    /**
     * @param Template $templateContainer
     * @param OrderCommentIdentity $identityContainer
     * @param SenderBuilderFactory $senderBuilderFactory
     * @param LoggerInterface $logger
     * @param Renderer $addressRenderer
     * @param ManagerInterface $eventManager
     */
    public function __construct(
        Template $templateContainer,
        OrderCommentIdentity $identityContainer,
        SenderBuilderFactory $senderBuilderFactory,
        LoggerInterface $logger,
        Renderer $addressRenderer,
        ManagerInterface $eventManager
    ) {
        parent::__construct(
            $templateContainer,
            $identityContainer,
            $senderBuilderFactory,
            $logger,
            $addressRenderer
        );

        $this->eventManager = $eventManager;
        $this->addressRenderer = $addressRenderer;
    }

    /**
     * Send email for rejected order to customer
     *
     * @param Order $order
     * @param bool $notify
     * @param string $comment
     * @return bool
     */
    public function send(Order $order, $notify = true, $comment = '')
    {
        $this->identityContainer->setStore($order->getStore());

        $emailData = [
            'order' => $order,
            'order_data' => [
                'customer_name' => $order->getCustomerName(),
                'frontend_status_label' => $order->getFrontendStatusLabel()
            ],
            'store' => $order->getStore(),
            'billing' => $order->getBillingAddress(),
            'comment' => $comment,
            'formattedBillingAddress' => $this->getFormattedBillingAddress($order),
            'formattedShippingAddress' => $this->getFormattedShippingAddress($order)
        ];

        $emailDataObject = new DataObject($emailData);

        /**
         * Event argument `transport` is @deprecated. Use `transportObject` instead.
         */
        $this->eventManager->dispatch(
            'email_rejected_order_set_template_vars_before',
            ['sender' => $this, 'transport' => $emailDataObject->getData(), 'transportObject' => $emailDataObject]
        );

        $this->templateContainer->setTemplateVars($emailDataObject->getData());

        return $this->checkAndSend($order, $notify);
    }
}
