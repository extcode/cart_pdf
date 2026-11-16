.. include:: ../../Includes.rst.txt

==================================================
Feature: Show amount brought forward on each page
==================================================

Description
===========

If the products of an order do not fit on one page, the product table starts
on each page but the first one with the amount brought forward from the
previous page, e.g. "Brought forward from page 1". The amount equals the
subtotal at the end of the previous page.

The row is rendered with the new template ``Order/BroughtForward.html`` of the
document, e.g. ``InvoicePdf/Order/BroughtForward.html``. The template gets the
following variables:

*  ``orderItem``: the order
*  ``orderSettings``: the TypoScript settings of ``body.order``
*  ``products``: the products on the previous pages
*  ``subtotal.net`` and ``subtotal.gross``: the sum of these products
*  ``previousPage``: the number of the previous page

The label is provided by the new language file
``EXT:cart_pdf/Resources/Private/Language/locallang.xlf``.

The feature is disabled by default. Enable it per document, independently of
the subtotal, e.g. for the invoice:

.. code-block:: typoscript

   plugin.tx_cartpdf.invoicePdf.body.order.pageBroughtForward = 1
   module.tx_cartpdf.invoicePdf.body.order.pageBroughtForward = 1

The delivery note has no prices, so its template is empty.

Starting with cart_pdf 9.0 for TYPO3 v14, the feature is enabled by default
for the order and the invoice.

Impact
======

The row of the extension matches the product table of the extension: five
columns, the label spans the first four. If own templates change the number
of columns or the style of the table, add an own
``Order/BroughtForward.html`` that matches them, e.g. by adapting the
``colspan``.

With ``body.order.pageBroughtForward = 0`` the row is not rendered.

.. index:: Frontend, Backend
