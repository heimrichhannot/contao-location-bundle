<?php

namespace HeimrichHannot\LocationBundle\DataContainer;

use Contao\BackendUser;
use Contao\Controller;
use Contao\CoreBundle\Exception\AccessDeniedException;
use Contao\Database;
use Contao\Image;
use Contao\StringUtil;
use Symfony\Component\DependencyInjection\ContainerInterface;

class LocationArchiveContainer
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var BackendUser
     */
    protected $user;

    /**
     * @var \HeimrichHannot\RequestBundle\Component\HttpFoundation\Request
     */
    protected $request;

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->user = BackendUser::getInstance();
        $this->request = $this->container->get('huh.request');
    }

    public function checkPermission()
    {
        $database = \Contao\Database::getInstance();

        if ($this->user->isAdmin)
        {
            return;
        }

        // Set root IDs
        if (!is_array($this->user->location_bundles) || empty($this->user->location_bundles))
        {
            $root = [0];
        }
        else
        {
            $root = $this->user->location_bundles;
        }

        $GLOBALS['TL_DCA']['tl_location_archive']['list']['sorting']['root'] = $root;

        // Check permissions to add archives
        if (!$this->user->hasAccess('create', 'location_bundlep'))
        {
            $GLOBALS['TL_DCA']['tl_location_archive']['config']['closed'] = true;
        }

        /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $objSession */
        $objSession = \Contao\System::getContainer()->get('session');

        // Check current action
        switch (\Contao\Input::get('act'))
        {
            case 'create':
            case 'select':
                // Allow
                break;

            case 'edit':
                // Dynamically add the record to the user profile
                if (!in_array(\Contao\Input::get('id'), $root))
                {
                    /** @var \Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface $sessionBag */
                    $sessionBag = $objSession->getBag('contao_backend');

                    $arrNew = $sessionBag->get('new_records');

                    if (is_array($arrNew['tl_location_archive']) && in_array(\Contao\Input::get('id'), $arrNew['tl_location_archive']))
                    {
                        // Add the permissions on group level
                        if ($this->user->inherit != 'custom')
                        {
                            $objGroup = $database->execute("SELECT id, location_bundles, location_bundlep FROM tl_user_group WHERE id IN(" . implode(',', array_map('intval', $this->user->groups)) . ")");

                            while ($objGroup->next())
                            {
                                $arrModulep = \StringUtil::deserialize($objGroup->location_bundlep);

                                if (is_array($arrModulep) && in_array('create', $arrModulep))
                                {
                                    $arrModules = \StringUtil::deserialize($objGroup->location_bundles, true);
                                    $arrModules[] = \Contao\Input::get('id');

                                    $database->prepare("UPDATE tl_user_group SET location_bundles=? WHERE id=?")->execute(serialize($arrModules), $objGroup->id);
                                }
                            }
                        }

                        // Add the permissions on user level
                        if ($this->user->inherit != 'group')
                        {
                            $this->user = $database->prepare("SELECT location_bundles, location_bundlep FROM tl_user WHERE id=?")
                                ->limit(1)
                                ->execute($this->user->id);

                            $arrModulep = \StringUtil::deserialize($this->user->location_bundlep);

                            if (is_array($arrModulep) && in_array('create', $arrModulep))
                            {
                                $arrModules = \StringUtil::deserialize($this->user->location_bundles, true);
                                $arrModules[] = \Contao\Input::get('id');

                                $database->prepare("UPDATE tl_user SET location_bundles=? WHERE id=?")
                                    ->execute(serialize($arrModules), $this->user->id);
                            }
                        }

                        // Add the new element to the user object
                        $root[] = \Contao\Input::get('id');
                        $this->user->location_bundles = $root;
                    }
                }
            // No break;

            case 'copy':
            case 'delete':
            case 'show':
                if (!in_array(\Contao\Input::get('id'), $root) || (\Contao\Input::get('act') == 'delete' && !$this->user->hasAccess('delete', 'location_bundlep')))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . \Contao\Input::get('act') . ' location_archive ID ' . \Contao\Input::get('id') . '.');
                }
                break;

            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
                $session = $objSession->all();
                if (\Contao\Input::get('act') == 'deleteAll' && !$this->user->hasAccess('delete', 'location_bundlep'))
                {
                    $session['CURRENT']['IDS'] = [];
                }
                else
                {
                    $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $root);
                }
                $objSession->replace($session);
                break;

            default:
                if (strlen(\Contao\Input::get('act')))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . \Contao\Input::get('act') . ' location_archives.');
                }
                break;
        }
    }

    public function editHeader($row, $href, $label, $title, $icon, $attributes)
    {
        return $this->user->canEditFieldsOf('tl_location_archive') ? '<a href="'.Controller::addToUrl($href.'&amp;id='.$row['id']) . '&rt=' . \RequestToken::get() . '" title="'.\StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ' : \Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
    }

    public function copyArchive($row, $href, $label, $title, $icon, $attributes)
    {
        return $this->user->hasAccess('create', 'location_bundlep') ? '<a href="'.Controller::addToUrl($href.'&amp;id='.$row['id']). '&rt=' . \RequestToken::get() . '" title="'.\StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ' : \Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
    }

    public function deleteArchive($row, $href, $label, $title, $icon, $attributes)
    {
        return $this->user->hasAccess('delete', 'location_bundlep') ? '<a href="'.Controller::addToUrl($href.'&amp;id='.$row['id']). '&rt=' . \RequestToken::get() . '" title="'.\StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label).'</a> ' : \Image::getHtml(preg_replace('/\.svg$/i', '_.svg', $icon)).' ';
    }
}