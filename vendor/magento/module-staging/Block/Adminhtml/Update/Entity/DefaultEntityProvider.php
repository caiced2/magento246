<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity;

/**
 * Class DefaultEntityProvider
 * @codeCoverageIgnore
 */
class DefaultEntityProvider implements \Magento\Staging\Block\Adminhtml\Update\Entity\EntityProviderInterface
{
    /**
     * {@inheritDoc}
     */
    public function getId()
    {
        return null;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getUrl($updateId)
    {
        return null;
    }
}
