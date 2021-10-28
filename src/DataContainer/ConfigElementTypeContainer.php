<?php

namespace HeimrichHannot\LocationBundle\DataContainer;

use Contao\Controller;
use Contao\CoreBundle\ServiceAnnotation\Callback;
use Contao\DC_Table;
use Contao\Input;
use HeimrichHannot\ListBundle\Model\ListConfigElementModel;
use HeimrichHannot\LocationBundle\ConfigElementType\LocationConfigElementType;
use HeimrichHannot\ReaderBundle\Model\ReaderConfigElementModel;
use HeimrichHannot\UtilsBundle\Model\ModelUtil;
use HeimrichHannot\UtilsBundle\Util\Utils;

class ConfigElementTypeContainer
{
    /**
     * @var Utils
     */
    private $utils;
    /**
     * @var ModelUtil
     */
    private $modelUtil;

    public function __construct(Utils $utils, ModelUtil $modelUtil)
    {
        $this->utils = $utils;
        $this->modelUtil = $modelUtil;
    }


    /**
     * @param DC_Table $dc
     *
     * @Callback(table="tl_list_config_element", target="config.onload")
     * @Callback(table="tl_reader_config_element", target="config.onload")
     */
    public function onLoadCallback($dc): void
    {
        if (!$dc || !$this->utils->container()->isBackend() || 'edit' !== Input::get('act')) {
            return;
        }
        /** @var ListConfigElementModel|ReaderConfigElementModel $element */
        $element = $this->modelUtil->findModelInstanceByPk($dc->table, $dc->id);
        if (!$element || LocationConfigElementType::getType() !== $element->type) {
            return;
        }

        Controller::loadLanguageFile('tl_list_config_element');
        $GLOBALS['TL_DCA'][$dc->table]['fields']['imageSelectorField']['label'] = &$GLOBALS['TL_LANG']['tl_list_config_element']['locationFieldSelector'];
    }
}