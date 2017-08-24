.. ==================================================
.. FOR YOUR INFORMATION
.. --------------------------------------------------
.. -*- coding: utf-8 -*- with BOM.

Konfiguration
=============

.. toctree::
   :maxdepth: 5
   :titlesonly:


Vorbereitung: Include static TypoScript
---------------------------------------

Die Erweiterung wird mit TypoScript-Konfigurationsdateien ausgeliefert, die in das Template eingebunden werden
müssen.

Vorbereitung: Ordner für PDF-Dateien
------------------------------------

Weiterhin müssen die Ordner angelegt werden, in denen die PDF-Dateien gespeichert werden sollen. Die Standard
TypoScript Konfiguration sieht dabei Ordner *tx_cart/order_pdf*, *tx_cart/invoice_pdf* und *tx_cart/delivery_pdf*
im fileadmin-Verzeichnis vor. Soll ein anderer Ordner verwendet werden stehen dazu für jeden PDF-Datentyp die Werte
storageRepository und storageFolder bereit. Genauere Informationen dazu finden sich dann im Bereich Administratoren
Handbuch.

.. WARNING::
   Diese Ordner sollten durch geeignete Maßnahmen vor dem direkten Zugriff aus dem Internet geschützt werden.

.. NOTE::
   Es ist zu beachten dass die Konfiguration sowohl für plugin.tx_cart als auch module.tx_cart vorgenommen werden muss,
   denn das Backend-Modul nutzt an dieser Stelle die Backend Modul-Konfigurationen und nicht die der Frontend
   Plugin-Konfigurationen.
   Die Veränderung der Konstanten passt beide Konfigurationen an.
