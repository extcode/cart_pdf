<?php

declare(strict_types=1);

$EM_CONF['cart_pdf'] = [
    'title'            => 'Cart - PDF',
    'description'      => 'Shopping Cart(s) for TYPO3 - PDF Renderer for Invoices and Delivery Notes',
    'category'         => 'services',
    'author'           => 'Daniel Gohlke',
    'author_email'     => 'ext.cart@extco.de',
    'author_company'   => 'extco.de UG (haftungsbeschränkt)',
    'state'            => 'beta',
    'version'          => '5.0.0',
    'constraints'      => [
        'depends' => [
            'typo3' => '13.4.0-13.4.99',
            'cart'  => '11.5.0',
            'tcpdf' => '6.10.0',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
