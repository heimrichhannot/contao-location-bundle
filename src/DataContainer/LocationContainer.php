<?php

namespace HeimrichHannot\LocationBundle\DataContainer;

use Contao\Controller;
use Contao\System;
use Symfony\Component\DependencyInjection\ContainerInterface;

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

    public function __construct(ContainerInterface $container)
    {
        $this->container = $container;

        $this->request = $this->container->get('huh.request');
    }

    public function listChildren($arrRow)
    {
        return '<div class="tl_content_left">' . ($arrRow['title'] ?: $arrRow['id']) . ' <span style="color:#b3b3b3; padding-left:3px">[' .
            \Date::parse(\Contao\Config::get('datimFormat'), trim($arrRow['dateAdded'])) . ']</span></div>';
    }

    public function checkPermission()
    {
        $user = \Contao\BackendUser::getInstance();
        $database = \Contao\Database::getInstance();

        if ($user->isAdmin)
        {
            return;
        }

        // Set the root IDs
        if (!is_array($user->location_bundles) || empty($user->location_bundles))
        {
            $root = [0];
        }
        else
        {
            $root = $user->location_bundles;
        }

        $id = strlen($this->request->getGet('id')) ? $this->request->getGet('id') : CURRENT_ID;

        // Check current action
        switch ($this->request->getGet('act'))
        {
            case 'paste':
                // Allow
                break;

            case 'create':
                if (!strlen($this->request->getGet('pid')) || !in_array($this->request->getGet('pid'), $root))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to create location items in location archive ID ' . $this->request->getGet('pid') . '.');
                }
                break;

            case 'cut':
            case 'copy':
                if (!in_array($this->request->getGet('pid'), $root))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . $this->request->getGet('act') . ' location item ID ' . $id . ' to location archive ID ' . $this->request->getGet('pid') . '.');
                }
            // NO BREAK STATEMENT HERE

            case 'edit':
            case 'show':
            case 'delete':
            case 'toggle':
            case 'feature':
                $objArchive = $database->prepare("SELECT pid FROM tl_location WHERE id=?")
                    ->limit(1)
                    ->execute($id);

                if ($objArchive->numRows < 1)
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Invalid location item ID ' . $id . '.');
                }

                if (!in_array($objArchive->pid, $root))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to ' . $this->request->getGet('act') . ' location item ID ' . $id . ' of location archive ID ' . $objArchive->pid . '.');
                }
                break;

            case 'select':
            case 'editAll':
            case 'deleteAll':
            case 'overrideAll':
            case 'cutAll':
            case 'copyAll':
                if (!in_array($id, $root))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access location archive ID ' . $id . '.');
                }

                $objArchive = $database->prepare("SELECT id FROM tl_location WHERE pid=?")
                    ->execute($id);

                if ($objArchive->numRows < 1)
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Invalid location archive ID ' . $id . '.');
                }

                /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
                $session = \System::getContainer()->get('session');

                $session = $session->all();
                $session['CURRENT']['IDS'] = array_intersect($session['CURRENT']['IDS'], $objArchive->fetchEach('id'));
                $session->replace($session);
                break;

            default:
                if (strlen($this->request->getGet('act')))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Invalid command "' . $this->request->getGet('act') . '".');
                }
                elseif (!in_array($id, $root))
                {
                    throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to access location archive ID ' . $id . '.');
                }
                break;
        }
    }

    public function toggleIcon($row, $href, $label, $title, $icon, $attributes)
    {
        $user = \Contao\BackendUser::getInstance();

        if (strlen($this->request->getGet('tid')))
        {
            $this->toggleVisibility($this->request->getGet('tid'), ($this->request->getGet('state') === '1'), (@func_get_arg(12) ?: null));
            Controller::redirect(System::getReferer());
        }

        // Check permissions AFTER checking the tid, so hacking attempts are logged
        if (!$user->hasAccess('tl_location::published', 'alexf'))
        {
            return '';
        }

        $href .= '&amp;tid='.$row['id'].'&amp;state='.($row['published'] ? '' : 1);

        if (!$row['published'])
        {
            $icon = 'invisible.svg';
        }

        return '<a href="'.Controller::addToUrl($href).'&rt='.\RequestToken::get().'" title="'.\StringUtil::specialchars($title).'"'.$attributes.'>'.\Image::getHtml($icon, $label, 'data-state="' . ($row['published'] ? 1 : 0) . '"').'</a> ';
    }

    public function toggleVisibility($intId, $blnVisible, \DataContainer $dc=null)
    {
        $user = \Contao\BackendUser::getInstance();
        $database = \Contao\Database::getInstance();

        // Set the ID and action
        \Contao\Input::setGet('id', $intId);
        \Contao\Input::setGet('act', 'toggle');

        if ($dc)
        {
            $dc->id = $intId; // see #8043
        }

        // Trigger the onload_callback
        if (is_array($GLOBALS['TL_DCA']['tl_location']['config']['onload_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_location']['config']['onload_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    System::importStatic($callback[0])->{$callback[1]}($dc);
                }
                elseif (is_callable($callback))
                {
                    $callback($dc);
                }
            }
        }

        // Check the field access
        if (!$user->hasAccess('tl_location::published', 'alexf'))
        {
            throw new \Contao\CoreBundle\Exception\AccessDeniedException('Not enough permissions to publish/unpublish location item ID ' . $intId . '.');
        }

        // Set the current record
        if ($dc)
        {
            $objRow = $database->prepare("SELECT * FROM tl_location WHERE id=?")
                ->limit(1)
                ->execute($intId);

            if ($objRow->numRows)
            {
                $dc->activeRecord = $objRow;
            }
        }

        $objVersions = new \Versions('tl_location', $intId);
        $objVersions->initialize();

        // Trigger the save_callback
        if (is_array($GLOBALS['TL_DCA']['tl_location']['fields']['published']['save_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_location']['fields']['published']['save_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    $blnVisible = System::importStatic($callback[0])->{$callback[1]}($blnVisible, $dc);
                }
                elseif (is_callable($callback))
                {
                    $blnVisible = $callback($blnVisible, $dc);
                }
            }
        }

        $time = time();

        // Update the database
        $database->prepare("UPDATE tl_location SET tstamp=$time, published='" . ($blnVisible ? '1' : "''") . "' WHERE id=?")
            ->execute($intId);

        if ($dc)
        {
            $dc->activeRecord->tstamp = $time;
            $dc->activeRecord->published = ($blnVisible ? '1' : '');
        }

        // Trigger the onsubmit_callback
        if (is_array($GLOBALS['TL_DCA']['tl_location']['config']['onsubmit_callback']))
        {
            foreach ($GLOBALS['TL_DCA']['tl_location']['config']['onsubmit_callback'] as $callback)
            {
                if (is_array($callback))
                {
                    System::importStatic($callback[0])->{$callback[1]}($dc);
                }
                elseif (is_callable($callback))
                {
                    $callback($dc);
                }
            }
        }

        $objVersions->create();
    }
}