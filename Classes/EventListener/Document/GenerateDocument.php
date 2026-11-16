<?php

declare(strict_types=1);

namespace Extcode\CartPdf\EventListener\Document;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use Extcode\Cart\Event\Document\GenerateDocumentEvent;
use Extcode\CartPdf\Service\DocumentRenderServiceInterface;
use Extcode\CartPdf\Service\FileWriterServiceInterface;

/**
 * Generates the document requested in the order backend module of cart and
 * attaches it to the order item. Persisting the order item is left to cart.
 */
final readonly class GenerateDocument
{
    public function __construct(
        private DocumentRenderServiceInterface $documentRenderService,
        private FileWriterServiceInterface $fileWriterService,
    ) {}

    public function __invoke(GenerateDocumentEvent $event): void
    {
        $orderItem = $event->getOrderItem();
        $documentType = $event->getType();

        $this->fileWriterService->writeContentToFile(
            $orderItem,
            $documentType,
            $this->documentRenderService->renderDocument($orderItem, $documentType)
        );
    }
}
