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
            if ( $key == 'qui-class' ) {
                continue;
            }

            if ( is_object( $value ) ) {
                continue;
            }

            $key = Utils\Security\Orthos::clear( $key );

            switch ( $key )
            {
                case 'alt':
                case 'class':
                case 'style':
                case 'title':
                break;

                default:
                    $key = 'data-'. $key;
            }

            $params .= ' '. $key .'="'. htmlentities( $value ) .'"';
        }

        // qui class
        $quiClass = '';

        if ( $this->getAttribute('qui-class') ) {
            $quiClass = 'data-qui="'. $this->getAttribute('qui-class') .'" ';
        }

        return '<div '. $quiClass . $params .'>'.
            $this->getBody() .
        '</div>';
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
}
