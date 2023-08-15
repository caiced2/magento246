<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Invitation grid collection
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Magento\Invitation\Model\ResourceModel\Invitation\Grid;

class Collection extends \Magento\Invitation\Model\ResourceModel\Invitation\Collection
{
    /**
     * Join website ID and referrals information (email)
     *
     * @return \Magento\Invitation\Model\ResourceModel\Invitation\Collection
     * |\Magento\Invitation\Model\ResourceModel\Invitation\Grid\Collection
     */
    protected function _initSelect()
    {
        parent::_initSelect();
        $this->addWebsiteInformation()->addInviteeInformation();
        return $this;
    }
}
