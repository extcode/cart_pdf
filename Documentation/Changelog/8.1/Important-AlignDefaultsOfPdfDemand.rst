.. include:: ../../Includes.rst.txt

==========================================
Important: Align defaults of PdfDemand DTO
==========================================

Description
===========

The constructor of ``\Extcode\CartPdf\Domain\Model\Dto\PdfDemand`` and
``\Extcode\CartPdf\Domain\Model\Dto\PdfDemand::createFromSettings()`` used
different default values. Both use the same defaults now, provided as
public constants:

=======================================  =========  =====================================
Constant                                 Value      Before
=======================================  =========  =====================================
``DEFAULT_DEBUG``                        ``0``      unchanged
``DEFAULT_FONT_SIZE``                    ``8``      constructor ``8``, factory ``11``
``DEFAULT_FOLD_MARKS_ENABLED``           ``false``  constructor ``true``, factory ``false``
``DEFAULT_ADDRESS_FIELD_MARKS_ENABLED``  ``false``  constructor ``true``, factory ``false``
=======================================  =========  =====================================

The rendered documents do not change. The extension creates the DTO only
via ``createFromSettings()``, whose defaults for the marks are kept. The
font size of the DTO is not used for rendering.

Affected Installations
======================

Only installations which create a ``PdfDemand`` object in their own code are
affected:

*  ``new PdfDemand()`` without arguments disables the fold marks and the
   address field marks now.
*  ``PdfDemand::createFromSettings()`` without a ``fontSize`` setting returns
   ``8`` instead of ``11`` for ``getFontSize()``.

Installations which configure the documents via TypoScript only are not
affected.

Migration
=========

Pass the required values explicitly to the constructor or add the settings
``enableFoldMarks``, ``enableAddressFieldMarks`` and ``fontSize`` to the array
given to ``createFromSettings()``.

.. index:: API
