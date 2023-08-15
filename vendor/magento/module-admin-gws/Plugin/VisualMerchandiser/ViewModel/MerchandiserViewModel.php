<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\AdminGws\Plugin\VisualMerchandiser\ViewModel;

use Magento\VisualMerchandiser\ViewModel\MerchandiserViewModel as ViewModel;
use Magento\AdminGws\Model\Role as AdminRole;

class MerchandiserViewModel
{
    /**
     * @var AdminRole $adminRole
     */
    private $adminRole;

    /**
     * @param AdminRole $adminRole
     */
    public function __construct(
        AdminRole $adminRole
    ) {
        $this->adminRole = $adminRole;
    }

    /**
     * Apply GWS rule merchandiser sortable property
     *
     * @param ViewModel $subject
     * @param string $result
     * @return string|void
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function afterGetSortable(ViewModel $subject, string $result)
    {
        if (!$this->adminRole->getIsAll()) {
            $result = ViewModel::SORTABLE_DISABLED;
        }
        return $result;
    }
}
