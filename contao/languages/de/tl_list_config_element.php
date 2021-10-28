<?php

use HeimrichHannot\LocationBundle\ConfigElementType\LocationConfigElementType;

$lang = &$GLOBALS['TL_LANG']['tl_list_config_element'];

$lang['locationFieldSelector'] = ['Ort-Feld auswählen', 'Wählen Sie hier das Feld aus, welches die Referenz auf die Orte enthält.'];

$lang['reference'][LocationConfigElementType::getType()] = 'Orte (Locations-Bundle)';

