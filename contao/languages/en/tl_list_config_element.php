<?php

use HeimrichHannot\LocationBundle\ConfigElementType\LocationConfigElementType;

$lang = &$GLOBALS['TL_LANG']['tl_list_config_element'];

$lang['locationFieldSelector'] = ['Select location field', 'Select the field that contains the location information.'];

$lang['reference'][LocationConfigElementType::getType()] = 'Locations (Locations-Bundle)';
