<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

namespace Magento\Staging\Model\Update;

/**
 * Interface IncludesInterface
 *
 * @api
 */
interface IncludesInterface
{
    /**
     * Retrieve SQL string for count statement
     *
     * @return \Zend_Db_Expr
     */
    public function getCountSql();

    /**
     * Retrieve fields for grouping entity includes
     *
     * @return array
     */
    public function getGroupByFields();
}
