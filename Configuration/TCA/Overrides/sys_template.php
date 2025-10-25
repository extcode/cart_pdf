<?php

declare(strict_types=1);
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;

call_user_func(function(): void {
    ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript',
        'Shopping Cart - Cart Pdf'
    );

    ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript/OrderPdf',
        'Shopping Cart - Cart Pdf - Order PDF'
    );

    ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript/InvoicePdf',
        'Shopping Cart - Cart Pdf - Invoice PDF'
    );

    ExtensionManagementUtility::addStaticFile(
        'cart_pdf',
        'Configuration/TypoScript/DeliveryPdf',
        'Shopping Cart - Cart Pdf - Delivery PDF'
    );
});
