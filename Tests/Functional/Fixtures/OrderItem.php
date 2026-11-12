<?php

declare(strict_types=1);

use Extcode\Cart\Domain\Model\Order\BillingAddress;

/*
 * One order with an invoice, containing special characters in the address
 * and the product titles to check the readability of the rendered PDF.
 */
return [
    'tx_cart_domain_model_order_item' => [
        [
            'uid' => 1,
            'pid' => 1,
            'billing_address' => 1,
            'shipping_address' => 0,
            'order_number' => 'ORD-2026-0815',
            'order_date' => (new DateTimeImmutable('2026-11-10 09:30:00 UTC'))->getTimestamp(),
            'invoice_number' => 'RE-2026-0042',
            'invoice_date' => (new DateTimeImmutable('2026-11-12 12:00:00 UTC'))->getTimestamp(),
            'delivery_number' => 'LS-2026-0007',
            'delivery_date' => (new DateTimeImmutable('2026-11-13 12:00:00 UTC'))->getTimestamp(),
            'currency' => '€',
            'currency_code' => 'EUR',
            'currency_sign' => '€',
            'gross' => 1017.99,
            'net' => 855.46,
            'total_gross' => 1022.89,
            'total_net' => 859.58,
            'tax' => 1,
            'total_tax' => 0,
            'tax_class' => 1,
            'products' => 2,
            'discounts' => 0,
            'shipping' => 1,
            'payment' => 1,
        ],
    ],
    'tx_cart_domain_model_order_address' => [
        [
            'uid' => 1,
            'pid' => 1,
            'record_type' => '\\' . BillingAddress::class,
            'item' => 1,
            'company' => 'Müller & Söhne GmbH',
            'first_name' => 'Jürgen',
            'last_name' => 'Weiß',
            'street' => 'Königsallee 5',
            'zip' => '40212',
            'city' => 'Düsseldorf',
            'country' => 'de',
        ],
    ],
    'tx_cart_domain_model_order_taxclass' => [
        [
            'uid' => 1,
            'pid' => 1,
            'item' => 1,
            'title' => 'normal',
            'value' => '19',
            'calc' => 0.19,
        ],
    ],
    'tx_cart_domain_model_order_tax' => [
        [
            'uid' => 1,
            'pid' => 1,
            'item' => 1,
            'record_type' => 'tax',
            'tax' => 162.53,
            'tax_class' => 1,
        ],
    ],
    'tx_cart_domain_model_order_product' => [
        [
            'uid' => 1,
            'pid' => 1,
            'item' => 1,
            'product_type' => 'simple',
            'sku' => 'CHAIR-ÄGÄIS',
            'title' => 'Größenverstellbarer Bürostuhl „Ägäis“',
            'count' => 2,
            'price' => 499.00,
            'gross' => 998.00,
            'net' => 838.66,
            'tax' => 159.34,
            'tax_class' => 1,
        ],
        [
            'uid' => 2,
            'pid' => 1,
            'item' => 1,
            'product_type' => 'simple',
            'sku' => 'CABLE-USB-C',
            'title' => 'Kabel & Adapter <USB-C> für 50% günstiger',
            'count' => 1,
            'price' => 19.99,
            'gross' => 19.99,
            'net' => 16.80,
            'tax' => 3.19,
            'tax_class' => 1,
        ],
    ],
    'tx_cart_domain_model_order_shipping' => [
        [
            'uid' => 1,
            'pid' => 1,
            'item' => 1,
            'name' => 'Standardversand',
            'status' => 'open',
            'gross' => 4.90,
            'net' => 4.12,
            'tax' => 0.78,
            'tax_class' => 1,
        ],
    ],
    'tx_cart_domain_model_order_payment' => [
        [
            'uid' => 1,
            'pid' => 1,
            'item' => 1,
            'name' => 'Vorkasse',
            'provider' => 'PREPAYMENT',
            'status' => 'open',
            'gross' => 0.00,
            'net' => 0.00,
            'tax' => 0.00,
            'tax_class' => 1,
        ],
    ],
];
