<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
declare(strict_types=1);

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Framework\Encryption\EncryptorInterface;

$objectManager = \Magento\TestFramework\Helper\Bootstrap::getObjectManager();

/**
 * @var AccountManagementInterface $accountManagement
 */
$accountManagement = $objectManager->get(AccountManagementInterface::class);

/**
 * @var EncryptorInterface $encryptor
 */
$encryptor = $objectManager->get(EncryptorInterface::class);

$customer1 = $objectManager->get(CustomerInterface::class);

$customer1->setWebsiteId(1)
    ->setGroupId(1)
    ->setStoreId(1)
    ->setPrefix('Mr.')
    ->setEmail(sha1(uniqid('', true)) . '@example.com')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Smith')
    ->setSuffix('Esq.')
    ->setTaxvat('12')
    ->setGender(0)
    ->setCreatedAt((new DateTime('now'))->sub(new DateInterval('P3M'))->format(DATE_ATOM));

$accountManagement->createAccount($customer1, $encryptor->getHash('Test#123', true));

$customer2 = $objectManager->get(CustomerInterface::class);

$customer2->setWebsiteId(1)
    ->setGroupId(1)
    ->setStoreId(1)
    ->setPrefix('Mr.')
    ->setEmail(sha1(uniqid('', true)) . '@example.com')
    ->setFirstname('John')
    ->setMiddlename('A')
    ->setLastname('Doe')
    ->setSuffix('Esq.')
    ->setTaxvat('12')
    ->setGender(0)
    ->setCreatedAt((new DateTime('now'))->sub(new DateInterval('P14D'))->format(DATE_ATOM));

$accountManagement->createAccount($customer2, $encryptor->getHash('Test#123', true));
