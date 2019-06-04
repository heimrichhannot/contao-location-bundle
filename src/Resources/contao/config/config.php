<?php

/**
 * Backend modules
 */
$GLOBALS['BE_MOD']['content']['location']         = [
    'tables' => ['tl_location_archive', 'tl_location']
];

$GLOBALS['BE_MOD']['content']['location_archive'] = [
    'tables' => ['tl_location_archive']
];

/**
 * Permissions
 */
$GLOBALS['TL_PERMISSIONS'][] = 'location_bundles';
$GLOBALS['TL_PERMISSIONS'][] = 'location_bundlep';