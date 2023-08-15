<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Staging\Block\Adminhtml\Update\Entity;

use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;
use Magento\Staging\Api\UpdateRepositoryInterface;
use Magento\Staging\Block\Adminhtml\Update\IdProvider as UpdateIdProvider;

/**
 * Class RemoveButton
 */
class RemoveButton implements ButtonProviderInterface
{
    /**
     * @var EntityProviderInterface
     */
    protected $entityProvider;

    /**
     * @var UpdateIdProvider
     */
    protected $updateIdProvider;

    /**
     * @var string
     */
    protected $entityIdentifier;

    /**
     * @var string
     */
    protected $jsRemoveModal;

    /**
     * @var string
     */
    protected $jsRemoveLoader;

    /**
     * @var UpdateRepositoryInterface
     */
    private $updateRepository;

    /**
     * @var DateTime
     */
    private $dateTime;

    /**
     * @param EntityProviderInterface $entityProvider
     * @param UpdateIdProvider $updateIdProvider
     * @param string $entityIdentifier
     * @param string $jsRemoveModal
     * @param string $jsRemoveLoader
     */
    public function __construct(
        EntityProviderInterface $entityProvider,
        UpdateIdProvider $updateIdProvider,
        $entityIdentifier,
        $jsRemoveModal,
        $jsRemoveLoader,
        UpdateRepositoryInterface $updateRepository,
        DateTime $dateTime
    ) {
        $this->entityProvider = $entityProvider;
        $this->updateIdProvider = $updateIdProvider;
        $this->entityIdentifier = $entityIdentifier;
        $this->jsRemoveModal = $jsRemoveModal;
        $this->jsRemoveLoader = $jsRemoveLoader;
        $this->updateRepository = $updateRepository;
        $this->dateTime = $dateTime;
    }

    /**
     * @return array
     */
    public function getButtonData()
    {
        $data = [];
        if ($this->canRemoveFromUpdate()) {
            $data = [
                'label' => __('Remove from Update'),
                'class' => 'reset',
                'sort_order' => 30,
                'on_click' => '',
                'data_attribute' => [
                    'mage-init' => [
                        'Magento_Ui/js/form/button-adapter' => [
                            'actions' => [
                                [
                                    'targetName' => $this->jsRemoveModal,
                                    'actionName' => 'openModal'
                                ],
                                [
                                    'targetName' => $this->jsRemoveLoader,
                                    'actionName' => 'render',
                                    'params' => [
                                        [
                                            $this->entityIdentifier => $this->entityProvider->getId(),
                                            'update_id' => $this->updateIdProvider->getUpdateId(),
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ];
        }
        return $data;
    }

    /**
     * Verify if entity can be removed from update
     *
     * @return bool
     */
    private function canRemoveFromUpdate()
    {
        $updateId = $this->updateIdProvider->getUpdateId();
        if (null !== $updateId) {
            $update = $this->updateRepository->get($updateId);
            $startTime = $this->dateTime->gmtTimestamp($update->getStartTime());
            $currentDateTime = $this->dateTime->gmtTimestamp();
            return $currentDateTime < $startTime;
        }
        return false;
    }
}
