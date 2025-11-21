<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Domain\Model\Dto;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

class PdfDemand
{
    public function __construct(
        private readonly int $debug = 0,
        private int $fontSize = 8,
        private bool $foldMarksEnabled = true,
        private bool $addressFieldMarksEnabled = true,
    ) {}

    public static function createFromSettings(array $settings): self
    {
        return new self(
            (int)($settings['debug'] ?? 0),
            (int)($settings['fontSize'] ?? 11),
            (bool)($settings['enableFoldMarks'] ?? 0),
            (bool)($settings['enableAddressFieldMarks'] ?? 0)
        );
    }

    public function getDebug(): int
    {
        return $this->debug;
    }

    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    public function getFoldMarksEnabled(): bool
    {
        return $this->foldMarksEnabled;
    }

    public function getAddressFieldMarksEnabled(): bool
    {
        return $this->addressFieldMarksEnabled;
    }
}
