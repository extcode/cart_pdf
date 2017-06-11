<?php

namespace Extcode\CartPdf\Domain\Model\Dto;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Data Transfer Object PdfDemand
 *
 * @author Daniel Lorenz <ext.cart@extco.de>
 */
class PdfDemand extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * Debug
     *
     * @var int
     */
    protected $debug = 0;

    /**
     * Font Size
     *
     * @var int
     */
    protected $fontSize = 8;

    /**
     * Are Fold Marks Enabled
     *
     * @var bool
     */
    protected $foldMarksEnabled = true;

    /**
     * Are Address Field Marks Enabled
     *
     * @var bool
     */
    protected $addressFieldMarksEnabled = true;

    /**
     * Get Debug
     *
     * @return int
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * Set Debug
     *
     * @param int $debug
     */
    public function setDebug($debug)
    {
        if ($debug) {
            $this->debug = $debug;
        }
    }

    /**
     * Get Font Size
     *
     * @return int
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * Set Font Size
     *
     * @param int $fontSize
     */
    public function setFontSize($fontSize)
    {
        if ($fontSize) {
            $this->fontSize = $fontSize;
        }
    }

    /**
     * Returns true when Fold Marks are Enabled
     *
     * @return bool
     */
    public function getFoldMarksEnabled()
    {
        return $this->foldMarksEnabled;
    }

    /**
     * Set Fold Marks are Enabled
     *
     * @param bool $foldMarksEnabled
     */
    public function setFoldMarksEnabled($foldMarksEnabled)
    {
        $this->foldMarksEnabled = $foldMarksEnabled;
    }

    /**
     * Returns true when Address Field are Enabled
     *
     * @return bool
     */
    public function getAddressFieldMarksEnabled()
    {
        return $this->addressFieldMarksEnabled;
    }

    /**
     * Set Address Field Marks are Enabled
     *
     * @param bool $addressFieldMarksEnabled
     */
    public function setAddressFieldMarksEnabled($addressFieldMarksEnabled)
    {
        $this->addressFieldMarksEnabled = $addressFieldMarksEnabled;
    }
}
