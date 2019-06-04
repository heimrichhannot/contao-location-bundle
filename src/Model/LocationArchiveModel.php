<?php

namespace HeimrichHannot\LocationBundle\Model;

class LocationArchiveModel extends \Model
{
    protected static $strTable = 'tl_location_archive';

    public static function findMultipleByIds($arrIds, array $arrOptions = [])
    {
        if (!is_array($arrIds) || empty($arrIds)) {
            return null;
        }

        $t = static::$strTable;

        if (!isset($arrOptions['order'])) {
            $arrOptions['order'] = \Database::getInstance()->findInSet("$t.id", $arrIds);
        }

        return static::findBy(["$t.id IN(" . implode(',', array_map('intval', $arrIds)) . ")"], null, $arrOptions);
    }

    public static function getTitle($intId)
    {
        if (($objLocationArchive = static::findByPk($intId)) !== null) {
            return $objLocationArchive->title;
        }
    }
}