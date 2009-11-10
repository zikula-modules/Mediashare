<?php
// $Id: pnversion.php,v 1.19 2008/06/18 19:38:12 jornlind Exp $

$dom = ZLanguage::getModuleDomain('Mediashare');

$modversion['name'] = 'mediashare';
$modversion['version'] = '4.0.1';
$modversion['displayname'] = __('Mediashare', $dom);
//! module url should be lowercase without spaces and different to displayname
$modversion['url'] = __('mediashare', $dom);
$modversion['description'] = __('Media sharing and gallery', $dom);
$modversion['credits'] = 'docs/credits.txt';
$modversion['changelog'] = 'docs/changelog.txt';
$modversion['license'] = 'docs/copying.txt';
$modversion['official'] = 0;
$modversion['author'] = 'Jorn Wildt';
$modversion['contact'] = 'jw@fjeldgruppen.dk';
$modversion['admin'] = 1;
$modversion['user'] = 1;
$modversion['securityschema'] = array('mediashare::' => '::');
