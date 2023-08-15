<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Rma\Controller\Adminhtml\Rma;

use Magento\Backend\App\Action;
use Magento\Backend\Model\View\Result\ForwardFactory;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Response\Http\FileFactory;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Filesystem;
use Magento\Framework\Registry;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Rma\Api\Data\RmaInterface;
use Magento\Rma\Api\RmaRepositoryInterface;
use Magento\Rma\Controller\Adminhtml\Rma as BaseRma;
use Magento\Rma\Model\Rma\RmaDataMapper;
use Magento\Rma\Model\Pdf\Rma as RmaPdf;
use Magento\Rma\Model\Pdf\RmaFactory as RmaPdfFactory;
use Magento\Rma\Model\Shipping\LabelService;
use Magento\Shipping\Helper\Carrier;

/**
 * Print action for RMA functionality.
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PrintAction extends BaseRma implements HttpGetActionInterface
{
    /**
     * @var RmaRepositoryInterface|null
     */
    private $rmaRepository;

    /**
     * @var RmaPdfFactory|null
     */
    private $rmaPdfFactory;

    /**
     * @var DateTime|null
     */
    private $dateTime;

    /**
     * @var ForwardFactory|null
     */
    private $resultForwardFactory;

    /**
     * @param Action\Context $context
     * @param Registry $coreRegistry
     * @param FileFactory $fileFactory
     * @param Filesystem $filesystem
     * @param Carrier $carrierHelper
     * @param LabelService $labelService
     * @param RmaDataMapper $rmaDataMapper
     * @param RmaRepositoryInterface|null $rmaRepository
     * @param RmaPdfFactory|null $rmaPdfFactory
     * @param DateTime|null $dateTime
     * @param ForwardFactory|null $resultForwardFactory
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        Action\Context $context,
        Registry $coreRegistry,
        FileFactory $fileFactory,
        Filesystem $filesystem,
        Carrier $carrierHelper,
        LabelService $labelService,
        RmaDataMapper $rmaDataMapper,
        ?RmaRepositoryInterface $rmaRepository = null,
        ?RmaPdfFactory $rmaPdfFactory = null,
        ?DateTime $dateTime = null,
        ?ForwardFactory $resultForwardFactory = null
    ) {
        parent::__construct(
            $context,
            $coreRegistry,
            $fileFactory,
            $filesystem,
            $carrierHelper,
            $labelService,
            $rmaDataMapper
        );
        $this->rmaRepository = $rmaRepository ?? $this->_objectManager->get(RmaRepositoryInterface::class);
        $this->rmaPdfFactory = $rmaPdfFactory ?? $this->_objectManager->get(RmaPdfFactory::class);
        $this->dateTime = $dateTime ?? $this->_objectManager->get(DateTime::class);
        $this->resultForwardFactory = $resultForwardFactory ?? $this->_objectManager->get(ForwardFactory::class);
    }

    /**
     * Generate PDF form of RMA.
     *
     * @return void|ResponseInterface
     */
    public function execute()
    {
        /** @var $rmaModel RmaInterface */
        $rmaModel = $this->rmaRepository->get((int)$this->getRequest()->getParam('rma_id'));
        if ($rmaModel->getId()) {
            /** @var $pdfModel RmaPdf */
            $pdfModel = $this->rmaPdfFactory->create();
            $pdf = $pdfModel->getPdf([$rmaModel]);
            $fileContent = ['type' => 'string', 'value' => $pdf->render(), 'rm' => true];

            return $this->_fileFactory->create(
                'rma' . $this->dateTime->date('Y-m-d_H-i-s') . '.pdf',
                $fileContent,
                DirectoryList::MEDIA,
                'application/pdf'
            );

        } else {
            return $this->resultForwardFactory->create()->forward('noroute');
        }
    }
}
