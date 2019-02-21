<?php

namespace Extcode\CartPdf\Domain\Model\Dto;

class PdfDemand extends \TYPO3\CMS\Extbase\DomainObject\AbstractEntity
{
    /**
     * @var int
     */
    protected $debug = 0;

    /**
     * @var int
     */
    protected $fontSize = 8;

    /**
     * @var bool
     */
    protected $foldMarksEnabled = true;

    /**
     * @var bool
     */
    protected $addressFieldMarksEnabled = true;

    /**
     * @return int
     */
    public function getDebug()
    {
        return $this->debug;
    }

    /**
     * @param int $debug
     */
    public function setDebug($debug)
    {
        if ($debug) {
            $this->debug = $debug;
        }
    }

    /**
     * @return int
     */
    public function getFontSize()
    {
        return $this->fontSize;
    }

    /**
     * @param int $fontSize
     */
    public function setFontSize($fontSize)
    {
        if ($fontSize) {
            $this->fontSize = $fontSize;
        }
    }

    /**
     * @return bool
     */
    public function getFoldMarksEnabled()
    {
        return $this->foldMarksEnabled;
    }

    /**
     * @param bool $foldMarksEnabled
     */
    public function setFoldMarksEnabled($foldMarksEnabled)
    {
        $this->foldMarksEnabled = $foldMarksEnabled;
    }

    /**
     * @return bool
     */
    public function getAddressFieldMarksEnabled()
    {
        return $this->addressFieldMarksEnabled;
    }

    /**
     * @param bool $addressFieldMarksEnabled
     */
    public function setAddressFieldMarksEnabled($addressFieldMarksEnabled)
    {
        $this->addressFieldMarksEnabled = $addressFieldMarksEnabled;
    }
}
