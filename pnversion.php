<?php
// $Id$

$dom = ZLanguage::getModuleDomain('mediashare');

$modversion['name']           = 'mediashare';
$modversion['version']        = '4.1';
$modversion['displayname']    = __('Mediashare', $dom);
$modversion['description']    = __('Media sharing and gallery module', $dom);
//! module url should be lowercase without spaces and different to displayname
$modversion['url']            = __('mediashare', $dom);

$modversion['credits']        = 'docs/credits.txt';
$modversion['changelog']      = 'docs/changelog.txt';
$modversion['license']        = 'docs/copying.txt';
$modversion['official']       = 0;
$modversion['author']         = 'Jorn Wildt';
$modversion['contact']        = 'jw@fjeldgruppen.dk';
$modversion['admin']          = 1;
$modversion['user']           = 1;

$modversion['securityschema'] = array('mediashare::' => '::');
