<?php

$GLOBALS['TL_DCA']['tl_user']['palettes']['extend'] = str_replace('newsfeedp;', 'newsfeedp;{locations_legend},locations,locations_default;', $GLOBALS['TL_DCA']['tl_user']['palettes']['extend']);
$GLOBALS['TL_DCA']['tl_user']['palettes']['custom'] = str_replace('newsfeedp;', 'newsfeedp;{locations_legend},locations,locations_default;', $GLOBALS['TL_DCA']['tl_user']['palettes']['custom']);

$GLOBALS['TL_DCA']['tl_user']['fields']['locations'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_user']['locations'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'options'   => ['manage'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['locationsRef'],
    'eval'      => ['multiple' => true, 'tl_class' => 'clr'],
    'sql'       => "varchar(32) NOT NULL default ''"
];

$GLOBALS['TL_DCA']['tl_user']['fields']['locations_default'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_user']['locations_default'],
    'exclude'    => true,
    'inputType'  => 'treePicker',
    'foreignKey' => 'tl_location.title',
    'eval'       => ['multiple' => true, 'fieldType' => 'checkbox', 'foreignTable' => 'tl_location', 'titleField' => 'title', 'searchField' => 'title', 'managerHref' => 'do=location'],
    'sql'        => "blob NULL"
];
