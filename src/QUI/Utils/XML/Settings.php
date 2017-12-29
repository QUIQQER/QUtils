<?php

/**
 * This file contains the \QUI\Utils\XML\Settings
 */

namespace QUI\Utils\XML;

use QUI;
use QUI\Utils\DOM;
use QUI\Utils\Text\XML;

use DusanKasan\Knapsack\Collection;

/**
 * Class Settings
 * @package QUI\Utils\XML
 */
class Settings
{
    /**
     * @var string
     */
    protected $xmlPath = '//settings/window';

    /**
     * @var null
     */
    protected static $Instance = null;

    /**
     * @return Settings
     */
    public static function getInstance()
    {
        if (is_null(self::$Instance)) {
            self::$Instance = new self();
        }

        return self::$Instance;
    }

    /**
     * Set the xml start / search path for the settings/window
     *
     * The default path is //settings/window ... it worked for
     *
     * <settings>
     *     <window>
     *
     *     </window>
     * </settings>
     *
     * or
     *
     * <quiqqer>
     *     <settings>
     *         <window>
     *
     *         </window>
     *     </settings>
     * </quiqqer>
     *
     * @param string $xmlPath
     */
    public function setXMLPath($xmlPath)
    {
        if (is_string($xmlPath)) {
            $this->xmlPath = $xmlPath;
        }
    }

    /**
     *
     * @param $xmlFiles
     * @return array
     */
    public function getPanel($xmlFiles)
    {
        $result = array(
            'title' => '',
            'icon'  => ''
        );

        if (is_string($xmlFiles)) {
            $xmlFiles = array($xmlFiles);
        }

        foreach ($xmlFiles as $xmlFile) {
            if (!file_exists($xmlFile)) {
                $xmlFile = CMS_DIR.$xmlFile;
            }

            if (!file_exists($xmlFile)) {
                continue;
            }

            $Dom     = XML::getDomFromXml($xmlFile);
            $Path    = new \DOMXPath($Dom);
            $windows = $Path->query($this->xmlPath);

            foreach ($windows as $Window) {
                /* @var $Window \DOMElement */
                $Title = $Window->getElementsByTagName('title');
                $Icon  = $Window->getElementsByTagName('icon');

                if ($Title->length) {
                    $result['title'] = htmlspecialchars(DOM::getTextFromNode($Title->item(0)));
                }

                if ($Icon->length) {
                    $result['icon'] = htmlspecialchars(DOM::getTextFromNode($Icon->item(0)));
                }

                // if params exists
                $Params = $Window->getElementsByTagName('params');

                if ($Params->length) {
                    $Icon = $Params->item(0)->getElementsByTagName('icon');

                    if ($Icon) {
                        $result['icon'] = DOM::parseVar($Icon->item(0)->nodeValue);
                    }
                }
            }
        }

        $sortByIndex = function ($a, $b) {
            return $a['index'] > $b['index'];
        };

        $result['categories'] = $this->getCategories($xmlFiles)->sort($sortByIndex);

        return $result;
    }

    /**
     * Parse a list of xml files to collections
     *
     * @param array|string $xmlFiles
     * @return \DusanKasan\Knapsack\Collection
     */
    public function getCategories($xmlFiles)
    {
        if (is_string($xmlFiles)) {
            $xmlFiles = array($xmlFiles);
        }

        $Collection = Collection::from(array());

        $findIndex = function ($array, $name) {
            foreach ($array as $key => $item) {
                if ($item['name'] == $name) {
                    return $key;
                }
            }

            return false;
        };

        foreach ($xmlFiles as $xmlFile) {
            if (!file_exists($xmlFile)) {
                $xmlFile = CMS_DIR.$xmlFile;
            }

            if (!file_exists($xmlFile)) {
                continue;
            }

            $Dom  = XML::getDomFromXml($xmlFile);
            $Path = new \DOMXPath($Dom);

            $categories = $Path->query($this->xmlPath."/categories/category");

            foreach ($categories as $Category) {
                $data = $this->parseCategory($Category);

                $entry = $Collection->find(function ($item) use ($data) {
                    return $data['name'] == $item['name'];
                });

                if (empty($entry)) {
                    $Collection = $Collection->append($data);
                    continue;
                }

                $entry['items'] = new Collection(
                    array_merge(
                        $entry['items']->toArray(),
                        $data['items']->toArray()
                    )
                );

                // find index
                $index = $findIndex($Collection->toArray(), $entry['name']);

                if ($index === false) {
                    $Collection = $Collection->append($entry);
                    continue;
                }

                if (empty($entry['title']) && !empty($data['title'])) {
                    $entry['title'] = $data['title'];
                }

                if (empty($entry['icon']) && !empty($data['icon'])) {
                    $entry['icon'] = $data['icon'];
                }

                $Collection = $Collection->replaceByKeys([$index => $entry]);
            }
        }

        return $Collection;
    }

    /**
     * Parse <category> DOMElement and return it as an array
     *
     * @param \DOMElement $Category
     * @return array
     */
    public function parseCategory(\DOMElement $Category)
    {
        $Collection = Collection::from([]);

        $data = array(
            'name'    => $Category->getAttribute('name'),
            'index'   => $Category->getAttribute('index'),
            'require' => $Category->getAttribute('require'),
            'title'   => '',
            'items'   => $Collection
        );

        foreach ($Category->childNodes as $Child) {
            if ($Child->nodeName == '#text') {
                continue;
            }

            if ($Child->nodeName == 'title' || $Child->nodeName == 'text') {
                $data['title'] = DOM::getTextFromNode($Child, false);
                continue;
            }

            if ($Child->nodeName == 'icon') {
                $data['icon'] = DOM::parseVar($Child->nodeValue);
                continue;
            }

            if ($Child->nodeName == 'image') {
                $data['icon'] = DOM::parseVar($Child->nodeValue);
                continue;
            }

            if ($Child->nodeName == 'settings') {
                $Collection = $Collection->append(
                    $this->parseSettings($Child)
                );
            }
        }

        $data['items'] = $Collection;

        return $data;
    }

    /**
     * Parse a <setting> DOM node and return it as an array
     *
     * @param \DOMElement $Setting
     * @return array
     */
    public function parseSettings(\DOMElement $Setting)
    {
        $data = array(
            'name'  => $Setting->getAttribute('name'),
            'title' => '',
            'index' => $Setting->getAttribute('index'),
            'icon'  => $Setting->getAttribute('icon'),
            'items' => array()
        );

        $items = array();

        foreach ($Setting->childNodes as $Child) {
            if ($Child->nodeName == '#text') {
                continue;
            }

            if ($Child->nodeName == 'title' || $Child->nodeName == 'text') {
                if (empty($data['title'])) {
                    $data['title'] = DOM::getTextFromNode($Child, false);
                    continue;
                }
            }

            if ($Child->nodeName == 'template') {
                $data['template'] = QUI\Utils\DOM::parseVar($Child->nodeValue);
                continue;
            }

            if ($Child->nodeName == 'text') {
                $items[] = DOM::getTextFromNode($Child);
                continue;
            }

            if ($Child->nodeName == 'input') {
                $items[] = DOMParser::inputDomToString($Child);
                continue;
            }

            if ($Child->nodeName == 'select') {
                $items[] = DOMParser::selectDomToString($Child);
                continue;
            }

            if ($Child->nodeName == 'textarea') {
                $items[] = DOMParser::textareaDomToString($Child);
                continue;
            }

            if ($Child->nodeName == 'group') {
                $items[] = DOMParser::groupDomToString($Child);
                continue;
            }

            if ($Child->nodeName == 'button') {
                $items[] = DOMParser::buttonDomToString($Child);
            }
        }

        if ($Setting->hasAttributes()) {
            foreach ($Setting->attributes as $attribute) {
                if (empty($data[$attribute->nodeName])) {
                    $data[$attribute->nodeName] = $attribute->nodeValue;
                }
            }
        }

        if (!empty($data['template'])) {
            unset($data['items']);
        } else {
            $data['items'] = $items;
        }

        return $data;
    }

    /**
     * Return the HTML from a category or from multiple categories
     *
     * @param string|array $files
     * @param bool|string $categoryName
     * @return string
     */
    public function getCategoriesHtml($files, $categoryName = false)
    {
        $Collection = $this->getCategories($files);
        $result     = '';

        $sortByIndex = function ($a, $b) {
            return $a['index'] > $b['index'];
        };

        $collections = $Collection->sort($sortByIndex)->toArray();

        foreach ($collections as $category) {
            if ($categoryName && $categoryName != $category['name']) {
                continue;
            }

            /* @var $Items Collection */
            $Items    = $category['items'];
            $settings = $Items->sort($sortByIndex)->toArray();

            foreach ($settings as $setting) {
                $result .= '<table class="data-table data-table-flexbox">';
                $result .= '<thead><tr><th>';

                if (is_array($setting['title'])) {
                    $result .= QUI::getLocale()->get($setting['title'][0], $setting['title'][1]);
                } else {
                    $result .= $setting['title'];
                }

                $result .= '</th></tr></thead>';
                $result .= '<tbody>';

                foreach ($setting['items'] as $item) {
                    $result .= '<tr><td>';
                    $result .= $item;
                    $result .= '</td></tr>';
                }

                $result .= '</tbody></table>';
            }
        }

        return $result;
    }
}
