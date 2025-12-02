<?php

namespace Extcode\CartPdf\Service;

use Extcode\Cart\Domain\Model\Order\Item as OrderItem;

interface DocumentRenderServiceInterface
{
    public function renderDocument(OrderItem $orderItem, string $pdfType): string;
}
