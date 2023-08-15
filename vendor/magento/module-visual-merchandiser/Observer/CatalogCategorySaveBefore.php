<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\VisualMerchandiser\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\VisualMerchandiser\Model\Category\Builder;
use Magento\VisualMerchandiser\Model\RulesFactory;

class CatalogCategorySaveBefore implements ObserverInterface
{
    /**
     * @var Builder
     */
    protected $categoryBuilder;

    /**
     * @var RulesFactory
     */
    protected $rulesFactory;

    /**
     * Constructor
     *
     * @param Builder $categoryBuilder
     * @param RulesFactory $rulesFactory
     */
    public function __construct(
        Builder $categoryBuilder,
        RulesFactory $rulesFactory
    ) {
        $this->categoryBuilder = $categoryBuilder;
        $this->rulesFactory = $rulesFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute(Observer $observer)
    {
        /* @var \Magento\Catalog\Model\Category $category */
        $category = $observer->getEvent()->getDataObject();

        // Disable smart category rule after application
        $rules = $this->rulesFactory->create();
        $rule = $rules->loadByCategory($category);
        if ($rule->getId() && $rule->getIsActive()) {
            $this->categoryBuilder->rebuildCategory($category);
            $rule->setData([
                'rule_id' => $rule->getId(),
                'category_id' => $category->getId(),
                'is_active' => $rule->getIsActive()
            ]);
            $rule->save();
        }
    }
}
