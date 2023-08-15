<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\CatalogEvent\Block\Adminhtml\Catalog\Category\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Catalog\Block\Adminhtml\Category\AbstractCategory;
use Magento\CatalogEvent\Model\Event;
use Magento\CatalogEvent\Model\ResourceModel\Event\Collection;
use Magento\CatalogEvent\Model\ResourceModel\Event\CollectionFactory;

/**
 * Class AddEventButton
 * @api
 * @since 100.1.0
 */
class AddEventButton extends AbstractCategory implements ButtonProviderInterface
{
    /**
     * @var \Magento\Framework\AuthorizationInterface
     * @since 100.1.0
     */
    protected $authorization;

    /**
     * @var CollectionFactory
     * @since 100.1.0
     */
    protected $collectionFactory;

    /**
     * @param \Magento\Backend\Block\Template\Context $context
     * @param \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree
     * @param \Magento\Framework\Registry $registry
     * @param \Magento\Catalog\Model\CategoryFactory $categoryFactory
     * @param CollectionFactory $collectionFactory
     * @param array $data
     */
    public function __construct(
        \Magento\Backend\Block\Template\Context $context,
        \Magento\Catalog\Model\ResourceModel\Category\Tree $categoryTree,
        \Magento\Framework\Registry $registry,
        \Magento\Catalog\Model\CategoryFactory $categoryFactory,
        CollectionFactory $collectionFactory,
        array $data = []
    ) {
        $this->authorization = $context->getAuthorization();
        parent::__construct($context, $categoryTree, $registry, $categoryFactory, $data);
        $this->collectionFactory = $collectionFactory;
    }

    /**
     * Add/Edit event button
     *
     * @return array
     * @since 100.1.0
     */
    public function getButtonData()
    {
        $category = $this->getCategory();
        if (!$category) {
            return [];
        }
        $categoryId = (int)$category->getId();
        if ($categoryId
            && $this->getCategory()->getLevel() > 1
            && $this->_authorization->isAllowed('Magento_CatalogEvent::events')
        ) {
            if ($this->getEvent() && $this->getEvent()->getId()) {
                return $this->getEditButton();
            } else {
                return $this->getCreateButton();
            }
        }
        return [];
    }

    /**
     * Get Edit event button configuration
     *
     * @return array
     * @since 100.1.0
     */
    protected function getEditButton()
    {
        return [
            'id' => 'edit_event',
            'label' => __('Edit Event'),
            'on_click' => "setLocation('" . $this->getEditCategoryEventUrl() . "')",
            'class' => 'action-secondary action-event-edit',
            'sort_order' => 15
        ];
    }

    /**
     * Get Create event button configuration
     *
     * @return array
     * @since 100.1.0
     */
    protected function getCreateButton()
    {
        return [
            'id' => 'add_event',
            'label' => __('Add Event'),
            'on_click' => "setLocation('" . $this->getNewCategoryEventUrl() . "')",
            'class' => 'action-secondary action-event-add',
            'sort_order' => 15
        ];
    }

    /**
     * Get create event for selected category URL
     *
     * @return string
     * @since 100.1.0
     */
    public function getNewCategoryEventUrl()
    {
        return $this->getUrl(
            'adminhtml/catalog_event/new/',
            [
                '_current' => false,
                '_query' => ['isAjax' => null],
                'category' => 1,
                'category_id' => (int)$this->getCategory()->getId(),
            ]
        );
    }

    /**
     * Get edit event for selected category url
     *
     * @return string
     * @since 100.1.0
     */
    public function getEditCategoryEventUrl()
    {
        return $this->getUrl(
            'adminhtml/catalog_event/edit/',
            [
                '_current' => false,
                '_query' => ['isAjax' => null],
                'id' => (int)$this->getEvent()->getId(),
            ]
        );
    }

    /**
     * Retrieve category event
     *
     * @return Event
     * @since 100.1.0
     */
    public function getEvent()
    {
        if (!$this->hasData('event')) {
            /** @var Collection $collection */
            $collection = $this->collectionFactory->create()->addFieldToFilter(
                'category_id',
                $this->getCategory()->getId()
            );

            $event = $collection->getFirstItem();
            $this->setData('event', $event);
        }

        return $this->getData('event');
    }
}
