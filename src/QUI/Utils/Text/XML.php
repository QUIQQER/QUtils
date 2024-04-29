<?php

/**
 * This file contains the \QUI\Utils\Text\XML
 */

namespace QUI\Utils\Text;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Exception;
use QUI;
use QUI\Utils\DOM;
use QUI\Utils\Security\Orthos;

use function array_keys;
use function call_user_func;
use function class_exists;
use function count;
use function dirname;
use function explode;
use function file_exists;
use function file_put_contents;
use function fnmatch;
use function is_array;
use function is_callable;
use function is_string;
use function json_encode;
use function mb_strpos;
use function md5;
use function method_exists;
use function preg_match;
use function realpath;
use function str_replace;
use function strpos;
use function trim;

use const XML_COMMENT_NODE;
use const XML_TEXT_NODE;

/**
 * QUIQQER XML Util class
 *
 * Provides methods to read and work with QUIQQER XML files
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @licence For copyright and license information, please view the /README.md
 */
class XML
{
    /**
     * Add a menu.xml file to a contextmenu bar item
     *
     * @param QUI\Controls\Contextmenu\Bar $Menu - Menu Object
     * @param string $file - Path to XML File
     * @param null|QUI\Interfaces\Users\User $User - User Object from QUIQQER
     */
    public static function addXMLFileToMenu(
        QUI\Controls\Contextmenu\Bar $Menu,
        string $file,
        ?QUI\Interfaces\Users\User $User = null
    ): void {
        if (!file_exists($file)) {
            return;
        }

        /**
         * @param DOMElement|mixed $Item
         * @return bool
         */
        $hasPermission = function (mixed $Item) use ($User) {
            $permissions = $Item->getAttribute('permission');

            if (empty($permissions)) {
                return true;
            }

            if ($User === null) {
                return true;
            }

            if (
                !class_exists('\QUI\Permissions\Permission') ||
                !class_exists('\QUI')
            ) {
                return true;
            }

            $permissions = explode(',', $permissions);

            foreach ($permissions as $permission) {
                if (!QUI\Permissions\Permission::hasPermission($permission, $User)) {
                    return false;
                }
            }

            return true;
        };

        // read the xml
        $items = self::getMenuItemsXml($file);

        foreach ($items as $Item) {
            /* @var $Item DOMElement */
            if (!$Item->getAttribute('parent')) {
                continue;
            }

            if ($hasPermission($Item) === false) {
                continue;
            }

            $params = [
                'text' => DOM::getTextFromNode($Item),
                'locale' => DOM::getTextFromNode($Item, false),
                'name' => $Item->getAttribute('name'),
                'icon' => DOM::parseVar($Item->getAttribute('icon')),
                'require' => $Item->getAttribute('require'),
                'exec' => $Item->getAttribute('exec'),
                'onClick' => 'QUI.Menu.menuClick'
            ];

            $Parent = $Menu;

            if ($Item->getAttribute('parent') != '/') {
                $parent_path = explode(
                    '/',
                    trim($Item->getAttribute('parent'), '/')
                );

                foreach ($parent_path as $parent) {
                    if ($Parent) {
                        $Parent = $Parent->getElementByName($parent);
                    }
                }
            }

            // check, if item already exist
            if (
                !$Item->getAttribute('name')
                || !$Parent
                || $Parent->getElementByName($Item->getAttribute('name'))
            ) {
                continue;
            }

            if ($Item->getAttribute('parent') == '/') {
                $MenuItem = new QUI\Controls\Contextmenu\BarItem($params);
            } elseif ($Item->getAttribute('type') == 'separator') {
                $MenuItem = new QUI\Controls\Contextmenu\Separator($params);
            } else {
                $MenuItem = new QUI\Controls\Contextmenu\MenuItem($params);
            }

            if ($Item->getAttribute('disabled') == 1) {
                $MenuItem->setDisable();
            }

            $Parent->appendChild($MenuItem);
        }
    }

    /**
     * Read the config parameter of an *.xml file and
     * create a QUI\Config if not exist or read the QUI\Config
     *
     * @param string $file - path to the xml file
     * @param bool $withCustomParams - Should custom parameters be considered?
     *
     * @return QUI\Config|boolean - Config | false
     *
     * @throws QUi\Exception
     */
    public static function getConfigFromXml(string $file, bool $withCustomParams = false): QUI\Config|bool
    {
        $Dom = self::getDomFromXml($file);
        $settings = $Dom->getElementsByTagName('settings');

        if (!$settings->length) {
            return false;
        }

        /* @var $Settings DOMElement */
        $Settings = $settings->item(0);
        $configs = $Settings->getElementsByTagName('config');

        if (!$configs->length) {
            return false;
        }

        /* @var $Conf DOMElement */
        $Conf = $configs->item(0);
        $name = $Conf->getAttribute('name');

        if (empty($name)) {
            // plugin conf???
            $dirname = dirname($file);
            $package = str_replace(
                dirname($dirname, 2) . '/',
                '',
                $dirname
            );

            try {
                QUI::getPackageManager()->getInstalledPackage($package);

                $name = 'plugins/' . $package;
            } catch (QUI\Exception) {
                return false;
            }
        }


        $ini_file = CMS_DIR . 'etc/' . $name . '.ini.php';

        QUI\Utils\System\File::mkdir(dirname($ini_file));

        if (!file_exists($ini_file)) {
            file_put_contents($ini_file, '');
        }

        $Config = new QUI\Config($ini_file);
        $params = self::getConfigParamsFromXml($file, $withCustomParams);

        foreach ($params as $section => $key) {
            if (isset($key['default'])) {
                if ($Config->existValue($section) === false) {
                    $Config->setValue($section, $key['default']);
                }

                continue;
            }

            foreach ($key as $value => $entry) {
                // no special characters allowed
                if (preg_match('/[^0-9_a-zA-Z]/', $value)) {
                    continue;
                }

                if ($Config->existValue($section, $value) === false) {
                    $Config->setValue($section, $value, $entry['default']);
                }
            }
        }

        return $Config;
    }

    /**
     * Reads the config parameter from an *.xml
     *
     * @param string $file - path to xml file
     * @param bool $withCustomParams - Should custom parameters be considered?
     *
     * @return array - DOMElement | false
     */
    public static function getConfigParamsFromXml(string $file, bool $withCustomParams = false): array
    {
        return DOM::getConfigParamsFromDOM(
            self::getDomFromXml($file),
            $withCustomParams
        );
    }

    /**
     * Reads the tools list from an *.xml
     *
     * @param string $file - path to xml file
     *
     * @return array
     */
    public static function getConsoleToolsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $tools = $Path->query("//console/tool");
        $list = [];

        if (!$tools->length) {
            return [];
        }

        for ($i = 0; $i < $tools->length; $i++) {
            /* @var $Tool DOMElement */
            $Tool = $tools->item($i);
            $exec = $Tool->getAttribute('exec');
            $file = $Tool->getAttribute('file');

            if (!empty($file)) {
                $file = DOM::parseVar($file);
                $file = Orthos::clearPath(realpath($file));

                if (file_exists($file)) {
                    include_once $file;
                }
            }

            if (!empty($exec)) {
                $list[] = $exec;
            }
        }

        return $list;
    }

    /**
     * Reads the css file list from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getWysiwygCSSFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $CSSList = $Path->query("//wysiwyg/css");
        $files = [];

        for ($i = 0; $i < $CSSList->length; $i++) {
            $files[] = $CSSList->item($i)->getAttribute('src');
        }

        return $files;
    }

    /**
     * Reads the database entries from an *.xml
     *
     * @param string $file - path to the xml file
     *
     * @return array
     */
    public static function getDataBaseFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $database = $Dom->getElementsByTagName('database');

        if (!$database->length) {
            return [];
        }

        $dbFields = [];
        $Database = $database->item(0);

        /* @var $Database DOMElement */
        $global = $Database->getElementsByTagName('global');
        $project = $Database->getElementsByTagName('projects');

        // global
        if ($global && $global->length) {
            /* @var $Table DOMElement */
            $Table = $global->item(0);
            $tables = $Table->getElementsByTagName('table');

            for ($i = 0; $i < $tables->length; $i++) {
                $dbFields['globals'][] = DOM::dbTableDomToArray(
                    $tables->item($i)
                );
            }

            if ($Table->getAttribute('execute')) {
                $dbFields['execute'][] = $Table->getAttribute('execute');
            }
        }

        // projects lang tables
        if ($project && $project->length) {
            $Table = $project->item(0);
            $tables = $Table->getElementsByTagName('table');

            for ($i = 0; $i < $tables->length; $i++) {
                $dbFields['projects'][] = DOM::dbTableDomToArray(
                    $tables->item($i)
                );
            }
        }


        return $dbFields;
    }

    /**
     * Liefer das XML als DOMDocument zurück
     *
     * @param string $filename
     *
     * @return DOMDocument
     */
    public static function getDomFromXml(string $filename): DOMDocument
    {
        if (!str_contains($filename, '.xml')) {
            return new DOMDocument();
        }

        if (!file_exists($filename)) {
            return new DOMDocument();
        }

        try {
            $Dom = new DOMDocument();
            $Dom->load($filename);
        } catch (Exception $Exception) {
            QUI\System\Log::writeException($Exception);

            return new DOMDocument();
        }

        return $Dom;
    }

    /**
     * Reads the events from an *.xml
     * Return all <event>
     *
     * @param string $file
     *
     * @return array
     */
    public static function getEventsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $events = $Dom->getElementsByTagName('events');

        if (!$events->length) {
            return [];
        }

        /* @var $Event DOMElement */
        $Event = $events->item(0);
        $list = $Event->getElementsByTagName('event');

        $result = [];

        for ($i = 0, $len = $list->length; $i < $len; $i++) {
            $result[] = $list->item($i);
        }

        return $result;
    }

    /**
     * Return the site types events from a site.xm file
     *
     * @param string $file
     *
     * @return array
     */
    public static function getSiteEventsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $types = $Path->query("//site/types/type");
        $result = [];

        $package = str_replace(OPT_DIR, '', dirname($file));

        foreach ($types as $Type) {
            /* @var $Type DOMElement */
            $events = $Type->getElementsByTagName('event');

            foreach ($events as $Event) {
                /* @var $Event DOMElement */
                $result[] = [
                    'on' => $Event->getAttribute('on'),
                    'fire' => $Event->getAttribute('fire'),
                    'type' => $package . ':' . $Type->getAttribute('type')
                ];
            }
        }

        return $result;
    }

    /**
     * Return the layout types from a xml file
     * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Site-Xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getLayoutsFromXml(string $file): array
    {
        $Path = new DOMXPath(self::getDomFromXml($file));
        $layouts = $Path->query("//site/layouts/layout");
        $result = [];

        /* @var $Layout DOMElement */
        foreach ($layouts as $Layout) {
            $result[] = $Layout;
        }

        return $result;
    }

    /**
     * Return a specific layout DOM Node entry by its layout name
     *
     * @param string $file - path to the xml file
     * @param string $layoutName - name of the layout type
     *
     * @return bool|DOMElement
     */
    public static function getLayoutFromXml(string $file, string $layoutName)
    {
        $layouts = self::getLayoutsFromXml($file);

        foreach ($layouts as $Layout) {
            /* @var $Layout DOMElement */
            if ($Layout->getAttribute('type') == $layoutName) {
                return $Layout;
            }
        }

        return false;
    }

    /**
     * Sucht die Übersetzungsgruppe aus einem DOMDocument Objekt
     *
     * @param DOMDocument $Dom
     *
     * @return array array(
     *      array(
     *            'groups'   => 'group.name',
     *            'locales'  => array(),
     *            'datatype' => 'js'
     *      ),
     *      array(
     *            'groups'   => 'group.name',
     *            'locales'  => array(),
     *            'datatype' => ''
     *      ),
     *  );
     */
    public static function getLocaleGroupsFromDom(DOMDocument $Dom): array
    {
        $locales = $Dom->getElementsByTagName('locales');

        if (!$locales->length) {
            return [];
        }

        /* @var $Locales DOMElement */
        $Locales = $locales->item(0);
        $groups = $Locales->getElementsByTagName('groups');

        if (!$groups->length) {
            return [];
        }

        $result = [];

        for ($g = 0, $glen = $groups->length; $g < $glen; $g++) {
            /* @var $Group DOMElement */
            $Group = $groups->item($g);
            $localeList = $Group->getElementsByTagName('locale');

            $locales = [
                'group' => $Group->getAttribute('name'),
                'locales' => [],
                'datatype' => $Group->getAttribute('datatype')
            ];

            for ($c = 0; $c < $localeList->length; $c++) {
                $Locale = $localeList->item($c);

                if ($Locale->nodeName == '#text') {
                    continue;
                }

                $priority = 0;

                if ($Locale->getAttribute('priority')) {
                    $priority = (int)$Locale->getAttribute('priority');
                }

                /* @var $Locale DOMElement */
                $params = [
                    'name' => $Locale->getAttribute('name'),
                    'html' => (bool)$Locale->getAttribute('html'),
                    'priority' => $priority
                ];

                $translations = $Locale->childNodes;

                for ($i = 0; $i < $translations->length; $i++) {
                    $Translation = $translations->item($i);

                    if ($Translation->nodeName == '#text') {
                        continue;
                    }

                    $params[$Translation->nodeName] = DOM::parseVar($Translation->nodeValue);

                    if (empty($params[$Translation->nodeName])) {
                        $params[$Translation->nodeName] = ' ';
                    }
                }

                $locales['locales'][] = $params;
            }

            $result[] = $locales;
        }

        return $result;
    }

    /**
     * Reads the menu items from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getMenuItemsXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $menu = $Dom->getElementsByTagName('menu');

        if (!$menu->length) {
            return [];
        }

        /* @var $Menu DOMElement */
        $Menu = $menu->item(0);
        $items = $Menu->getElementsByTagName('item');

        if (!$items->length) {
            return [];
        }

        $result = [];

        for ($c = 0; $c < $items->length; $c++) {
            $Item = $items->item($c);

            if ($Item->nodeName == '#text') {
                continue;
            }

            $result[] = $Item;
        }

        return $result;
    }

    /**
     * Return the package data from a package.xml
     * parse a package xml to an array
     *
     * @param string $file - path to the xml file
     *
     * @return array
     */
    public static function getPackageFromXMLFile(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $package = $Path->query("//quiqqer/package");

        if (!$package->length) {
            return [];
        }

        $result = [];
        $Package = $package->item(0);
        $childNodes = $Package->childNodes;

        foreach ($childNodes as $Node) {
            /* @var $Node DOMElement */
            if ($Node->nodeName === 'title') {
                $result['title'] = DOM::getTextFromNode($Node);
                continue;
            }

            if ($Node->nodeName === 'description') {
                $result['description'] = DOM::getTextFromNode($Node);
                continue;
            }

            if ($Node->nodeName === 'image') {
                $result['image'] = DOM::parseVar($Node->getAttribute('src'));
                continue;
            }

            if ($Node->nodeName === 'template_parent') {
                $result['template_parent'] = trim($Node->nodeValue);
                continue;
            }
        }

        // preview images
        $previews = $Path->query("//quiqqer/package/preview/image");
        $result['preview'] = [];

        foreach ($previews as $Image) {
            /* @var $Image DOMElement */
            $result['preview'][] = DOM::parseVar($Image->getAttribute('src'));
        }

        // provider
        $provider = $Path->query("//quiqqer/package/provider");
        $result['provider'] = [];

        foreach ($provider as $Provider) {
            /* @var $Provider DOMElement */
            foreach ($Provider->childNodes as $Node) {
                if ($Node->nodeType === XML_COMMENT_NODE) {
                    continue;
                }

                if (
                    $Node->nodeType === XML_TEXT_NODE
                    || $Node->nodeName == '#text'
                    || !$Node->getAttribute('src')
                ) {
                    continue;
                }

                if (!isset($result['provider'][$Node->nodeName])) {
                    $result['provider'][$Node->nodeName] = [];
                }

                $result['provider'][$Node->nodeName][] = $Node->getAttribute('src');
            }
        }

        return $result;
    }

    /**
     * Return the panel nodes from an *.xml file
     *
     * @param string $file - path to the xml file
     *
     * @return array
     */
    public static function getPanelsFromXMLFile(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $panels = $Path->query("//quiqqer/panels/panel");

        if (!$panels->length) {
            return [];
        }

        $result = [];

        for ($i = 0, $len = $panels->length; $i < $len; $i++) {
            $result[] = DOM::parsePanelToArray($panels->item($i));
        }

        return $result;
    }

    /**
     * Read the permissions from an *.xml file
     *
     * @param string $file - path to the xml file
     *
     * @return array
     */
    public static function getPermissionsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $permissions = $Dom->getElementsByTagName('permissions');

        if (!$permissions || !$permissions->length) {
            return [];
        }

        $package = str_replace(
            [OPT_DIR, '/permissions.xml'],
            '',
            $file
        );

        $package = trim($package, '/');

        /* @var $Permissions DOMElement */
        $Permissions = $permissions->item(0);
        $permission = $Permissions->getElementsByTagName('permission');

        if (!$permission || !$permission->length) {
            return [];
        }

        $result = [];

        for ($i = 0; $i < $permission->length; $i++) {
            $data = DOM::parsePermissionToArray($permission->item($i));

            $data['title'] = $package . ' permission.' . $data['name'];
            $data['desc'] = $package . ' permission.' . $data['name'] . '._desc';

            $result[] = $data;
        }

        return $result;
    }

    /**
     * Reads the settings window from an *.xml and search all categories
     *
     * @param string $file - path to xml file
     *
     * @return DOMElement[] - List of DOMElements
     */
    public static function getSettingCategoriesFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);
        $list = [];

        $categories = $Path->query("//settings/window/categories/category");

        if (!$categories->length) {
            return $list;
        }

        /* @var $Category DOMElement */
        foreach ($categories as $Category) {
            $list[] = $Category;
        }

        return $list;
    }

    /**
     * Reads the settings window from an *.xml and search a category
     *
     * @param string $file - path to xml file
     * @param string $name - optional, Category name
     *
     * @return DOMElement|false - List of DOMElements
     */
    public static function getSettingCategoryFromXml(string $file, string $name)
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $categories = $Path->query("//settings/window/categories/category");

        if (!$categories->length) {
            return false;
        }

        foreach ($categories as $Category) {
            /* @var $Category DOMElement */
            if ($Category->getAttribute('name') == $name) {
                return $Category;
            }
        }

        return false;
    }

    /**
     * Return the settings window from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getSettingWindowsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $windows = $Path->query("//quiqqer/settings/window");

        if (!$windows->length) {
            return [];
        }

        $result = [];

        for ($i = 0, $len = $windows->length; $i < $len; $i++) {
            $result[] = $windows->item($i);
        }

        return $result;
    }

    /**
     * Return the project settings window from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getProjectSettingWindowsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $Path = new DOMXPath($Dom);

        $windows = $Path->query("//quiqqer/project/settings/window");

        if (!$windows->length) {
            return [];
        }

        $result = [];

        for ($i = 0, $len = $windows->length; $i < $len; $i++) {
            $result[] = $windows->item($i);
        }

        return $result;
    }

    /**
     * Return the site types from a xml file
     * https://dev.quiqqer.com/quiqqer/quiqqer/wikis/Site-Xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getTypesFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $sites = $Dom->getElementsByTagName('site');

        if (!$sites->length) {
            return [];
        }

        /* @var $Sites DOMElement */
        $Sites = $sites->item(0);
        $types = $Sites->getElementsByTagName('types');

        if (!$types->length) {
            return [];
        }

        /* @var $Types DOMElement */
        $Types = $types->item(0);
        $typeList = $Types->getElementsByTagName('type');

        $result = [];

        for ($c = 0; $c < $typeList->length; $c++) {
            $Type = $typeList->item($c);

            if ($Type->nodeName == '#text') {
                continue;
            }

            $result[] = $Type;
        }

        return $result;
    }

    /**
     * Reads the tabs from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getTabsFromXml(string $file): array
    {
        return self::getTabsFromDom(
            self::getDomFromXml($file)
        );
    }

    /**
     * Return the tabs from a DOMDocument
     *
     * @param DOMDocument $Dom
     *
     * @return array
     */
    public static function getTabsFromDom(DOMDocument $Dom): array
    {
        $window = $Dom->getElementsByTagName('window');

        if (!$window->length) {
            return [];
        }

        return DOM::getTabs($window->item(0));
    }

    /**
     * @param DOMDocument $Dom
     * @return array
     */
    public static function getSiteTabsFromDom(DOMDocument $Dom): array
    {
        $Path = new DOMXPath($Dom);
        $window = $Path->query("//site/window");

        if (!$window->length) {
            return [];
        }

        return DOM::getTabs($window->item(0));
    }

    /**
     * Reads the template_engines from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getTemplateEnginesFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $template = $Dom->getElementsByTagName('template_engines');

        if (!$template->length) {
            return [];
        }

        /* @var $Template DOMElement */
        $Template = $template->item(0);
        $engines = $Template->getElementsByTagName('engine');

        if (!$engines->length) {
            return [];
        }

        $result = [];

        for ($c = 0; $c < $engines->length; $c++) {
            $Engine = $engines->item($c);

            if ($Engine->nodeName == '#text') {
                continue;
            }

            $result[] = $Engine;
        }

        return $result;
    }

    /**
     * Reads the editor from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getWysiwygEditorsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $editors = $Dom->getElementsByTagName('editors');

        if (!$editors->length) {
            return [];
        }

        /* @var $Editors DOMElement */
        $Editors = $editors->item(0);
        $list = $Editors->getElementsByTagName('editor');

        if (!$list->length) {
            return [];
        }

        $result = [];

        for ($c = 0; $c < $list->length; $c++) {
            $Editor = $list->item($c);

            if ($Editor->nodeName == '#text') {
                continue;
            }

            $result[] = $Editor;
        }

        return $result;
    }

    /**
     * Reads the widgets from an *.xml
     *
     * @param string $file
     *
     * @return array
     */
    public static function getWidgetsFromXml(string $file): array
    {
        $Dom = self::getDomFromXml($file);
        $widgets = $Dom->getElementsByTagName('widgets');

        if (!$widgets->length) {
            return [];
        }

        $result = [];

        for ($w = 0; $w < $widgets->length; $w++) {
            $Widgets = $widgets->item($w);

            if ($Widgets->nodeName == '#text') {
                continue;
            }

            /* @var $Widgets DOMElement */
            $list = $Widgets->getElementsByTagName('widget');

            for ($c = 0; $c < $list->length; $c++) {
                $Widget = $list->item($c);

                if ($Widget->nodeName == '#text') {
                    continue;
                }

                /* @var $Widget DOMElement */
                // widget on another location
                if ($Widget->getAttribute('src')) {
                    $file = $Widget->getAttribute('src');
                    $file = DOM::parseVar($file);
                    $Widget = self::getWidgetFromXml($file);

                    if ($Widget) {
                        $result[] = $Widget;
                    }

                    continue;
                }

                $Widget->setAttribute('name', md5($file . $c));

                $result[] = $Widget;
            }
        }

        return $result;
    }

    /**
     * Reads the widget from an *.xml file
     *
     * @param string $file - path to the xml file
     *
     * @return boolean|DOMElement
     */
    public static function getWidgetFromXml(string $file)
    {
        $Dom = self::getDomFromXml($file);
        $widget = $Dom->getElementsByTagName('widget');

        if (!$widget->length) {
            return false;
        }

        /* @var $Widget DOMElement */
        $Widget = $widget->item(0);
        $Widget->setAttribute('name', md5($file));

        return $Widget;
    }

    /**
     * Save the setting to a xml specified config file
     *
     * @param string $file
     * @param array $params
     *
     * @throws QUI\Exception
     */
    public static function setConfigFromXml($file, $params)
    {
        QUI\Permissions\Permission::checkAdminUser();
        QUI\Permissions\Permission::checkPermission('quiqqer.settings');

        // defaults prüfen
        $defaults = self::getConfigParamsFromXml($file);
        $Config = self::getConfigFromXml($file);

        if (!$Config) {
            return;
        }


        $checkFnMatch = function ($key, $keyList) {
            if (!is_array($keyList)) {
                return false;
            }

            foreach ($keyList as $keyEntry) {
                if (fnmatch($keyEntry, $key)) {
                    return $keyEntry;
                }
            }

            return false;
        };

        foreach ($params as $section => $param) {
            if (!is_array($param)) {
                continue;
            }

            foreach ($param as $key => $value) {
                if (!isset($defaults[$section])) {
                    continue;
                }

                // no special characters allowed
                if (preg_match('/[^0-9_a-zA-Z]/', $key)) {
                    continue;
                }

                // default key for fn match
                $defaultkeys = array_keys($defaults[$section]);
                $fnMatchFound = $checkFnMatch($key, $defaultkeys);

                if (!$fnMatchFound && !isset($defaults[$section][$key])) {
                    continue;
                }

                if ($fnMatchFound) {
                    $default = $defaults[$section][$fnMatchFound];
                } else {
                    $default = $defaults[$section][$key];
                }

                if (!isset($value)) {
                    $value = $default['default'];
                }

                // typ prüfen
                switch ($default['type']) {
                    case 'bool':
                    case 'boolean':
                        $value = QUI\Utils\BoolHelper::JSBool($value);

                        if ($value) {
                            $value = 1;
                        } else {
                            $value = 0;
                        }
                        break;

                    case 'int':
                    case 'integer':
                        $value = (int)$value;
                        break;

                    case 'string':
                        if (!is_string($value)) { // #workaround for quiqqer/erp#29
                            $value = json_encode($value);
                        }

                        $value = QUI\Utils\Security\Orthos::cleanHTML($value);
                        break;
                }

                $Config->set($section, $key, $value);
            }
        }

        $Config->save();

        if (strpos($file, 'quiqqer/quiqqer/admin/settings/cache.xml') !== false) {
            // if cache settings are set, this must be in the global conf
            // workaround for quiqqer/quiqqer#726
            $noCache = $Config->get('general', 'nocache');

            self::setConfigFromXml(
                OPT_DIR . 'quiqqer/quiqqer/admin/settings/conf.xml',
                ['globals' => ['cache' => $noCache ? 0 : 1]]
            );
        }

        // @todo muss in paket klasse ausgelagert werden
        // package config?
        if (strpos(URL_DIR . $file, URL_OPT_DIR) !== false) {
            // Determine if file path is absolute (1st case) or relative
            if (mb_strpos($file, OPT_DIR) === 0) {
                $_file = str_replace(OPT_DIR, '', $file);
            } else {
                $_file = str_replace(URL_OPT_DIR, '', URL_DIR . $file);
            }

            $_file = explode('/', $_file);

            try {
                $Package = QUI::getPackage($_file[0] . '/' . $_file[1]);

                QUI::getEvents()->fireEvent('packageConfigSave', [$Package, $params]);
            } catch (QUI\Exception $Exception) {
            }
        }

        // clear cache
        QUI\Cache\Manager::clearCompleteQuiqqerCache();
        QUI\Cache\Manager::clearTemplateCache();
    }

    /**
     * Import a xml array to the database
     * the Array must come from self::getDataBaseFromXml
     *
     * @param array $dbFields - array with db fields
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public static function importDataBase(array $dbFields)
    {
        $Table = QUI::getDataBase()->table();
        $projects = QUI\Projects\Manager::getConfig()->toArray();

        // globale tabellen erweitern / anlegen
        if (isset($dbFields['globals'])) {
            foreach ($dbFields['globals'] as $table) {
                $tbl = QUI::getDBTableName($table['suffix']);

                $Table->addColumn($tbl, $table['fields'], $table['engine']);

                if (isset($table['primary'])) {
                    $Table->setPrimaryKey($tbl, $table['primary']);
                }

                if (isset($table['unique'])) {
                    $Table->setUniqueColumns($tbl, $table['unique']);
                }

                if (!empty($table['comment']) && method_exists($Table, 'setComment')) {
                    $Table->setComment($tbl, $table['comment']);
                }

                $index = [];

                if (isset($table['index']) && !is_array($table['index'])) {
                    $index[] = $table['index'];
                } elseif (isset($table['index']) && is_array($table['index'])) {
                    $index = $table['index'];
                }

                foreach ($index as $ind) {
                    if (strpos($ind, ',') !== false) {
                        $Table->setIndex($tbl, explode(',', $ind));
                    } else {
                        $Table->setIndex($tbl, $ind);
                    }
                }

                if (isset($table['auto_increment'])) {
                    $Table->setAutoIncrement($tbl, $table['auto_increment']);
                }

                if (isset($table['fulltext'])) {
                    $Table->setFulltext($tbl, $table['fulltext']);
                }
            }
        }

        // projekt tabellen erweitern / anlegen
        if (isset($dbFields['projects'])) {
            foreach ($dbFields['projects'] as $table) {
                if (!isset($table['suffix'])) {
                    continue;
                }

                $suffix = $table['suffix'];
                $fields = $table['fields'];
                $engine = $table['engine'];
                $noLang = false;

                if ($table['no-project-lang']) {
                    $noLang = true;
                }

                if ($table['no-site-reference'] !== true && $noLang === false) {
                    $fields = [
                            'id' => 'bigint(20) NOT NULL PRIMARY KEY'
                        ] + $fields;
                }

                // Projekte durchgehen
                foreach ($projects as $name => $params) {
                    if (!isset($params['langs'])) {
                        continue;
                    }

                    $langs = explode(',', $params['langs']);

                    foreach ($langs as $lang) {
                        $tbl = QUI::getDBTableName($name . '_' . $lang . '_' . $suffix);

                        if ($noLang) {
                            $tbl = QUI::getDBTableName($name . '_' . $suffix);
                        }

                        $Table->addColumn($tbl, $fields, $engine);

                        if (isset($table['primary'])) {
                            $Table->setPrimaryKey($tbl, $table['primary']);
                        }

                        if (isset($table['index'])) {
                            $index = [];

                            if (isset($table['index']) && !is_array($table['index'])) {
                                $index[] = $table['index'];
                            } elseif (isset($table['index']) && is_array($table['index'])) {
                                $index = $table['index'];
                            }

                            foreach ($index as $ind) {
                                if (strpos($ind, ',') !== false) {
                                    $Table->setIndex($tbl, explode(',', $ind));
                                } else {
                                    $Table->setIndex($tbl, $ind);
                                }
                            }
                        }

                        if (isset($table['auto_increment'])) {
                            $Table->setAutoIncrement($tbl, $table['auto_increment']);
                        }

                        if (isset($table['fulltext'])) {
                            $Table->setFulltext($tbl, $table['fulltext']);
                        }
                    }
                }
            }
        }

        // php executes
        if (isset($dbFields['execute'])) {
            foreach ($dbFields['execute'] as $exec) {
                $exec = str_replace('\\\\', '\\', $exec);

                if (!is_callable($exec)) {
                    QUI\System\Log::addInfo($exec . ' not callable');
                    continue;
                }

                call_user_func($exec);
            }
        }
    }

    /**
     * Import a database.xml
     *
     * @param string $xmlFile - Path to the file
     *
     * @throws QUI\Exception
     * @throws Exception
     */
    public static function importDataBaseFromXml(string $xmlFile)
    {
        $dbFields = self::getDataBaseFromXml($xmlFile);

        if (!count($dbFields)) {
            return;
        }

        try {
            self::importDataBase($dbFields);
        } catch (QUI\Exception $Exception) {
            QUI\System\Log::addError(
                "Error on XML database import ($xmlFile): "
                . $Exception->getMessage()
            );

            throw $Exception;
        }
    }

    /**
     * Import a permissions.xml
     *
     * @param string $xmlfile - Path to the file
     * @param string $src - [optional] the source for the permissions
     */
    public static function importPermissionsFromXml(string $xmlfile, string $src = '')
    {
        $Manager = QUI::getPermissionManager();
        $Manager->importPermissionsFromXml($xmlfile, $src);
    }
}
