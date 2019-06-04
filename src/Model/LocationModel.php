<?php

namespace HeimrichHannot\LocationBundle\Model;

class LocationModel extends \Model
{
    protected static $strTable = 'tl_location';

    public static function findPublishedByParentAndId($varId, $arrPids, array $arrOptions = [])
    {
        if (!is_array($arrPids) || empty($arrPids)) {
            return null;
        }

        $t          = static::$strTable;
        $arrColumns = ["($t.id=?) AND $t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")"];

        if (!BE_USER_LOGGED_IN) {
            $time         = time();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        return static::findBy($arrColumns, $varId, $arrOptions);
    }


    public static function findPublishedByPids($arrPids, array $arrOptions = [])
    {
        if (!is_array($arrPids) || empty($arrPids)) {
            return null;
        }

        $t          = static::$strTable;
        $arrColumns = ["$t.pid IN(" . implode(',', array_map('intval', $arrPids)) . ")"];

        // Never return unpublished elements in the back end, so they don't end up in the RSS feed
        if (!BE_USER_LOGGED_IN || TL_MODE == 'BE') {
            $time         = time();
            $arrColumns[] = "($t.start='' OR $t.start<$time) AND ($t.stop='' OR $t.stop>$time) AND $t.published=1";
        }

        return static::findBy($arrColumns, null, $arrOptions);
    }

    public static function getName($intId)
    {
        if (($objLocation = static::findByPk($intId)) !== null) {
            return $objLocation->title;
        }
    }

    public static function getLocationsAsOptions($arrSkipLocations = [], $blnIncludeDescriptions = false)
    {
        $arrResult = [];

        if (($objLocations = static::findAll(['order' => 'title ASC'])) !== null) {
            while ($objLocations->next()) {
                if (empty($arrSkipLocations) || !in_array($objLocations->id, $arrSkipLocations)) {
                    $strCountry = ($objLocations->country && $objLocations->country != 'de' ? $GLOBALS['TL_LANG']['CNT'][$objLocations->country] : '');

                    $arrResult[$objLocations->id] = ($blnIncludeDescriptions && $objLocations->description ?
                        $objLocations->title . ' (' . ($strCountry ? $strCountry . ' – ' : '') . $objLocations->description . ')' :
                        $objLocations->title . ($strCountry ? ' (' . $strCountry . ')' : ''));
                }
            }
        }

        return $arrResult;
    }
}