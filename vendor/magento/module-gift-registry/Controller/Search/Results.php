<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Controller\Search;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\TimezoneInterface;
use Magento\Framework\Validator\EmailAddress;
use Magento\Framework\Validator\NotEmpty;
use Magento\Framework\Validator\ValidatorChain;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\ResolverInterface;
use Magento\GiftRegistry\Model\Search\Results\FilterInputs;
use Magento\Framework\App\ObjectManager;

/**
 * GiftRegistry Search Results
 */
class Results extends \Magento\GiftRegistry\Controller\Search implements HttpPostActionInterface
{
    /**
     * @var FilterInputs
     */
    private $filterInputs;

    /**
     * @param Context $context
     * @param Registry $coreRegistry
     * @param TimezoneInterface $localeDate
     * @param StoreManagerInterface $storeManager
     * @param ResolverInterface $localeResolver
     * @param FilterInputs|null $filterInputs
     */
    public function __construct(
        Context $context,
        Registry $coreRegistry,
        TimezoneInterface $localeDate,
        StoreManagerInterface $storeManager,
        ResolverInterface $localeResolver,
        FilterInputs $filterInputs = null
    ) {
        $this->filterInputs = $filterInputs ?? ObjectManager::getInstance()->get(
            FilterInputs::class
        );
        parent::__construct($context, $coreRegistry, $localeDate, $storeManager, $localeResolver);
    }

    /**
     * Get current customer session
     *
     * @return \Magento\Customer\Model\Session
     */
    protected function _getSession()
    {
        return $this->_objectManager->get(\Magento\Customer\Model\Session::class);
    }

    /**
     * Validate input search params
     *
     * @param array $params
     * @return bool
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     */
    protected function _validateSearchParams($params)
    {
        if (empty($params) || !is_array($params) || empty($params['search'])) {
            $this->messageManager->addNotice(__('Please enter correct search options.'));
            return false;
        }

        switch ($params['search']) {
            case 'type':
                if (empty($params['firstname']) || strlen($params['firstname']) < 2) {
                    $this->messageManager->addNotice(__('Please enter at least 2 letters of the first name.'));
                    return false;
                }
                if (empty($params['lastname']) || strlen($params['lastname']) < 2) {
                    $this->messageManager->addNotice(__('Please enter at least 2 letters of the last name.'));
                    return false;
                }
                break;

            case 'email':
                if (empty($params['email']) || !ValidatorChain::is($params['email'], EmailAddress::class)) {
                    $this->messageManager->addNotice(__('Please enter a valid email address.'));
                    return false;
                }
                break;

            case 'id':
                if (empty($params['id'])) {
                    $this->messageManager->addNotice(__('Please enter a gift registry ID.'));
                    return false;
                }
                break;

            default:
                $this->messageManager->addNotice(__('Please enter correct search options.'));
                return false;
        }
        return true;
    }

    /**
     * Index action
     *
     * @return void
     */
    public function execute()
    {
        $this->_view->loadLayout();

        $params = $this->getRequest()->getParam('params');
        if ($params) {
            $this->_getSession()->setRegistrySearchData($params);
        } else {
            $params = $this->_getSession()->getRegistrySearchData();
        }

        if ($this->_validateSearchParams($params)) {
            $results = $this->_objectManager->create(
                \Magento\GiftRegistry\Model\Entity::class
            )->getCollection()->applySearchFilters(
                $this->filterInputs->filterInputParams(
                    $params,
                    (isset($params['type_id']) ? $this->_initType($params['type_id']) : null)
                )
            );

            $this->_view->getLayout()->getBlock('giftregistry.search.results')->setSearchResults($results);
        } else {
            $this->_redirect('*/*/index', ['_current' => true]);
            return;
        }
        $this->_view->getPage()->getConfig()->getTitle()->set(__('Gift Registry Search'));
        $this->_view->renderLayout();
    }
}
