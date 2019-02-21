.. include:: ../../../Includes.txt

Main Configuration
==================

Preparation: Include static TypoScript
--------------------------------------

The extension comes with several TypoScript configuration files. One for general configuration an one for each type of PDF.

Preparation: Folder for PDF files
------------------------------------

Furthermore, the folders must be created in which the PDF files are to be stored. The standard
TypoScript configuration sees folder *tx_cart/order_pdf*, *tx_cart/invoice_pdf* and *tx_cart/delivery_pdf*
in the fileadmin directory. If another folder should be used, the values ​​are available for each PDF data type
storageRepository and storageFolder ready.

.. WARNING::
   These folders should be protected by appropriate measures against direct access from the Internet.

.. NOTE::
   Please note that the configuration has to be done for both plugin.tx_cart and module.tx_cart,
   because the backend module uses the backend module configurations instead of the frontend at this point
   Plugin configurations.
   The change of the constants adapts to both configurations.