<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\VersionsCms\Model;

use Magento\Framework\App\RequestInterface;
use Magento\VersionsCms\Api\Data\HierarchyNodeInterface;

/**
 * Interface for CMS Hierarchy Node resolver.
 * This resolver detects current CMS Hierarchy Node instance based on request.
 *
 * @api
 */
interface CurrentNodeResolverInterface
{
    /**
     * Gets current CMS Hierarchy Node instance
     *
     * Current CMS Hierarchy Node is a node of CMS Hierarchy Tree
     * that corresponds to request.
     *
     * @param RequestInterface $request
     * @return HierarchyNodeInterface|null
     */
    public function get(RequestInterface $request);
}
