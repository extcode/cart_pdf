<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Service;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Domain\Model\Order\Item as OrderItem;

interface FileWriterServiceInterface
{
    public function writeContentToFile(OrderItem $orderItem, string $pdfType, string $fileContent): void;
}
