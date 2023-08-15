<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\ReCaptchaGiftCard\Observer;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\RequestHandlerInterface;

/**
 * Giftcard feature observer
 */
class GiftCardObserver implements ObserverInterface
{
    /**
     * Recaptcha key
     */
    private const RECAPTCHA_KEY = 'giftcard';

    /**
     * @var RedirectInterface
     */
    private $redirect;

    /**
     * @var IsCaptchaEnabledInterface
     */
    private $isCaptchaEnabled;

    /**
     * @var RequestHandlerInterface
     */
    private $requestHandler;

    /**
     * @param RedirectInterface $redirect
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param RequestHandlerInterface $requestHandler
     */
    public function __construct(
        RedirectInterface $redirect,
        IsCaptchaEnabledInterface $isCaptchaEnabled,
        RequestHandlerInterface $requestHandler
    ) {
        $this->redirect = $redirect;
        $this->isCaptchaEnabled = $isCaptchaEnabled;
        $this->requestHandler = $requestHandler;
    }

    /**
     *  Provides required configurtion details
     *
     * @param Observer $observer
     * @return void
     * @throws LocalizedException
     */
    public function execute(Observer $observer): void
    {
        $key = 'giftcard';
        if ($this->isCaptchaEnabled->isCaptchaEnabledFor(self::RECAPTCHA_KEY)) {
            /** @var Action $controller */
            $controller = $observer->getControllerAction();
            $request = $controller->getRequest();
            $response = $controller->getResponse();
            $data = $observer->getData();
            if (count($data['controller_action']->getRequest()->getPost())> 0) {
                $redirectOnFailureUrl = $this->redirect->getRefererUrl();
                $this->requestHandler->execute($key, $request, $response, $redirectOnFailureUrl);
            }
        }
    }
}
