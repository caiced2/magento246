<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftCardAccount\Controller\Customer;

use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\ObjectManager;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\CustomerBalance\Helper\Data as CustomerBalanceHelper;
use Magento\GiftCardAccount\Model\Spi\GiftCardAccountManagerInterface;
use Magento\GiftCardAccount\Model\Giftcardaccount as GiftCardAccount;
use Magento\GiftCardAccount\Model\GiftcardaccountFactory as GiftCardAccountFactory;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Redeem gift card.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class Index extends \Magento\Framework\App\Action\Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var CustomerBalanceHelper
     */
    private $customerBalanceHelper;

    /**
     * @var GiftCardAccountManagerInterface
     */
    private $manager;

    /**
     * @var StoreManagerInterface
     */
    private $storeManager;

    /**
     * @var GiftCardAccountFactory
     */
    private $giftCardFactory;

    /**
     * @param Context $context
     * @param CustomerBalanceHelper|null $customerBalanceHelper
     * @param GiftCardAccountManagerInterface|null $manager
     * @param StoreManagerInterface|null $storeManager
     * @param GiftCardAccountFactory|null $giftCardFactory
     */
    public function __construct(
        Context $context,
        ?CustomerBalanceHelper $customerBalanceHelper = null,
        ?GiftCardAccountManagerInterface $manager = null,
        ?StoreManagerInterface $storeManager = null,
        ?GiftCardAccountFactory $giftCardFactory = null
    ) {
        parent::__construct($context);
        $this->customerBalanceHelper = $customerBalanceHelper
            ?? ObjectManager::getInstance()->get(CustomerBalanceHelper::class);
        $this->manager = $manager ?? ObjectManager::getInstance()->get(GiftCardAccountManagerInterface::class);
        $this->storeManager = $storeManager ?? ObjectManager::getInstance()->get(StoreManagerInterface::class);
        $this->giftCardFactory = $giftCardFactory ?? ObjectManager::getInstance()->get(GiftCardAccountFactory::class);
    }

    /**
     * @inheritDoc
     *
     * Only logged in users can use this functionality,
     * this function checks if user is logged in before all other actions.
     */
    public function dispatch(RequestInterface $request)
    {
        if (!$this->_objectManager->get(\Magento\Customer\Model\Session::class)->authenticate()) {
            $this->_actionFlag->set('', 'no-dispatch', true);
        }
        return parent::dispatch($request);
    }

    /**
     * @inheritDoc
     */
    public function execute()
    {
        $data = $this->getRequest()->getPostValue();
        if ($this->getRequest()->isPost() && isset($data['giftcard_code'])) {
            $code = $data['giftcard_code'];
            try {
                /** @var GiftCardAccount $card */
                $card = $this->giftCardFactory->create();
                $card->setCode($code);
                $card->loadByCode($code);
                $card->redeem();
                $this->messageManager->addSuccess(
                    __(
                        'Gift Card "%1" was redeemed.',
                        $this->_objectManager->get(\Magento\Framework\Escaper::class)->escapeHtml($code)
                    )
                );
            } catch (NoSuchEntityException $exception) {
                $this->messageManager->addErrorMessage(__('We cannot redeem this gift card.'));
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
            } catch (\Throwable $e) {
                $this->messageManager->addException($e, __('We cannot redeem this gift card.'));
            }
            $this->_redirect('*/*/*');
            return;
        }
        $this->_view->loadLayout();
        $this->_view->loadLayoutUpdates();
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Gift Card'));
        $this->_view->renderLayout();
    }
}
