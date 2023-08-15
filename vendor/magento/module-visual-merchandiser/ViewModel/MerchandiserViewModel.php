<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VisualMerchandiser\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;

class MerchandiserViewModel implements ArgumentInterface
{
    public const SORTABLE_ENABLED = 'enabled';
    public const SORTABLE_DISABLED = 'disabled';

    /**
     * Flag to determine if sortable product positions should be enabled
     *
     * @var string
     */
    private $sortable = self::SORTABLE_ENABLED;

    /**
     * Returns 'sortable' flag
     *
     * @return string
     */
    public function getSortable()
    {
        return $this->sortable;
    }
}
