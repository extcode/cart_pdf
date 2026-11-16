.. include:: ../../Includes.rst.txt

=====================================
Feature: Show subtotal on each page
=====================================

Description
===========

If the products of an order do not fit on one page, the product table ends
on each page but the last one with a page footer, e.g. the subtotal of the
products on this and the previous pages. The table header is repeated on each
page as before. The last page ends with the summary of the order.

The page footer is rendered with the new template ``Order/PageFooter.html``
of the document, e.g. ``InvoicePdf/Order/PageFooter.html``. The template gets
the following variables:

*  ``orderItem``: the order
*  ``orderSettings``: the TypoScript settings of ``body.order``
*  ``products``: the products on this and the previous pages
*  ``subtotal.net`` and ``subtotal.gross``: the sum of these products

The feature is enabled by default for the order and the invoice. The
delivery note has no prices, so it is disabled there and the template is
empty.

.. code-block:: typoscript

   plugin.tx_cartpdf.invoicePdf.body.order.pageSubtotal = 1
   module.tx_cartpdf.invoicePdf.body.order.pageSubtotal = 1

Impact
======

The product table is split into one table per page. The number of products
per page is determined by writing the table on trial, so products of
different height (e.g. with long titles or additional lines) are taken into
account.

Own templates for ``Order/Header.html``, ``Order/Product.html`` and
``Order/Footer.html`` keep working. A ``rowspan`` must not span several
products, as the products may be spread over two tables.

Set ``body.order.pageSubtotal = 0`` to render the product table as one table
as before.

.. index:: Frontend, Backend
