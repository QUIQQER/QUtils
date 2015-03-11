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
    /**
     * Constructor
     * @param Array $attributes
     */
    public function __construct( $attributes=array() )
    {
        $this->setAttributes( $attributes );
    }

    /**
     * Return the DOM Node string
     *
     * @return String
     */
    public function create()
    {
        $attributes = $this->getAttributes();
        $params     = '';

        foreach ( $attributes as $key => $value )
        {
            if ( strpos( $key, 'data-' ) === false ) {
                continue;
            }

            if ( is_object( $value ) ) {
                continue;
            }

            $key = Utils\Security\Orthos::clear( $key );
            $params .= ' '. $key .'="'. htmlentities( $value ) .'"';
        }

        // qui class
        $quiClass = '';

        if ( $this->getAttribute('qui-class') ) {
            $quiClass = 'data-qui="'. $this->getAttribute('qui-class') .'" ';
        }

        if ( $this->getAttribute('class') ) {
            $quiClass .= 'class="'. $this->getAttribute('class') .'" ';
        }

        $nodeName = 'div';

        if ( $this->getAttribute( 'nodeName' ) ) {
            $nodeName = $this->getAttribute( 'nodeName' );
        }

        return "<{$nodeName} {$quiClass}{$params}>".
            $this->getBody() .
        "</{$nodeName}>";
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
     * Add a css file to the control
     * @param String $file
     */
    public function addCSSFile($file)
    {
        Control\Manager::addCSSFile( $file );
    }

    /**
     * Return the Project
     * @return \QUI\Projects\Project
     */
    protected function _getProject()
    {
        if ( $this->getAttribute('Project') ) {
            return $this->getAttribute('Project');
        }

        $Project = \QUI::getRewrite()->getProject();

        if ( !$Project ) {
            $Project = \QUI::getProjectManager()->get();
        }

        $this->setAttribute( 'Project', $Project );

        return $Project;
    }
}
