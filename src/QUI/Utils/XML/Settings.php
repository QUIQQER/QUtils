<?php

/**
 * This file contains the \QUI\Utils\XML\Settings
 */

namespace QUI\Utils\XML;

use DOMElement;
use DOMNode;
use DOMXPath;
use DusanKasan\Knapsack\Collection;
use QUI;
use QUI\Utils\DOM;
use QUI\Utils\Text\XML;

use function array_merge;
use function file_exists;
use function htmlspecialchars;
use function is_array;
use function is_null;
use function is_string;
use function str_replace;

/**
 * Class Settings
 */
class Settings
{
    /**
     * @var string
     */
    protected string $xmlPath = '//settings/window';

    /**
     * @var null|Settings
     */
    protected static ?Settings $Instance = null;

    /**
     * @return Settings
     */
    public static function getInstance(): Settings
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
    public function setXMLPath(string $xmlPath): void
    {
        $this->xmlPath = $xmlPath;
    }

    /**
     *
     * @param $xmlFiles
     * @param bool|string $windowName
     * @return array
     */
    public function getPanel($xmlFiles, bool | string $windowName = false): array
    {
        $result = [
            'title' => '',
            'icon' => ''
        ];

        if (is_string($xmlFiles)) {
            $xmlFiles = [$xmlFiles];
        }

        foreach ($xmlFiles as $xmlFile) {
            if (defined('CMS_DIR') && !str_contains($xmlFile, CMS_DIR)) {
                $xmlFile = CMS_DIR . $xmlFile;
            }

            if (!file_exists($xmlFile)) {
                continue;
            }

            $Dom = XML::getDomFromXml($xmlFile);
            $Path = new DOMXPath($Dom);
            $windows = $Path->query($this->xmlPath);

            if (!$windows->length) {
                continue;
            }

            foreach ($windows as $Window) {
                if (
                    !method_exists($Window, 'getElementsByTagName')
                    || !method_exists($Window, 'getAttribute')
                ) {
                    continue;
                }

                $Title = $Window->getElementsByTagName('title');
                $Icon = $Window->getElementsByTagName('icon');

                if ($windowName && $windowName !== $Window->getAttribute('name')) {
                    continue;
                }

                if ($Title->length && $Title->item(0)->parentNode === $Window) {
                    $result['title'] = htmlspecialchars(DOM::getTextFromNode($Title->item(0)));
                }

                if ($Icon->length && $Icon->item(0) !== '' && $Icon->item(0)->parentNode === $Window) {
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
        $result['name'] = $windowName;

        return $result;
    }

    /**
     * Parse a list of xml files to collections
     *
     * @param array|string $xmlFiles
     * @return Collection
     */
    public function getCategories(array | string $xmlFiles): Collection
    {
        if (is_string($xmlFiles)) {
            $xmlFiles = [$xmlFiles];
        }

        $Collection = Collection::from([]);

        $findIndex = function ($array, $name) {
            foreach ($array as $key => $item) {
                if ($item['name'] == $name) {
                    return $key;
                }
            }

            return false;
        };

        foreach ($xmlFiles as $xmlFile) {
            if (defined('CMS_DIR') && !str_contains($xmlFile, CMS_DIR)) {
                $xmlFile = CMS_DIR . $xmlFile;
            }

            if (!file_exists($xmlFile)) {
                continue;
            }

            $Dom = XML::getDomFromXml($xmlFile);
            $Path = new DOMXPath($Dom);

            $categories = $Path->query($this->xmlPath . "/categories/category");

            foreach ($categories as $Category) {
                $data = $this->parseCategory($Category);

                if (defined('CMS_DIR')) {
                    $data['file'] = str_replace(CMS_DIR, '', $xmlFile);
                } else {
                    $data['file'] = $xmlFile;
                }

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

                $files = [];

                if (is_string($entry['file'])) {
                    $files[] = $entry['file'];
                } else {
                    $files = $entry['file'];
                }

                $files[] = $data['file'];
                $entry['file'] = $files;

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
     * @param DOMNode|DOMElement $Category
     * @return array
     */
    public function parseCategory(DOMNode | DOMElement $Category): array
    {
        if (!method_exists($Category, 'getAttribute')) {
            return [];
        }

        $Collection = Collection::from([]);

        $data = [
            'name' => $Category->getAttribute('name'),
            'index' => $Category->getAttribute('index'),
            'require' => $Category->getAttribute('require'),
            'click' => $Category->getAttribute('click'),
            'title' => ''
        ];

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
     * @param DOMNode|DOMElement $Setting
     * @return array
     */
    public function parseSettings(DOMNode | DOMElement $Setting): array
    {
        if (!method_exists($Setting, 'getAttribute')) {
            return [];
        }

        $data = [
            'name' => $Setting->getAttribute('name'),
            'title' => '',
            'index' => $Setting->getAttribute('index'),
            'icon' => $Setting->getAttribute('icon'),
            'items' => []
        ];

        $items = [];

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

            $item = null;

            if ($Child->nodeName == 'text') {
                $item = DOM::getTextFromNode($Child);
            }

            if ($Child->nodeName == 'input') {
                $item = DOMParser::inputDomToString($Child);
            }

            if ($Child->nodeName == 'select') {
                $item = DOMParser::selectDomToString($Child);
            }

            if ($Child->nodeName == 'textarea') {
                $item = DOMParser::textareaDomToString($Child);
            }

            if ($Child->nodeName == 'group') {
                $item = DOMParser::groupDomToString($Child);
            }

            if ($Child->nodeName == 'button') {
                $item = DOMParser::buttonDomToString($Child);
            }

            if ($item === null) {
                continue;
            }

            if (method_exists($Child, 'getAttribute') && $Child->getAttribute('row-style')) {
                $items[] = [
                    'rowStyle' => $Child->getAttribute('row-style'),
                    'content' => $item
                ];

                continue;
            }

            $items[] = $item;
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
     * @param array|string $files
     * @param bool|string $categoryName
     * @return string
     */
    public function getCategoriesHtml(array | string $files, bool | string $categoryName = false): string
    {
        $Collection = $this->getCategories($files);
        $result = '';

        $sortByIndex = function ($a, $b) {
            return $a['index'] > $b['index'];
        };

        $collections = $Collection->sort($sortByIndex)->toArray();

        foreach ($collections as $category) {
            if ($categoryName && $categoryName != $category['name']) {
                continue;
            }

            /* @var $Items Collection */
            $Items = $category['items'];
            $settings = $Items->sort($sortByIndex)->toArray();

            foreach ($settings as $setting) {
                $result .= '<table class="data-table data-table-flexbox">';
                $result .= '<thead><tr><th>';

                if (class_exists('QUI') && is_array($setting['title'])) {
                    $result .= QUI::getLocale()->get($setting['title'][0], $setting['title'][1]);
                } else {
                    $result .= $setting['title'];
                }

                $result .= '</th></tr></thead>';
                $result .= '<tbody>';

                foreach ($setting['items'] as $item) {
                    if (is_string($item)) {
                        $result .= '<tr><td>';
                        $result .= $item;
                        $result .= '</td></tr>';
                        continue;
                    }

                    if (empty($item['rowStyle'])) {
                        $result .= '<tr><td>';
                        $result .= $item['content'];
                        $result .= '</td></tr>';
                        continue;
                    }

                    $result .= '<tr style="' . $item['rowStyle'] . '"><td>';
                    $result .= $item['content'];
                    $result .= '</td></tr>';
                }

                $result .= '</tbody></table>';
            }
        }

        return $result;
    }
}
