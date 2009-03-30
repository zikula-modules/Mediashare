<?php
// ------------------------------------------------------------------------------------
// Translation for Mediashare module
// Translation by: Daniel Neugebauer / Thomas Smiatek / Carsten Volmer
// $Id$
// ------------------------------------------------------------------------------------

require_once('modules/mediashare/pnlang/deu/common.php');

define('_MSALLOWTEMPLATEOVERRIDE',      'Überschreiben der Templates in Alben erlauben?');
define('_MSAPIKEYFLICKR',               'Flickr API-Key');
define('_MSAPIKEYSMUGMUG',              'SmugMug API-Key');
define('_MSAPIKEYPHOTOBUCKET',          'Photobucket API-Key');
define('_MSAPIKEYPICASA',               'Picasa API-Key');
define('_MSAPPLYGLOBALTEMPLATE',        'Global zuweisen');
define('_MSAPPLYGLOBALTEMPLATECONFIRM', 'Alle Album-Templates überschreiben');
define('_MSDEFAULTALBUMTEMPLATE',       'Standard Album-Template');
define('_MSDEFAULTSLIDESHOWTEMPLATE',   'Standard Diashow-Template');
define('_MSDIRNOTWRITABLE',             'Kein Schreibzugriff auf dieses Verzeichnis.');
define('_MSGENERAL',                    'Allgemein');
define('_MSGENERALSETUP',               'Allgemeines Setup');
define('_MSIMPORT',                     'Import');
define('_MSMEDIADIR',                   'Upload-Verzeichnis');
define('_MSMEDIADIRHELP',               "Hier werden die Dateien gespeichert. Bitte darauf achten, dass es sich auf ein Verzeichnis namens 'mediashare' im Zikula-Root bezieht und dass Schreibzugriff für den Webserver besteht.");
define('_MSMEDIAHANDLERS',              'Handler');
define('_MSMEDIAHANDLERSINFO',          'Die folgende Liste zeigt die verfügbaren Handler. Die Plugins erstellen Thumbnails (Vorschaubilder) und stellen die von Ihnen hochgeladenen Dateien dar.');
define('_MSMEDIASOURCES',               'Quellen');
define('_MSMEDIASOURCESINFO',           'Die folgende Liste zeigt die verfügbaren Quellen. Diese Plugins beinhalten verschiedenste Möglichkeiten, Dateien den Alben hinzuzufügen.');
define('_MSMODULEDIR',                  'Aktuelles Modulverzeichnis');
define('_MSOPENBASEDIR',                'Open BaseDir (PHP Restriktion)');
define('_MSPLUGINS',                    'Plugins');
define('_MSPREVIEWSIZE',                'Vorschaugröße (Pixel)');
define('_MSSCANFORPLUGINS',             'Nach Plugins suchen');
define('_MSSINGLEALLOWEDSIZE',          'Max. Größe einer Datei (KByte)');
define('_MSTOTALALLOWEDSIZE',           'Max. Größe aller Dateien eines Users (KByte)');
define('_MSTHUMBNAILSIZE',              'Thumbnailgröße (Pixel)');
define('_MSTMPDIR',                     'Temp-Verzeichnis');
define('_MSTMPDIRHELP',                 "Dieses Verzeichnis benötigt Mediashare während des Uploadvorgangs für temporäre Dateien. Bitte sicherstellen, dass Schreibzugriff für den Webserver besteht.");
define('_MSVFSDBSELECTION',             'Speichern in der Datenbank (nicht empfohlen)');
define('_MSVFSDBSELECTIONHELP',         'Das Speichern der Dateien in der Datenbank erhöht die Sicherheit und ermöglicht die Nutzung mehrerer Webserver auf Kosten der Performance.');
define('_MSVFSDIRECTSELECTION',         'Speichern im lokalen Dateisystem');
define('_MSVFSDIRECTSELECTIONHELP',     'Das Speichern im lokalen Dateisystem erhöht die Performance auf Kosten einiger Sicherheitsaspekte und der Nutzungsmöglichkeit mehrerer Webserver.');
define('_MSSHARPEN',                    'Thumbnail-Schärfung aktivieren');
define('_MSSHARPENHELP',                'Die Schärfung von Thumbnails und Vorschaubildern erhöht die Bildqualität, belastet jedoch die CPU Ressourcen.');
define('_MSTHUMBNAILSTART',             'Thumbnails anzeigen');
define('_MSTHUMBNAILSTARTHELP',         'Die Standardansicht eines Albums kann entweder eine Thumbnail-Übersicht sein, oder eine Folge von Einzelbildansichten');
define('_MSREC_PAGETITLE',              'Thumbnails und Vorschaubilder neu erzeugen');
define('_MSREC_INTRO',                  'Die Neuerzeugung aller Thumnails und Vorschaubilder beansprucht eine gewisse Zeit. Dieses Feature verwendet JavaScript zur Erstellung jeweils einer Datei - ohne dabei eine PHP Laufzeitüberschreitung auszulösen. Der Rahmen rechts wird zur Kommunikation mit dem Server genutzt. Der Fortschritt kann sowohl in diesem Rahmen wie auch in der Liste unten verfolgt werden.');
define('_MSREC_RECALCULATE',            'Neu erzeugen');

?>
