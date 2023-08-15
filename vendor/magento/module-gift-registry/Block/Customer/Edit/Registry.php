<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftRegistry\Block\Customer\Edit;

/**
 * Customer giftregistry edit block
 *
 * @api
 * @since 100.0.2
 */
class Registry extends AbstractEdit
{
    /**
     * Scope Selector 'registry/registrant'
     *
     * @var string
     */
    protected $_prefix = 'registry';

    /**
     * Return array of attributes groupped by group
     *
     * @return array
     * @codeCoverageIgnore
     */
    public function getGroupedRegistryAttributes()
    {
        return $this->getGroupedAttributes();
    }

    /**
     * Return privacy field selector (input type = select)
     *
     * @return string
     */
    public function getIsPublicHtml()
    {
        $options[''] = $this->escapeHtml(__('Please Select'));
        $options += $this->getEntity()->getOptionsIsPublic();
        $value = $this->getEntity()->getIsPublic();
        return $this->getSelectHtml($options, 'is_public', 'is_public', $value, 'required-entry');
    }

    /**
     * Return status field selector (input type = select)
     *
     * @return string
     */
    public function getStatusHtml()
    {
        $options = $this->getEntity()->getOptionsStatus();
        $value = $this->getEntity()->getIsActive();
        return $this->getSelectHtml($options, 'is_active', 'is_active', $value, 'required-entry');
    }
}
