<?php

namespace HeimrichHannot\LocationBundle\ConfigElementType;

use Contao\StringUtil;
use HeimrichHannot\ConfigElementTypeBundle\ConfigElementType\ConfigElementData;
use HeimrichHannot\ConfigElementTypeBundle\ConfigElementType\ConfigElementResult;
use HeimrichHannot\ConfigElementTypeBundle\ConfigElementType\ConfigElementTypeInterface;
use HeimrichHannot\LocationBundle\Model\LocationModel;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use Terminal42\ServiceAnnotationBundle\Annotation\ServiceTag;

/**
 * @ServiceTag("huh.list.config_element_type")
 * @ServiceTag("huh.reader.config_element_type")
 */
class LocationConfigElementType implements ConfigElementTypeInterface
{
    /**
     * @var ModelUtil
     */
    private $modelUtil;

    public function __construct(ModelUtil $modelUtil)
    {
        $this->modelUtil = $modelUtil;
    }

    public static function getType(): string
    {
        return 'locations';
    }

    public function getPalette(string $prependPalette, string $appendPalette): string
    {
        return $prependPalette.'{config_legend},imageSelectorField;'.$appendPalette;
    }

    public function applyConfiguration(ConfigElementData $configElementData): ConfigElementResult
    {
        $locationIds = array_filter(StringUtil::deserialize($configElementData->getItemData()[$configElementData->getConfiguration()->imageSelectorField], true));

        $locations = [];

        foreach ($locationIds as $locationId) {
            /** @var LocationModel|null $locationModel */
            $locationModel = $this->modelUtil->findModelInstanceByPk(LocationModel::getTable(), $locationId);
            if (!$locationModel) {
                continue;
            }
            $locations[] = $locationModel->row();
        }

        return new ConfigElementResult(ConfigElementResult::TYPE_FORMATTED_VALUE, $locations);
    }
}