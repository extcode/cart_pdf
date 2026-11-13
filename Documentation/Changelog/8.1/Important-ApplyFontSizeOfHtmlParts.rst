.. include:: ../../Includes.rst.txt

==============================================
Important: Apply configured font size of parts
==============================================

Description
===========

The ``fontSize`` configured for a single part of a document was never
applied, because the condition in
``\Extcode\CartPdf\Service\TcpdfWrapper::writeHtmlCellWithConfig()`` was
inverted. All parts were rendered with the font size of the whole document
(``plugin.tx_cartpdf.<type>.fontSize``), a ``fontSize`` of ``0`` set the font
size to ``0``.

The configured ``fontSize`` of a part is applied now, a missing or empty
``fontSize`` keeps the font size of the document.

Affected Installations
======================

All installations are affected. Generated documents can look different now,
for example with the default configuration:

*  The sender line in the address window (``letterhead.html.envelopeAddress``,
   ``fontSize = 6``) fits into one line.
*  Header and footer parts (``fontSize = 8``) are rendered smaller than the
   document font size of ``10``.
*  The overview before the order table (``body.before.html.overview``,
   ``fontSize = 12``) is rendered larger.

Installations with an own configuration of the parts get the font sizes
configured there.

Migration
=========

Check the generated documents. If a part should keep the document font size,
remove its ``fontSize`` setting or set it to the same value as the
``fontSize`` of the document.

.. index:: TypoScript, Frontend
