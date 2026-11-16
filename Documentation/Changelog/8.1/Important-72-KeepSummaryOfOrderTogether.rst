.. include:: ../../Includes.rst.txt

===============================================
Important: #72 - Keep summary of order together
===============================================

See `Pull Request #72 <https://github.com/extcode/cart_pdf/pull/72>`__

Description
===========

Until now, the product table was written as one table and TCPDF split it
where the page ended. The summary of the order could be split over two pages
or stand alone on the last page.

The product table is split into one table per page now, also if the subtotal
and the amount brought forward are disabled (the default). The summary is
never split: if it does not fit below the last product, this product moves to
the next page as well.

Documents whose product table fits on one page do not change.

Affected Installations
======================

Installations with own templates for ``Order/Header.html``,
``Order/Product.html`` or ``Order/Footer.html``:

*  A ``rowspan`` must not span several products, as the products may be
   spread over two tables.
*  The table header (``<thead>``) is rendered at the top of each table, so it
   is repeated on each page as before.

.. index:: Frontend, Backend
