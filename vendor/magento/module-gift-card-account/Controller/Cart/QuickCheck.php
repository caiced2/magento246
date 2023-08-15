<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Controller\Cart;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\NotFoundException;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\Framework\App\Action\Action;
use Psr\Log\LoggerInterface;

/**
 * Check a gift card account availability.
 */
class QuickCheck extends Action implements HttpPostActionInterface
{
    /**
     * Core registry
     *
     * @var \Magento\Framework\Registry
     */
    protected $_coreRegistry = null;

    /**
     * @var GiftCardAccountManagerInterface
     */
    private $management;

    /**
     * @var LoggerInterface
     */
    private $logger;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\Registry $coreRegistry
     * @param GiftCardAccountManagerInterface|null $management
     * @param LoggerInterface|null $logger
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\Registry $coreRegistry,
        ?GiftCardAccountManagerInterface $management = null,
        ?LoggerInterface $logger = null
    ) {
        $this->_coreRegistry = $coreRegistry;
        $this->management = $management
            ?? ObjectManager::getInstance()->get(GiftCardAccountManagerInterface::class);
        $this->logger = $logger ?? ObjectManager::getInstance()->get(LoggerInterface::class);
        parent::__construct($context);
    }

    /**
     * @inheritDoc
     *
     * @throws NotFoundException
     */
    public function execute()
    {
        /** @var HttpRequest $request */
        $request = $this->getRequest();
        $this->_coreRegistry->unregister('current_giftcardaccount_check_error');
        $this->_coreRegistry->unregister('current_giftcardaccount');
        if (!$request->isXmlHttpRequest()) {
            throw new NotFoundException(__('Invalid Request'));
        } else {
            try {
                $card = $this->management->requestByCode($request->getParam('giftcard_code', ''));
                $this->_coreRegistry->register('current_giftcardaccount', $card);
            } catch (TooManyAttemptsException $exception) {
                $this->_coreRegistry->register(
                    'current_giftcardaccount_check_error',
                    $exception->getMessage()
                );
            } catch (NoSuchEntityException|\InvalidArgumentException $exception) {
                //Will show default error message.
                $this->logger->error($exception);
            } catch (\Throwable $exception) {
                //Will show default error message.
                $this->logger->error($exception);
            }
        }

        $this->_view->loadLayout();
        $this->_view->renderLayout();
    }
}
