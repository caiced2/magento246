<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity;

use Magento\Framework\View\Element\UiComponent\ContextInterface;
use Magento\Framework\View\Element\UiComponentFactory;
use Magento\Ui\Component\Form\Field;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Model\VersionHistoryInterface;

/**
 * Class StartTime
 */
class StartTime extends Field
{
    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var VersionHistoryInterface
     */
    private $versionHistory;

    /**
     * @param ContextInterface $context
     * @param UiComponentFactory $uiComponentFactory
     * @param UpdateRepositoryInterface $updateRepository
     * @param VersionHistoryInterface $versionHistory
     * @param array $components
     * @param array $data
     */
    public function __construct(
        ContextInterface $context,
        UiComponentFactory $uiComponentFactory,
        UpdateRepositoryInterface $updateRepository,
        VersionHistoryInterface $versionHistory,
        array $components = [],
        array $data = []
    ) {
        parent::__construct($context, $uiComponentFactory, $components, $data);
        $this->updateRepository = $updateRepository;
        $this->versionHistory = $versionHistory;
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function prepare()
    {
        parent::prepare();
        $id = $this->getContext()->getRequestParam($this->getContext()->getDataProvider()->getRequestFieldName(), null);

        if ($id <= $this->versionHistory->getCurrentId()) {
            $data = $this->getData();
            $data['config']['disabled'] = 1;
            $this->setData($data);
        }
    }
}
