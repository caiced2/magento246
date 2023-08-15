<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\AdvancedCheckout\Block\Adminhtml\Customer\Edit;

use Magento\Customer\Controller\RegistryConstants;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Additional buttons on customer edit form
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
class ManageShoppingCartButton implements ButtonProviderInterface
{
    /**
     * @var \Magento\Backend\Block\Widget\Button\ButtonList
     */
    protected $buttonList;

    /**
     * @var \Magento\Framework\AuthorizationInterface
     */
    protected $authorization;

    /**
     * Url Builder
     *
     * @var \Magento\Framework\UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var \Magento\Framework\Registry
     */
    protected $registry;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var \Magento\Customer\Api\CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * Constructor
     *
     * @codeCoverageIgnore
     * @param \Magento\Backend\Block\Widget\Context $context
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
     */
    public function __construct(
        \Magento\Backend\Block\Widget\Context $context,
        \Magento\Framework\Registry $registry,
        \Magento\Customer\Api\CustomerRepositoryInterface $customerRepository
    ) {
        $this->buttonList = $context->getButtonList();
        $this->authorization = $context->getAuthorization();
        $this->urlBuilder = $context->getUrlBuilder();
        $this->registry = $registry;
        $this->storeManager = $context->getStoreManager();
        $this->customerRepository = $customerRepository;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if (!$this->getCustomerId()) {
            return $data;
        }

        $customerWebsite = $this->customerRepository->getById($this->getCustomerId())->getWebsiteId();
        if ($this->authorization->isAllowed('Magento_AdvancedCheckout::view')
            && $this->authorization->isAllowed('Magento_AdvancedCheckout::update')
            && $this->storeManager->getStore(Store::ADMIN_CODE)->getWebsiteId() != $customerWebsite
        ) {
            $data =  [
                'label' => __('Manage Shopping Cart'),
                'on_click' => sprintf("location.href = '%s';", $this->getManageShoppingCartUrl()),
                'sort_order' => 70,
            ];
        }
        return $data;
    }

    /**
     * @codeCoverageIgnore
     * @return string
     */
    public function getManageShoppingCartUrl()
    {
        return $this->urlBuilder->getUrl('checkout/index', ['customer' => $this->getCustomerId()]);
    }

    /**
     * Return the customer Id.
     *
     * @codeCoverageIgnore
     * @return int|null
     */
    public function getCustomerId()
    {
        $customerId = $this->registry->registry(RegistryConstants::CURRENT_CUSTOMER_ID);
        return $customerId;
    }
}
