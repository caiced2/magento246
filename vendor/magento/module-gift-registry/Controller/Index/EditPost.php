<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

declare(strict_types=1);

namespace Magento\GiftRegistry\Controller\Index;

use Magento\GiftRegistry\Controller\Index;
use Magento\Customer\Model\Address;
use Magento\Customer\Model\Customer;
use Magento\Customer\Model\Session;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\GiftRegistry\Helper\Data;
use Magento\GiftRegistry\Model\Entity;
use Magento\GiftRegistry\Model\Person;
use Psr\Log\LoggerInterface;

/**
 * EditPost class is used to add or update gift registry
 *
 * @SuppressWarnings(PHPMD.AllPurposeAction)
 */
class EditPost extends Index
{
    /**
     * Strip tags from received data
     *
     * @param string|array $data
     * @return string|array
     */
    protected function _filterPost($data)
    {
        if (!is_array($data)) {
            return strip_tags($data);
        }
        foreach ($data as &$field) {
            if (!empty($field)) {
                if (!is_array($field)) {
                    $field = strip_tags($field);
                } else {
                    $field = $this->_filterPost($field);
                }
            }
        }
        return $data;
    }

    /**
     * Create gift registry action
     *
     * @return void|ResponseInterface
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function execute()
    {
        if (!($typeId = $this->getRequest()->getParam('type_id'))) {
            $this->_redirect('*/*/addselect');
            return;
        }

        if (!$this->_formKeyValidator->validate($this->getRequest())) {
            $this->_redirect('*/*/edit', ['type_id', $typeId]);
            return;
        }

        if ($this->getRequest()->isPost() && ($data = $this->getRequest()->getPostValue())) {
            $entityId = $this->getRequest()->getParam('entity_id');
            $isError = false;
            $isAddAction = true;
            try {
                /* @var $model Entity */
                $model = $this->_initEntity('entity_id');
                if ($entityId) {
                    $isAddAction = false;
                }
                if ($isAddAction) {
                    $entityId = null;
                    $model = $this->_objectManager->create(Entity::class);
                    if ($model->setTypeById($typeId) === false) {
                        throw new LocalizedException(__('The type is incorrect. Verify and try again.'));
                    }
                }

                $data = $this->_objectManager->get(
                    Data::class
                )->filterDatesByFormat(
                    $data,
                    $model->getDateFieldArray()
                );
                $data = $this->_filterPost($data);
                $this->getRequest()->setPostValue($data);
                $model->importData($data, $isAddAction);

                $registrantsPost = $this->getRequest()->getPost('registrant');
                $persons = [];
                foreach ($registrantsPost as $registrant) {
                    /* @var $person Person */
                    $person = $this->_objectManager->create(Person::class);
                    $idField = $person->getIdFieldName();
                    if (!empty($registrant[$idField])) {
                        $person->load($registrant[$idField]);
                        if (!$person->getId()) {
                            throw new LocalizedException(
                                __('The registrant data is incorrect. Verify and try again.')
                            );
                        }
                    } else {
                        unset($registrant['person_id']);
                    }
                    $person->setData($registrant);
                    $errors = $person->validate();
                    if ($errors !== true) {
                        foreach ($errors as $err) {
                            $this->messageManager->addError($err);
                        }
                        $isError = true;
                    } else {
                        $persons[] = $person;
                    }
                }
                $addressTypeOrId = $this->getRequest()->getParam('address_type_or_id');
                if (!$addressTypeOrId || $addressTypeOrId === Data::ADDRESS_NEW) {
                    // creating new address
                    if (!empty($data['address'])) {
                        /* @var $address Address */
                        $address = $this->_objectManager->create(Address::class);
                        $address->setData($data['address']);
                        $errors = $address->validate();
                        $model->importAddress($address);
                    } else {
                        throw new LocalizedException(__("The address can't be empty. Enter and try again."));
                    }
                    if ($errors !== true) {
                        foreach ($errors as $err) {
                            $this->messageManager->addError($err);
                        }
                        $isError = true;
                    }
                } elseif ($addressTypeOrId !== Data::ADDRESS_NONE) {
                    // using one of existing Customer addresses
                    $addressId = $addressTypeOrId;
                    if (!$addressId) {
                        throw new LocalizedException(__('An address needs to be selected. Select and try again.'));
                    }
                    /* @var $customer Customer */
                    $customer = $this->_objectManager->get(Session::class)->getCustomer();

                    $address = $customer->getAddressItemById($addressId);
                    if (!$address) {
                        throw new LocalizedException(__('The address is incorrect. Verify and try again.'));
                    }
                    $model->importAddress($address);
                }
                $errors = $model->validate();
                if ($errors !== true) {
                    foreach ($errors as $err) {
                        $this->messageManager->addError($err);
                    }
                    $isError = true;
                }

                if (!$isError) {
                    $model->save();
                    $this->updateRegistrantsForEntity($model, $persons, $isAddAction);
                    $this->messageManager->addSuccess(__('You saved this gift registry.'));
                    if ($isAddAction) {
                        $model->sendNewRegistryEmail();
                    }
                }
            } catch (LocalizedException $e) {
                $this->messageManager->addError($e->getMessage());
                $isError = true;
            } catch (\Exception $e) {
                $this->messageManager->addError(__("We couldn't save this gift registry."));
                $this->_objectManager->get(LoggerInterface::class)->critical($e);
                $isError = true;
            }

            if ($isError) {
                $this->_getSession()->setGiftRegistryEntityFormData($this->getRequest()->getPostValue());
                $params = $isAddAction ? ['type_id' => $typeId] : ['entity_id' => $entityId];
                return $this->_redirect('*/*/edit', $params);
            } else {
                $this->_redirect('*/*/');
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * Update registrants for a gift registry
     *
     * @param Entity $entity
     * @param array $persons
     * @param bool $isAddAction
     * @return void
     */
    private function updateRegistrantsForEntity(Entity $entity, array $persons, bool $isAddAction): void
    {
        $entityId = $entity->getId();
        $personLeft = [];
        $registrants = $this->getRegistrantsForEntity($entity);
        foreach ($persons as $person) {
            $person->setEntityId($entityId);
            // Checking gift registry with only existing registrants
            if (!$isAddAction) {
                if ($person->getId() !== null && !in_array($person->getId(), $registrants)) {
                    continue;
                }
            }
            $person->save();
            $personLeft[] = $person->getId();
        }
        // Remove orphan persons
        if (!$isAddAction && !empty($personLeft)) {
            $this->_objectManager->create(
                Person::class
            )->getResource()->deleteOrphan(
                $entityId,
                $personLeft
            );
        }
    }

    /**
     * Get registrants for a gift registry
     *
     * @param Entity $entity
     * @return array $registrants
     */
    private function getRegistrantsForEntity(Entity $entity): array
    {
        $registrants = [];
        foreach ($entity->getRegistrantsCollection() as $registrant) {
            $registrants[] = $registrant->getPersonId();
        }

        return $registrants;
    }
}
