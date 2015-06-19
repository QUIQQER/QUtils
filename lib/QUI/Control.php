<?php

/**
 * This file includes \QUI\Control
 */

namespace QUI;

/**
 * QUI Control
 * PHP counterpart to the \QUI\Control JavaScript class
 *
 * @author www.pcsg.de (Henning Leutz)
 */
class Control extends QDOM
{
    protected $_cssClasses = array();

    /**
     * Constructor
     *
     * @param Array $attributes
     */
    public function __construct($attributes = array())
    {
        $this->setAttributes($attributes);
    }

    /**
     * Return the DOM Node string
     *
     * @return String
     */
    public function create()
    {
        $body = $this->getBody();

        $attributes = $this->getAttributes();
        $params = '';

        foreach ($attributes as $key => $value) {
            if (strpos($key, 'data-') === false) {
                continue;
            }

            if (is_object($value)) {
                continue;
            }

            $key = Utils\Security\Orthos::clear($key);
            $params .= ' '.$key.'="'.htmlentities($value).'"';
        }

        // qui class
        $quiClass = '';

        if ($this->getAttribute('qui-class')) {
            $quiClass = 'data-qui="'.$this->getAttribute('qui-class').'" ';
        }

        $cssClasses = array();

        if ($this->getAttribute('class')) {
            $cssClasses[] = $this->getAttribute('class');
        }

        $cssClasses = array_merge(array_keys($this->_cssClasses), $cssClasses);

        if (!empty($cssClasses)) {
            $quiClass .= 'class="'.implode($cssClasses, ' ').'" ';
        }

        $nodeName = 'div';

        if ($this->getAttribute('nodeName')) {
            $nodeName = $this->getAttribute('nodeName');
        }

        return "<{$nodeName} {$quiClass}{$params}>".$body."</{$nodeName}>";
    }

    /**
     * Return the inner body of the element
     * Can be overwritten
     *
     * @return String
     */
    public function getBody()
    {
        return '';
    }

    /**
     * Add a css class
     *
     * @param String $cssClass
     */
    public function addCSSClass($cssClass)
    {
        $this->_cssClasses[$cssClass] = true;
    }

    /**
     * Remove a css class from the CSS list
     *
     * @param String $cssClass
     */
    public function removeCSSClass($cssClass)
    {
        if (isset($this->_cssClasses[$cssClass])) {
            unset($this->_cssClasses[$cssClass]);
        }
    }

    /**
     * Add a css file to the control
     *
     * @param String $file
     */
    public function addCSSFile($file)
    {
        Control\Manager::addCSSFile($file);
    }

    /**
     * Return the Project
     *
     * @return \QUI\Projects\Project
     */
    protected function _getProject()
    {
        if ($this->getAttribute('Project')) {
            return $this->getAttribute('Project');
        }

        $Project = \QUI::getRewrite()->getProject();

        if (!$Project) {
            $Project = \QUI::getProjectManager()->get();
        }

        $this->setAttribute('Project', $Project);

        return $Project;
    }
}
