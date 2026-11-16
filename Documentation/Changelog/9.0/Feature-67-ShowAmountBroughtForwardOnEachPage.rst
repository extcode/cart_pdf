.. include:: ../../Includes.rst.txt

=======================================================
Feature: #67 - Show amount brought forward on each page
=======================================================

See `Pull Request #67 <https://github.com/extcode/cart_pdf/pull/67>`__

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

The feature is enabled by default for the order and the invoice. The
delivery note has no prices, so it is disabled there and the template is
empty. It can be enabled independently of the subtotal:

.. code-block:: typoscript

   plugin.tx_cartpdf.invoicePdf.body.order.pageBroughtForward = 1
   module.tx_cartpdf.invoicePdf.body.order.pageBroughtForward = 1

Impact
======

The row of the extension matches the product table of the extension: five
columns, the label spans the first four. If own templates change the number
of columns or the style of the table, add an own
``Order/BroughtForward.html`` that matches them, e.g. by adapting the
``colspan``.

Set ``body.order.pageBroughtForward = 0`` to disable the row.

.. index:: Frontend, Backend
