<?php
/**
 * Plugin for \Magento\Framework\Mview\View\StateInterface model
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\CatalogPermissions\Model\Indexer\Plugin;

class MviewState
{
    /**
     * State instance
     *
     * @var \Magento\Framework\Mview\View\StateInterface
     */
    protected $state;

    /**
     * Changelog instance
     *
     * @var \Magento\Framework\Mview\View\ChangelogInterface
     */
    protected $changelog;

    /**
     * Related indexers IDs
     *
     * @var int[]
     */
    protected $viewIds = [
        \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID,
        \Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID,
    ];

    /**
     * Constructor
     *
     * @param \Magento\Framework\Mview\View\StateInterface $state
     * @param \Magento\Framework\Mview\View\ChangelogInterface $changelog
     */
    public function __construct(
        \Magento\Framework\Mview\View\StateInterface $state,
        \Magento\Framework\Mview\View\ChangelogInterface $changelog
    ) {
        $this->state = $state;
        $this->changelog = $changelog;
    }

    /**
     * Synchronize status for view
     *
     * @param \Magento\Framework\Mview\View\StateInterface $state
     * @return \Magento\Framework\Mview\View\StateInterface
     */
    public function afterSetStatus(\Magento\Framework\Mview\View\StateInterface $state)
    {
        if (in_array($state->getViewId(), $this->viewIds)) {
            $viewId = ($state->getViewId() == \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID)
                ? \Magento\CatalogPermissions\Model\Indexer\Product::INDEXER_ID
                : \Magento\CatalogPermissions\Model\Indexer\Category::INDEXER_ID;

            $relatedState = $this->state->loadByView($viewId);

            // If equals nothing to change
            if ($relatedState->getMode() == \Magento\Framework\Mview\View\StateInterface::MODE_DISABLED
                || $state->getStatus() == $relatedState->getStatus()
            ) {
                return $state;
            }

            // Suspend
            if ($state->getStatus() == \Magento\Framework\Mview\View\StateInterface::STATUS_SUSPENDED) {
                $relatedState->setStatus(\Magento\Framework\Mview\View\StateInterface::STATUS_SUSPENDED);
                $relatedState->setVersionId($this->changelog->setViewId($viewId)->getVersion());
                $relatedState->save();
            } else {
                if ($relatedState->getStatus() == \Magento\Framework\Mview\View\StateInterface::STATUS_SUSPENDED) {
                    $relatedState->setStatus(\Magento\Framework\Mview\View\StateInterface::STATUS_IDLE);
                    $relatedState->save();
                }
            }
        }

        return $state;
    }
}
