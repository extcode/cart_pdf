<?php

declare(strict_types=1);

$EM_CONF['cart_pdf'] = [
    'title' => 'Cart - PDF',
    'description' => 'Shopping Cart(s) for TYPO3 - PDF Renderer for Invoices and Delivery Notes',
    'category' => 'services',
    'version' => '7.0.0',
    'state' => 'beta',
    'author' => 'Daniel Gohlke',
    'author_email' => 'ext@extco.de',
    'constraints' => [
        'depends' => [
            'typo3' => '12.4.0-12.4.99',
            'cart'  => '10.3.0-10.99.99',
            'tcpdf' => '6.7.0-6.99.99',
        ],
        'conflicts' => [],
        'suggests'  => [],
    ],
];
