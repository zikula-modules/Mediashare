<?php
// ------------------------------------------------------------------------------------
// Translation for PostNuke Mediashare module
// Translation by: Jorn Wildt
// ------------------------------------------------------------------------------------

require_once('modules/mediashare/pnlang/dan/common.php');

define('_MSALLOWTEMPLATEOVERRIDE', 'Tillad skabelonvalg pr. album?');
define('_MSAPPLYGLOBALTEMPLATE', 'Sæt alle');
define('_MSAPPLYGLOBALTEMPLATECONFIRM', 'Overskriv alle albumskabeloner');
define('_MSDEFAULTALBUMTEMPLATE', 'Standard albumvisning');
define('_MSDEFAULTSLIDESHOWTEMPLATE', 'Standard diasshowvisning');
define('_MSDIRNOTWRITABLE', 'Kan ikke skrive til denne mappe.');
define('_MSGENERAL', 'Generelt');
define('_MSGENERALSETUP', 'Instillinger');
define('_MSIMPORT', 'Import');
define('_MSMEDIADIR', 'Mediefil upload mappe');
define('_MSMEDIADIRHELP', "Dette er mappen hvor dine mediefiler bliver gemt. Sørg for at navnet peger på en mappe ved navn 'mediashare' i PostNukes top-mappe, og sørg for at webserveren kan skrive til den.");
define('_MSMEDIAHANDLERS', 'Mediahandlers');
define('_MSMEDIAHANDLERSINFO', 'Listen herunder viser de mediahandlers der er til rådighed. Disse plugins er ansvarlige for at lave frimærkebilleder og vise de forskellige mediefiler du uploader.');
define('_MSMEDIASOURCES', 'Mediakilder');
define('_MSMEDIASOURCESINFO', 'Listen herunder viser de mediakilder der er til rådighed. Disse plugins er ansvarlige for de forskellige måder du kan uploade nye mediefiler.');
define('_MSMODULEDIR', 'Aktuel modulmappe.');
define('_MSOPENBASEDIR', 'Open-base mappe (PHP begrænsning)');
define('_MSPLUGINS', 'Plugins');
define('_MSPREVIEWSIZE', 'Previewstørrelse (pixels)');
define('_MSSCANFORPLUGINS', 'Skan efter plugins');
define('_MSSINGLEALLOWEDSIZE', 'Max. størrelse af et enkelt billede (kb)');
define('_MSTOTALALLOWEDSIZE', 'Max. tilladte lagerforbrug for en enkelt bruger (kb)');
define('_MSTHUMBNAILSIZE', 'Frimærkestørrelse (pixels)');
define('_MSTMPDIR', 'Arbejdsmappe');
define('_MSTMPDIRHELP', 'Dette er den mappe som Mediashare bruger til at gemme multimediefiler i når der arbejdes med dem. Sørg for at webserveren har skriveadgang til mappen.');
define('_MSVFSDBSELECTION', 'Filer i databasen');
define('_MSVFSDBSELECTIONHELP', 'Ved at gemme filer i databasen opnår du en bedre sikkerhed og gør det muligt at anvende flere webservere til den samme installation - på bekostning af performance.');
define('_MSVFSDIRECTSELECTION', 'Filer på harddisken');
define('_MSVFSDIRECTSELECTIONHELP', 'Ved at gemme filer på harddiske forbedres performance på beskostning af lidt sikkerhed.');
define('_MSSHARPEN', 'Aktiver forbedring af frimærker');
define('_MSSHARPENHELP', 'Forbedring af frimærker giver skarpere frimærkebilleder på bekostning af øget CPU-forbrug.');
define('_MSTHUMBNAILSTART', 'Vis frimærker');
define('_MSTHUMBNAILSTARTHELP', 'Standard albumvisning kan enten være frimærkevisning eller enkelt-billede-visning');

define('_MSREC_PAGETITLE', 'Genberegn frimærker og previews');
define('_MSREC_INTRO', 'Genberegning af alle frimærker og previews kan tage lang tid. Denne funktion anvender JavaScript til at genberegne et billede af gangen for at undgå PHP\'s begrænsninger på eksekveringstiden. Iframen til venstre bruges til kommunikation med serveren. Du kan følge fremgangen i både iframen og checkboks-listen forneden.');
define('_MSREC_RECALCULATE', 'Genberegn');
?>