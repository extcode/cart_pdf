<?php

declare(strict_types=1);

/*
 * (c) 2025 rc design visual concepts (rc-design.at)
 * _________________________________________________
 * The TYPO3 project - inspiring people to share!
 * _________________________________________________
 */

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
    protected int $debugMode = 0;
    protected int $fontSize = 8;
    protected bool $foldMarksEnabled = true;
    protected bool $addressFieldMarksEnabled = true;

    public function getDebugMode(): int
    {
        return $this->debugMode;
    }

    public function setDebugMode(int $debugMode): void
    {
        if ($debugMode > 0) {
            $this->debugMode = $debugMode;
        }
    }

    // Legacy getter for backward compatibility (optional)
    public function getDebug(): int
    {
        return $this->getDebugMode();
    }

    // Legacy setter for backward compatibility (optional)
    public function setDebug(int $debug): void
    {
        $this->setDebugMode($debug);
    }

    public function getFontSize(): int
    {
        return $this->fontSize;
    }

    public function setFontSize(int $fontSize): void
    {
        if ($fontSize > 0) {
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
