<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity\Create;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionHistoryInterface;

/**
 * Class for start time field
 */
class StartTime extends Field
{
    /**
     * @var VersionHistoryInterface
     */
    private $versionHistory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param VersionHistoryInterface $versionHistory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        VersionHistoryInterface $versionHistory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->versionHistory = $versionHistory;
    }

    /**
     * @inheritdoc
     */
    public function prepare()
    {
        parent::prepare();
        $updateId = $this->getContext()->getRequestParam('update_id', null);
        if ($updateId && $updateId <= $this->versionHistory->getCurrentId()) {
            $data = $this->getData();
            $data['config']['disabled'] = 1;
            $this->setData($data);
        }
    }
}
