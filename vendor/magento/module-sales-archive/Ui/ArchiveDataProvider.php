<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\SalesArchive\Ui;

use Magento\Framework\Api\FilterBuilder;
use Magento\Framework\Api\Search\ReportingInterface;
use Magento\Framework\Api\Search\SearchCriteriaBuilder;
use Magento\Framework\App\RequestInterface;
use Magento\SalesArchive\Model\ResourceModel\Archive;

class ArchiveDataProvider extends \Magento\Framework\View\Element\UiComponent\DataProvider\DataProvider
{
    /**
     * @var null|string
     */
    private $archiveDataSource;

    /**
     * @var Archive
     */
    private $archiveResourceModel;

    /**
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param ReportingInterface $reporting
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param RequestInterface $request
     * @param FilterBuilder $filterBuilder
     * @param Archive $archiveResourceModel
     * @param array $meta
     * @param array $data
     * @param null|string $archiveDataSource
     *
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        ReportingInterface $reporting,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        RequestInterface $request,
        FilterBuilder $filterBuilder,
        Archive $archiveResourceModel,
        array $meta = [],
        array $data = [],
        $archiveDataSource = null
    ) {
        parent::__construct(
            $name,
            $primaryFieldName,
            $requestFieldName,
            $reporting,
            $searchCriteriaBuilder,
            $request,
            $filterBuilder,
            $meta,
            $data
        );
        $this->archiveDataSource = $archiveDataSource;
        $this->archiveResourceModel = $archiveResourceModel;
    }

    /**
     * Retrieve and populate search criteria.
     *
     * Additional logic is included to support the Order view being shared between active and archived Orders. If the
     * parent order is archived, we need to use different data source configurations that are mapped to archive
     * table locations.
     *
     * @return \Magento\Framework\Api\Search\SearchCriteria
     */
    public function getSearchCriteria()
    {
        parent::getSearchCriteria();

        if ($this->archiveResourceModel->isOrderInArchive($this->request->getParam('order_id'))) {
            $this->searchCriteria->setRequestName($this->archiveDataSource);
        }

        return $this->searchCriteria;
    }
}
