<?php


namespace HeimrichHannot\LocationBundle\DataContainer;

use Symfony\Bundle\SecurityBundle\Security;
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
use Contao\StringUtil;
use Contao\System;
use Contao\Validator;
use Contao\Versions;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LocationContainer
{

    public function __construct(private readonly Security $security,private readonly RequestStack $requestStack)
    {
    }

    public function listChildren(array $arrRow): string
    {
        return '<div class="tl_content_left">'.($arrRow['title'] ?: $arrRow['id']).' <span style="color:#b3b3b3; padding-left:3px">['.
            Date::parse(Config::get('datimFormat'), trim((string) $arrRow['dateAdded'])).']</span></div>';
    }

    /**
     * @param string $varValue
     *
     * @throws \Exception
     *
     * @return string
     */
    public static function generateAlias($varValue, DataContainer $dc): string
    {
        if (null === ($location = System::getContainer()->get('huh.utils.model')->findModelInstanceByPk('tl_location', $dc->id))) {
            return '';
        }

        $title = $dc->activeRecord->title ?: $location->title;

        return System::getContainer()->get('huh.utils.dca')->generateAlias($varValue, $dc->id, 'tl_location', $title);
    }


    /**
     * Add a breadcrumb menu to the page tree.
     *
     * @throws AccessDeniedException
     * @throws \RuntimeException
     */
    public function addBreadcrumb(): void
    {
        $strKey = 'tl_location_node';

        $request = $this->requestStack->getCurrentRequest();
        $session = $request?->getSession();

        if (null === $session) {
            return;
        }

        /** @var AttributeBagInterface $objSession */
        $objSession = $session->getBag('contao_backend');

        if ($request && $request->query->has('cn')) {
            $cnValue = $request->query->get('cn', true);

            if (Validator::isInsecurePath($cnValue)) {
                throw new \RuntimeException('Insecure path ' . $cnValue);
            }

            $objSession->set($strKey, $cnValue);
            Controller::redirect(preg_replace('/&cn=[^&]*/', '', (string) Environment::get('request')));
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
                    $arrLinks[] = Backend::addPageIcon($objLocation->row(), '', null, '', true).' <a href="'.Backend::addToUrl('cn='.$objLocation->id).'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectNode'] ?? '').'">'.$objLocation->title.'</a>';
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
        $arrLinks[] = Image::getHtml('pagemounts.svg').' <a href="'.Backend::addToUrl('cn=0').'" title="'.StringUtil::specialchars($GLOBALS['TL_LANG']['MSC']['selectAllNodes'] ?? '').'">'.($GLOBALS['TL_LANG']['MSC']['filterAll'] ?? '').'</a>';
        $arrLinks = array_reverse($arrLinks);

        // Insert breadcrumb menu
        $GLOBALS['TL_DCA']['tl_location']['list']['sorting']['breadcrumb'] .= '

<ul id="tl_breadcrumb">
  <li>'.implode(' › </li><li>', $arrLinks).'</li>
</ul>';
    }

    /**
     * Return the paste location button.
     */
    public function pasteLocation(DataContainer $dc, array $row, string $table, int $cr, ?array $arrClipboard = null): string
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
        $imagePasteAfter = Image::getHtml('pasteafter.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1] ?? '', $row['id']));
        $imagePasteInto = Image::getHtml('pasteinto.svg', sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1] ?? '', $row['id']));

        if ($row['id'] > 0) {
            $return = $disablePA ? Image::getHtml('pasteafter_.svg').' ' : '<a href="'.Controller::addToUrl('act='.$arrClipboard['mode'].'&mode=1&rt='.System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue().'&pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&id='.$arrClipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteafter'][1] ?? '', $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteAfter.'</a> ';
        }

        return $return.($disablePI ? Image::getHtml('pasteinto_.svg').' ' : '<a href="'.Controller::addToUrl('act='.$arrClipboard['mode'].'&mode=2&rt='.System::getContainer()->get('contao.csrf.token_manager')->getDefaultTokenValue().'&pid='.$row['id'].(!\is_array($arrClipboard['id']) ? '&id='.$arrClipboard['id'] : '')).'" title="'.StringUtil::specialchars(sprintf($GLOBALS['TL_LANG'][$table]['pasteinto'][1] ?? '', $row['id'])).'" onclick="Backend.getScrollOffset()">'.$imagePasteInto.'</a> ');
    }
}
