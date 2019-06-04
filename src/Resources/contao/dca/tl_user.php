<?php

$dca = &$GLOBALS['TL_DCA']['tl_user'];

/**
 * Palettes
 */
$dca['palettes']['extend'] = str_replace('fop;', 'fop;{location_bundle_legend},location_bundles,location_bundlep;', $dca['palettes']['extend']);
$dca['palettes']['custom'] = str_replace('fop;', 'fop;{location_bundle_legend},location_bundles,location_bundlep;', $dca['palettes']['custom']);

/**
 * Fields
 */
$dca['fields']['location_bundles'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_user']['location_bundles'],
    'exclude'    => true,
    'inputType'  => 'checkbox',
    'foreignKey' => 'tl_location_archive.title',
    'eval'       => ['multiple' => true],
    'sql'        => "blob NULL"
];

$dca['fields']['location_bundlep'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_user']['location_bundlep'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'options'   => ['create', 'delete'],
    'reference' => &$GLOBALS['TL_LANG']['MSC'],
    'eval'      => ['multiple' => true],
    'sql'       => "blob NULL"
];
