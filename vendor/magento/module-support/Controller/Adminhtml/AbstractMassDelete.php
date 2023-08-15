<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Support\Controller\Adminhtml;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

/**
 * Abstract class for Mass Delete action
 */
abstract class AbstractMassDelete extends \Magento\Backend\App\Action
{
    /**
     * Redirect url
     */
    const REDIRECT_URL = '*/*/';

    /**
     * Resource collection
     *
     * @var string
     */
    protected $collection = \Magento\Support\Model\ResourceModel\Backup\Collection::class;

    /**
     * @var string
     */
    protected $model = \Magento\Support\Model\Backup::class;

    /**
     * Process mass deletion of selected or excluded items
     *
     * @param array|null $selected
     * @param array|null|string $excluded
     * @return void
     */
    protected function processItems($selected, $excluded)
    {
        if (isset($excluded)) {
            if (!empty($excluded) && is_array($excluded)) {
                $this->deleteExcludedItems($excluded);
            } else {
                $this->deleteAll();
            }
        } elseif (!empty($selected)) {
            $this->deleteSelectedItems($selected);
        } else {
            $this->messageManager->addError(__('An item needs to be selected. Select and try again.'));
        }
    }

    /**
     * Delete all
     *
     * @return void
     */
    protected function deleteAll()
    {
        /** @var AbstractCollection $collection */
        $collection = $this->_objectManager->get($this->collection);
        $this->setSuccessMessage($this->delete($collection));
    }

    /**
     * Delete all but the not selected
     *
     * @param array $excluded
     * @return void
     */
    protected function deleteExcludedItems(array $excluded)
    {
        /** @var AbstractCollection $collection */
        $collection = $this->_objectManager->get($this->collection);
        $collection->addFieldToFilter(static::ID_FIELD, ['nin' => $excluded]);
        $this->setSuccessMessage($this->delete($collection));
    }

    /**
     * Delete selected items
     *
     * @param array $selected
     * @return void
     */
    protected function deleteSelectedItems(array $selected)
    {
        /** @var AbstractCollection $collection */
        $collection = $this->_objectManager->get($this->collection);
        $collection->addFieldToFilter(static::ID_FIELD, ['in' => $selected]);
        $this->setSuccessMessage($this->delete($collection));
    }

    /**
     * Delete collection items
     *
     * @param AbstractCollection $collection
     * @return int
     */
    protected function delete(AbstractCollection $collection)
    {
        $count = 0;
        foreach ($collection->getAllIds() as $id) {
            /** @var \Magento\Framework\Model\AbstractModel $model */
            $model = $this->_objectManager->get($this->model);
            $model->load($id);
            $model->delete();
            ++$count;
        }

        return $count;
    }

    /**
     * Set success message
     *
     * @param int $count
     * @return void
     */
    protected function setSuccessMessage($count)
    {
        $this->messageManager->addSuccess(__('A total of %1 record(s) have been deleted.', $count));
    }
}
