<?php

declare(strict_types=1);

namespace Extcode\CartPdf\Domain\Model\Dto;

/*
 * This file is part of the package extcode/cart-pdf.
 *
 * For the full copyright and license information, please read the
 * LICENSE file that was distributed with this source code.
 */

use TYPO3\CMS\Extbase\DomainObject\AbstractEntity;

class PdfDemand extends AbstractEntity
{
    protected int $debug = 0;

    protected int $fontSize = 8;

    protected bool $foldMarksEnabled = true;

    protected bool $addressFieldMarksEnabled = true;

    public function getDebug(): int
    {
        return $this->debug;
    }

    public function setDebug(int $debug): void
    {
        if ($debug) {
            $this->debug = $debug;
        }
    }

    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    public function setFontSize(int $fontSize): void
    {
        if ($fontSize) {
            $this->fontSize = $fontSize;
        }
    }

    public function getFoldMarksEnabled(): bool
    {
        return $this->foldMarksEnabled;
    }

    public function setFoldMarksEnabled(bool $foldMarksEnabled): void
    {
        $this->foldMarksEnabled = $foldMarksEnabled;
    }

    public function getAddressFieldMarksEnabled(): bool
    {
        return $this->addressFieldMarksEnabled;
    }

    public function setAddressFieldMarksEnabled(bool $addressFieldMarksEnabled): void
    {
        $this->addressFieldMarksEnabled = $addressFieldMarksEnabled;
    }
}
