<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Staging\Model\Update;

/**
 * Includes for updates
 */
class Includes implements IncludesInterface
{
    /**
     * @inheritdoc
     */
    public function getCountSql()
    {
        return new \Zend_Db_Expr('count(1)');
    }

    /**
     * @inheritdoc
     */
    public function getGroupByFields()
    {
        return ['created_in'];
    }
}
