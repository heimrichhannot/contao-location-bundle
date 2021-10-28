<?php

/**
 * Backend modules
 */

use HeimrichHannot\LocationBundle\Model\LocationModel;

$GLOBALS['BE_MOD']['content']['location'] = [
    'tables' => ['tl_location']
];

/**
 * Models
 */
$GLOBALS['TL_MODELS']['tl_location']         = LocationModel::class;

/**
 * Permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'location_bundles';
$GLOBALS['TL_PERMISSIONS'][] = 'location_bundlep';