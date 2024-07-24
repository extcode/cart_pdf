<?php

declare(strict_types=1);
defined('TYPO3_MODE') or die();

call_user_func(function (): void {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript',
        'Shopping Cart - Cart Pdf'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript/OrderPdf',
        'Shopping Cart - Cart Pdf - Order PDF'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript/InvoicePdf',
        'Shopping Cart - Cart Pdf - Invoice PDF'
    );

    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript/DeliveryPdf',
        'Shopping Cart - Cart Pdf - Delivery PDF'
    );
});
