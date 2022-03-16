<?php

/**
 * This file contains the \QUI\Utils\XML\Settings
 */

namespace QUI\Utils\XML;

use DOMAttr;
use DOMElement;
use DOMNode;
use QUI\Utils\DOM;

use function htmlspecialchars;
use function implode;
use function strpos;
use function time;

/**
 * Class DOMParser
 * New DOM parser, replaces \QUI\Utils\DOM
 */
class DOMParser
{
    /**
     * Parse a <input> XML DOMNode
     *
     * @param DOMNode|DOMElement $Input
     *
     * @return string
     */
    public static function inputDomToString(DOMNode $Input): string
    {
        if ($Input->nodeName != 'input') {
            return '';
        }

        $attributes = self::getAttributes($Input);

        $type    = 'text';
        $classes = $attributes['class'];
        $dataQui = '';

        if ($Input->getAttribute('type')) {
            $type = $Input->getAttribute('type');
        }

        if ($Input->getAttribute('data-qui')
            && strpos($attributes['attributes'], 'data-qui') === false) {
            $dataQui = 'data-qui="' . $Input->getAttribute('data-qui') . '"';
        }

        switch ($type) {
            case 'group':
            case 'groups':
            case 'user':
            case 'users':
                $classes[] = $type;
                $type      = 'text';
                break;

            case 'button':
                return self::buttonDomToString($Input);
        }

        if ($type != 'checkbox' || $type != 'radio') {
            $classes[] = 'field-container-field';
        }

        $input = '<input type="' . $type . '"
                           name="' . $attributes['conf'] . '"
                           id="' . $attributes['id'] . '"
                           class="' . implode(' ', $classes) . '"
                           ' . $attributes['attributes'] . '
                           ' . $dataQui . '
                    />';

        if ($type == 'radio') {
            $input = '<div class="field-container-field">' . $input . '</div>';
        }

        if ($type == 'checkbox') {
            $input = '<div class="field-container-field">' . $input . $attributes['desc'] . '</div>';
        }


        return self::createHTML($input, $attributes);
    }

    /**
     * Parse a <textarea> XML DOMNode
     *
     * @param DOMNode|DOMElement $TextArea
     *
     * @return string
     */
    public static function textareaDomToString(DOMNode $TextArea): string
    {
        if ($TextArea->nodeName != 'textarea') {
            return '';
        }

        $attributes = self::getAttributes($TextArea);
        $dataQui    = '';

        if ($TextArea->getAttribute('data-qui')
            && strpos($attributes['attributes'], 'data-qui') === false) {
            $dataQui = 'data-qui="' . $TextArea->getAttribute('data-qui') . '"';
        }

        $textArea = '<textarea
            name="' . $attributes['conf'] . '"
            id="' . $attributes['id'] . '"
            class="field-container-field"
            ' . $attributes['attributes'] . '
            ' . $dataQui . '
        ></textarea>';

        return self::createHTML($textArea, $attributes);
    }

    /**
     * @param DOMNode|DOMElement $Select
     * @return string
     */
    public static function selectDomToString(DOMNode $Select): string
    {
        if ($Select->nodeName != 'select') {
            return '';
        }

        $attributes = self::getAttributes($Select);
        $dataQui    = '';

        if ($Select->getAttribute('data-qui')
            && strpos($attributes['attributes'], 'data-qui') === false) {
            $dataQui = 'data-qui="' . $Select->getAttribute('data-qui') . '"';
        }

        $select = '<select
            name="' . $attributes['conf'] . '"
            id="' . $attributes['id'] . '"
            class="field-container-field"
            ' . $dataQui . '
            ' . $attributes['attributes'] . '
        >';

        // Options
        $options = $Select->getElementsByTagName('option');

        foreach ($options as $Option) {
            /* @var $Option DOMElement */
            $value = $Option->getAttribute('value');
            $html  = DOM::getTextFromNode($Option);

            $select .= '<option value="' . $value . '">' . $html . '</option>';
        }

        $select .= '</select>';

        return self::createHTML($select, $attributes);
    }

    /**
     * @param DOMNode|DOMElement $Group
     * @return string
     */
    public static function groupDomToString(DOMNode $Group): string
    {
        if ($Group->nodeName != 'group') {
            return '';
        }

        $attributes = self::getAttributes($Group);

        $input = '<input type="hidden"
                     data-qui="controls/usersAndGroups/Select"
                     name="' . $attributes['conf'] . '"
                     id="' . $attributes['id'] . '"
                     class="' . implode(' ', $attributes['class']) . '"
                     ' . $attributes['attributes'] . '
                 />';

        return self::createHTML($input, $attributes);
    }

    /**
     * Button Element
     *
     * @param DOMNode|DOMElement $Button
     *
     * @return string
     */
    public static function buttonDomToString(DOMNode $Button): string
    {
        if ($Button->nodeName != 'button'
            && $Button->getAttribute('type') != 'button'
            && $Button->getAttribute('type') != 'submit'
        ) {
            return '';
        }

        $attributes = self::getAttributes($Button);

        $button = '<button
                     data-qui="qui/controls/buttons/Button"
                     name="' . $attributes['conf'] . '"
                     id="' . $attributes['id'] . '"
                     class="' . implode(' ', $attributes['class']) . '"
                     ' . $attributes['attributes'] . '
                 >' . $attributes['text'] . '</button>';

        return self::createHTML($button, $attributes);
    }

    /**
     * Return needle DOMNode Attributes
     *
     * @param DOMNode|DOMElement $Node
     * @return array
     */
    public static function getAttributes(DOMNode $Node): array
    {
        $id   = $Node->getAttribute('conf') . '-' . time();
        $conf = $Node->getAttribute('conf');

        // Attributes
        $label = true;
        $data  = '';

        foreach ($Node->attributes as $Attribute) {
            /* @var $Attribute DOMAttr */
            $name  = htmlspecialchars($Attribute->name);
            $value = htmlspecialchars($Attribute->value);

            if ($name === 'conf') {
                continue;
            }

            if ($name === 'class') {
                continue;
            }

            $data .= " $name=\"$value\"";
        }

        if ($Node->getAttribute('label') === 0 || $Node->getAttribute('label') === 'false') {
            $label = false;
        }

        // classes
        $class = [];

        if ($Node->getAttribute('class')) {
            $class[] = htmlspecialchars($Node->getAttribute('class'));
        }


        // text
        $Text = $Node->getElementsByTagName('text');
        $text = '';

        if ($Text->length) {
            $text = htmlspecialchars(DOM::getTextFromNode($Text->item(0)));
        }


        // description
        $Desc = $Node->getElementsByTagName('description');
        $desc = '';

        if ($Desc->length) {
            $desc = DOM::getTextFromNode($Desc->item(0));
        }

        // label styles
        $labelStyles = '';

        if ($Node->getAttribute('label-style')) {
            $labelStyles = $Node->getAttribute('label-style');
        }


        return [
            'id'          => $id,
            'text'        => $text,
            'conf'        => $conf,
            'desc'        => $desc,
            'attributes'  => $data,
            'class'       => $class,
            'label'       => $label,
            'label-style' => $labelStyles
        ];
    }

    /**
     * @param string $fieldHTML
     * @param array $attributes
     * @return string
     */
    protected static function createHTML(string $fieldHTML, array $attributes): string
    {
        $isCheckbox = strpos($fieldHTML, 'type="checkbox"');
        $labelStyle = '';

        if (!empty($attributes['label-style'])) {
            $labelStyle = ' style="' . $attributes['label-style'] . '"';
        }

        if (!isset($attributes['label']) || $attributes['label'] != false) {
            $string = '<label class="field-container"' . $labelStyle . '>';
            $string .= '<div class="field-container-item" title="' . $attributes['text'] . '">';
            $string .= $attributes['text'];
            $string .= '</div>';
            $string .= $fieldHTML;
            $string .= '</label>';
        } else {
            $string = '<div class="field-container" ' . $labelStyle . '>';
            $string .= $fieldHTML;
            $string .= '</div>';
        }

        if (!empty($attributes['desc']) && !$isCheckbox) {
            $string .= '<div class="field-container-item-desc">' . $attributes['desc'] . '</div>';
        }

        return $string;
    }
}
