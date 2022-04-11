<?php

/*
 * Copyright (c) 2022 Heimrich & Hannot GmbH
 *
 * @license LGPL-3.0-or-later
 */

namespace HeimrichHannot\LocationBundle\DataContainer;

use Contao\Backend;
use Contao\Config;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\DataContainer;
use Contao\Date;
use Contao\Environment;
use Contao\Image;
use Contao\Input;
use Contao\RequestToken;
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Contao\Versions;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Security\Core\Security;

class LocationContainer
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var \HeimrichHannot\RequestBundle\Component\HttpFoundation\Request
     */
    protected $request;
    private Security $security;

    public function __construct(ContainerInterface $container, Security $security)
    {
        $this->container = $container;
        $this->request = $this->container->get('huh.request');

        $this->security = $security;
    }

    public function listChildren($arrRow)
    {
        return '<div class="tl_content_left">'.($arrRow['title'] ?: $arrRow['id']).' <span style="color:#b3b3b3; padding-left:3px">['.
            Date::parse(Config::get('datimFormat'), trim($arrRow['dateAdded'])).']</span></div>';
    }

    /**
     * @param string $varValue
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function generateAlias($varValue, DataContainer $dc)
    {
        if (null === ($location = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_location', $dc->id))) {
            return '';
        }

        $title = $dc->activeRecord->title ?: $location->title;

        return System::getContainer()->get('huh.utils.dca')->generateAlias($varValue, $dc->id, 'tl_location', $title);
    }

    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        if (\strlen($this->request->getGet('tid'))) {
            $this->toggleVisibility($this->request->getGet('tid'), ('1' === $this->request->getGet('state')), (@func_get_arg(12) ?: null));
            Controller::redirect(System::getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$this->security->getUser()->hasAccess('tl_location::published', 'alexf')) {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published']) {
            $icon = 'invisible.svg';
        }

        return '<a href="'.Controller::addToUrl($href).'&rt='.RequestToken::get().'" title="'.StringUtil::specialchars($title).'"'.$attributes.'>'.Image::getHtml($icon, $label, 'data-state="'.($row['published'] ? 1 : 0).'"').'</a> ';
    }

    public function toggleVisibility($intId, $blnVisible, DataContainer $dc = null)
    {
        $database = Database::getInstance();

        // Set the ID and action
        Input::setGet('id', $intId);
        Input::setGet('act', 'toggle');

        if ($dc) {
            $dc->id = $intId; // see #8043
        }

        // Trigger the onload_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_location']['config']['onload_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_location']['config']['onload_callback'] as $callback) {
                if (\is_array($callback)) {
                    System::importStatic($callback[0])->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$this->security->getUser()->hasAccess('tl_location::published', 'alexf')) {
            throw new AccessDeniedException('Not enough permissions to publish/unpublish location item ID '.$intId.'.');
        }

        // Set the current record
        if ($dc) {
            $objRow = $database->prepare('SELECT * FROM tl_location WHERE id=?')
                ->limit(1)
                ->execute($intId);

            if ($objRow->numRows) {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new Versions('tl_location', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_location']['fields']['published']['save_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_location']['fields']['published']['save_callback'] as $callback) {
                if (\is_array($callback)) {
                    $blnVisible = System::importStatic($callback[0])->{$callback[1]}($blnVisible, $dc);
                } elseif (\is_callable($callback)) {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $database->prepare("UPDATE tl_location SET tstamp=$time, published='".($blnVisible ? '1' : "''")."' WHERE id=?")
            ->execute($intId);

        if ($dc) {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (\is_array($GLOBALS['TL_DCA']['tl_location']['config']['onsubmit_callback'])) {
            foreach ($GLOBALS['TL_DCA']['tl_location']['config']['onsubmit_callback'] as $callback) {
                if (\is_array($callback)) {
                    System::importStatic($callback[0])->{$callback[1]}($dc);
                } elseif (\is_callable($callback)) {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();
    }

    /**
     * Add a breadcrumb menu to the page tree.
     *
     * @throws AccessDeniedException
     * @throws \RuntimeException
     */
    public function addBreadcrumb()
    {
        $strKey = 'tl_location_node';

        /** @var AttributeBagInterface $objSession */
        $objSession = System::getContainer()->get('session')->getBag('contao_backend');

        // Set a new node
        if (System::getContainer()->get('huh.request')->hasGet('cn')) {
            // Check the path
            if (Validator::isInsecurePath(System::getContainer()->get('huh.request')->getGet('cn', true))) {
                throw new \RuntimeException('Insecure path '.System::getContainer()->get('huh.request')->getGet('cn', true));
            }

            $objSession->set($strKey, System::getContainer()->get('huh.request')->getGet('cn', true));
            Controller::redirect(preg_replace('/&cn=[^&]*/', '', Environment::get('request')));
        }

        $intNode = $objSession->get($strKey);

        if ($intNode < 1) {
            return;
        }

        // Check the path (thanks to Arnaud Buchoux)
        if (Validator::isInsecurePath($intNode)) {
            throw new \RuntimeException('Insecure path '.$intNode);
        }

        $arrIds = [];
        $arrLinks = [];

        // Generate breadcrumb trail
        if ($intNode) {
            $intId = $intNode;
            $objDatabase = Database::getInstance();

            do {
                $objLocation = $objDatabase->prepare('SELECT * FROM tl_location WHERE id=?')->limit(1)->execute($intId);

                if ($objLocation->numRows < 1) {
                    // Currently selected page does not exist
                    if ($intId == $intNode) {
                        $objSession->set($strKey, 0);

                        return;
                    }

                    break;
                }

                $arrIds[] = $intId;

                // No link for the active page
                if ($objLocation->id == $intNode) {
                    $arrLinks[] = Backend::addPageIcon($objLocation->row(), '', null, '', true).' '.$objLocation->title;
                } else {
                    $arrLinks[] = Backend::addPageIcon($objLocation->row(), '', null, '', true).' <a href="'.Backend::addToUrl('cn='.$objLocation->id).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode']).'">'.$objLocation->title.'</a>';
                }

                // FIXME: Implement permission check
//                if (!$objUser->isAdmin && $objUser->hasAccess($objLocation->id, 'locations'))
//                {
//                    break;
//                }

                $intId = $objLocation->pid;
            } while ($intId > 0);
        }

        // FIXME: implement permission check
//        if (!$objUser->hasAccess($arrIds, 'locations'))
//        {
//            $objSession->set($strKey, 0);
//            throw new AccessDeniedException('Locations ID ' . $intNode . ' is not available.');
//        }

        // Limit tree
        $GLOBALS['TL_DCA']['tl_location']['list']['sorting']['root'] = [$intNode];

        // Add root link
        $arrLinks[] = Image::getHtml('pagemounts.svg').' <a href="'.Backend::addToUrl('cn=0').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes']).'">'.$GLOBALS['TL_LANG']['MSC']['filterAll'].'</a>';
        $arrLinks = array_reverse($arrLinks);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_location']['list']['sorting']['breadcrumb'] .= '

<ul id="tl_breadcrumb">
  <li>'.implode(' › </li><li>', $arrLinks).'</li>
</ul>';
    }

    /**
     * Return the paste location button.
     *
     * @param \DataContainer
     * @param array
     * @param string
     * @param bool
     * @param array
     *
     * @return string
     */
    public function pasteLocation(DataContainer $dc, $row, $table, $cr, $arrClipboard = null)
    {
        $disablePA = false;
        $disablePI = false;

        // Disable all buttons if there is a circular reference
        if (false !== $arrClipboard && ('cut' === $arrClipboard['mode'] && (1 === $cr || $arrClipboard['id'] === $row['id']) || 'cutAll' === $arrClipboard['mode'] && (1 === $cr || \in_array($row['id'], $arrClipboard['id'], true)))) {
            $disablePA = true;
            $disablePI = true;
        }

        $return = '';

        // Return the buttons
        $imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id']));
        $imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id']));

        if ($row['id'] > 0) {
            $return = $disablePA ? Image::getHtml('pasteafter_.svg').' ' : '<a href="'.Controller::addToUrl('act='.$arrClipboard['mode'].'&mode=1&rt='.System::getContainer()->get('security.csrf.token_manager')->getToken(System::getContainer()->getParameter('contao.csrf_token_name'))->getValue().'&pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disablePI ? Image::getHtml('pasteinto_.svg').' ' : '<a href="'.Controller::addToUrl('act='.$arrClipboard['mode'].'&mode=2&rt='.System::getContainer()->get('security.csrf.token_manager')->getToken(System::getContainer()->getParameter('contao.csrf_token_name'))->getValue().'&pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&id='.$arrClipboard['id'] : '')).'" title="'.specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1], $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }
}
