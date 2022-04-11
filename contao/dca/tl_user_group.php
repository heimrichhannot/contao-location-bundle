<?php

use Contao\CoreBundle\DataContainer\PaletteManipulator;

Contao\System::loadLanguageFile('tl_user');

PaletteManipulator::create()
    ->addLegend('locations_legend', 'filemounts_legend')
    ->addField('locations', 'locations_legend', PaletteManipulator::POSITION_APPEND)
    ->addField('locations_default', 'locations_legend', PaletteManipulator::POSITION_APPEND)
    ->applyToPalette('default', 'tl_user_group');

$GLOBALS['TL_DCA']['tl_user_group']['fields']['locations'] = [
    'label'     => &$GLOBALS['TL_LANG']['tl_user']['locations'],
    'exclude'   => true,
    'inputType' => 'checkbox',
    'options'   => ['manage'],
    'reference' => &$GLOBALS['TL_LANG']['tl_user']['locationsRef'],
    'eval'      => ['multiple' => true, 'tl_class' => 'clr'],
    'sql'       => "blob NULL"
];

$GLOBALS['TL_DCA']['tl_user_group']['fields']['locations_default'] = [
    'label'      => &$GLOBALS['TL_LANG']['tl_user']['locations_default'],
    'exclude'    => true,
    'inputType'  => 'treePicker',
    'foreignKey' => 'tl_location.title',
    'eval'       => ['multiple' => true, 'fieldType' => 'checkbox', 'foreignTable' => 'tl_location', 'titleField' => 'title', 'searchField' => 'title', 'managerHref' => 'do=location'],
    'sql'        => "blob NULL"
];
