<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Controller\Cart;

use Magento\Framework\App\ObjectManager;
use Magento\GiftCardAccount\Api\Data\GiftCardAccountInterfaceFactory;
use Magento\GiftCardAccount\Api\GiftCardAccountManagementInterface;
use Magento\GiftCardAccount\Api\Exception\TooManyAttemptsException;
use Magento\Framework\App\Action\HttpPostActionInterface;

/**
 * Add Gift Card to current quote.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Add extends \Magento\Checkout\Controller\Cart implements HttpPostActionInterface
{
    /**
     * @var GiftCardAccountManagementInterface
     */
    private $management;

    /**
     * @var GiftCardAccountInterfaceFactory
     */
    private $cardFactory;

    /**
     * @param \Magento\Framework\App\Action\Context $context
     * @param \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig
     * @param \Magento\Checkout\Model\Session $checkoutSession
     * @param \Magento\Store\Model\StoreManagerInterface $storeManager
     * @param \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator
     * @param \Magento\Checkout\Model\Cart $cart
     * @param GiftCardAccountManagementInterface|null $management
     * @param GiftCardAccountInterfaceFactory|null $cardFactory
     */
    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\App\Config\ScopeConfigInterface $scopeConfig,
        \Magento\Checkout\Model\Session $checkoutSession,
        \Magento\Store\Model\StoreManagerInterface $storeManager,
        \Magento\Framework\Data\Form\FormKey\Validator $formKeyValidator,
        \Magento\Checkout\Model\Cart $cart,
        ?GiftCardAccountManagementInterface $management = null,
        ?GiftCardAccountInterfaceFactory $cardFactory = null
    ) {
        parent::__construct(
            $context,
            $scopeConfig,
            $checkoutSession,
            $storeManager,
            $formKeyValidator,
            $cart
        );
        $this->management = $management
            ?? ObjectManager::getInstance()->get(GiftCardAccountManagementInterface::class);
        $this->cardFactory = $cardFactory ?? ObjectManager::getInstance()->get(GiftCardAccountInterfaceFactory::class);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if (isset($data['giftcard_code'])) {
            $code = $data['giftcard_code'];
            try {
                $this->management->saveByQuoteId(
                    $this->cart->getQuote()->getId(),
                    $this->cardFactory->create(['data' => ['gift_cards' => [$code]]])
                );
                $this->messageManager->addSuccess(
                    __(
                        'Gift Card "%1" was added.',
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($code)
                    )
                );
            } catch (TooManyAttemptsException $e) {
                $this->messageManager->addErrorMessage($e->getMessage());
            } catch (\Magento\Framework\Exception\LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Throwable $e) {
                $this->messageManager->addException($e, __('We cannot apply this gift card.'));
            }
        }

        return $this->_goBack();
    }
}
