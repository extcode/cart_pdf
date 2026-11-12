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
    public const DEFAULT_DEBUG = 0;
    public const DEFAULT_FONT_SIZE = 8;
    public const DEFAULT_FOLD_MARKS_ENABLED = false;
    public const DEFAULT_ADDRESS_FIELD_MARKS_ENABLED = false;

    public function __construct(
        private readonly int $debug = self::DEFAULT_DEBUG,
        private int $fontSize = self::DEFAULT_FONT_SIZE,
        private bool $foldMarksEnabled = self::DEFAULT_FOLD_MARKS_ENABLED,
        private bool $addressFieldMarksEnabled = self::DEFAULT_ADDRESS_FIELD_MARKS_ENABLED,
    ) {}

    public static function createFromSettings(array $settings): self
    {
        return new self(
            (int)($settings['debug'] ?? self::DEFAULT_DEBUG),
            (int)($settings['fontSize'] ?? self::DEFAULT_FONT_SIZE),
            (bool)($settings['enableFoldMarks'] ?? self::DEFAULT_FOLD_MARKS_ENABLED),
            (bool)($settings['enableAddressFieldMarks'] ?? self::DEFAULT_ADDRESS_FIELD_MARKS_ENABLED)
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
