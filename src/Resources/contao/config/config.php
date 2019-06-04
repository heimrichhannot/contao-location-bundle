<?php

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['location'] = [
    'tables' => ['tl_location_archive', 'tl_location']
];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_location']         = 'HeimrichHannot\LocationBundle\Model\LocationModel';
$GLOBALS['TL_MODELS']['tl_location_archive'] = 'HeimrichHannot\LocationBundle\Model\LocationArchiveModel';

/**
 * Permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'location_bundles';
$GLOBALS['TL_PERMISSIONS'][] = 'location_bundlep';