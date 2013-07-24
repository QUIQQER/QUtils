<?php

/**
 * This file contains QUI\Utils\Text\Word
 */

namespace QUI\Utils\Text;

mb_internal_encoding( 'UTF-8' );

/**
 * Helper for word handling
 *
 * @author www.pcsg.de (Henning Leutz
 * @package com.pcsg.qutils
 */

class Word
{

    /**
     * Zählt die wichtigen Wörter eines deutschen Textes
     *
     * @param String $text
     * @return Array
     */
    static function countImportantWords($text)
    {
        $str = $text;

        // html raus
        $str = strip_tags( $str );
        $str = explode( ' ', $str );

        $result = array();

        foreach ( $str as $entry )
        {
            if ( self::isUseful( $entry ) == false ) {
                continue;
            }

            // sammeln
            if ( isset( $result[ $entry ] ) )
            {
                $result[$entry]++;
                continue;
            }

            $result[$entry] = 1;
        }

        asort( $result );
        $result = array_reverse( $result );

        return $result;
    }

    /**
     * Is the word usefull?
     * a word filter for german words. checks the string
     * at the moment only for german words
     *
     * @param String $word - German word
     * @return Bool
     */
    static function isUseful($word)
    {
        if ( strlen( $word ) <= 1 ) {
            return false;
        }

        // Kleingeschriebene Wörter raus
        if ( strtolower( $word{0} ) == $word{0} ) {
            return false;
        }

        if ( preg_match('/[^a-zA-Z]/i', $word) ) {
            return false;
        }

        // Wörter mit 2 Grossbuchstaben
        $w0 = utf8_encode(
            strtoupper(
                str_replace(
                    array('ä','ö','ü'),
                    array('Ä','Ö','Ü'),
                    $word{0}
                )
            )
        );

        $w1 = utf8_encode(
            strtoupper(
                str_replace(
                    array('ä','ö','ü'),
                    array('Ä','Ö','Ü'),
                    $word{1}
                )
            )
        );

        // Wörter mit weniger als 3 Buchstaben
        if ( strlen($word) <= 3 ) {
            return false;
        }

        // Wörter welche nicht erlaubt sind
        $not = array(
            'ab', 'aus', 'von', 'an', 'auf', 'außer', 'bei', 'hinter', 'in', 'neben', 'über', 'vor', 'entlang', 'innerhalb',
            'längs', 'an', 'auf', 'bis', 'durch', 'hinter', 'in', 'nach', 'nachdem', 'zu', 'mit', 'der', 'dem', 'den', 'die',
            'das', 'dass', 'daß', 'ein', 'eine', 'einer', 'eines', 'einem', 'einen', 'wegen', 'zufolge', 'in', 'auf', 'unter',
            'über', 'da', 'dort', 'heute', 'darum', 'deshalb', 'warum', 'weshalb', 'weswegen', 'oben', 'ausgerechnet',
            'nur', 'wo', 'wann', 'wie', 'wieso', 'halt', 'übrigens', 'daran', 'dran', 'woran', 'darüber', 'drüber',
            'hierüber', 'worüber', 'gern', 'ich', 'du', 'er', 'sie', 'es', 'wir', 'mir', 'euch', 'sie', 'uns', 'eure', 'deren',
            'etwas', 'jedermann', ' paar', 'was', 'denen', 'alle', 'man', 'wer', 'für', 'um', 'binnen', 'seit', 'während',
            'angesichts', 'anlässlich', 'aufgrund', 'behufs', 'dank', 'gemäß', 'infolge', 'kraft', 'laut',
            'mangels', 'ob', 'seitens', 'seitdem', 'trotz', 'unbeschadet', 'ungeachtet', 'vermöge', 'zwecks', 'zu', 'zur',
            'zum', 'vergebens', 'fast', 'zwar', 'sehr', 'recht', 'überaus', 'folglich', 'ja', 'halt', 'eh', 'wohl', 'erstens',
            'zweitens', 'drittens', 'sogar', 'bereits', 'bedauerlicherweise', 'leider', 'sicher', 'sicherlich',
            'vielleicht', 'viel', 'viele', 'vieles', 'abzüglich', 'exklusive', 'inklusive', 'mit',
            'nebst', 'ohne', ' statt', 'anstatt', 'wider', 'wieder', 'zuwider', 'obwohl', 'wenn', 'falls', 'weil', 'bevor',
            'als', 'indem', 'und', 'weder', 'noch', 'allerdings', 'aber', 'entweder', 'oder', 'heißt', 'nämlich', 'ehe',
            'gleich', 'woher', 'wohin', 'wodurch', 'soviel', 'sowie', 'sooft', 'denn', 'nun', 'sobald', 'sodass', 'so', 'damit',
            'wird', 'werden', 'hat', 'habe', 'haben', 'hatte', 'hatten', 'doch', 'jedoch', 'kann',
            'können', 'konnte', 'konnten', 'soll', 'sollte', 'sollten', 'dazu', 'ohnehin', 'muss',
            'war', 'waren', 'machen', ' dann', 'derzeit', 'beim', 'auch', 'will', 'wollen',
            'schon', 'eher', 'lassen', 'läßt', 'lässt', 'ließ', 'lies', 'dürfen', 'darf', ' gibt',
            'geben', 'gab', 'gaben', 'zuletzt', 'also', 'davon'
        );

        $_word = strtolower($word);

        if ( in_array($_word, $not) ) {
            return false;
        }

        /*  *gegen*  */
        if ( strpos($word, 'gegen') !== false ) {
            return false;
        }

        /* search*  */
        $beginings = array(
            'außer', 'ober', 'unter,	welch', 'kein', 'hier', 'nicht', 'jede', 'manch', 'dies', 'jene', 'jemand', 'halb', 'irgend', 'nirgend', 'indes',
            'solang', 'beide', 'erste', 'zweite', 'dritte', 'vierte', 'fünfte', 'sechste', 'siebte', 'achte', 'neunte', 'zehnte', 'elfte', 'zwölfte',
            'vermittels', 'einig', 'betreff', 'ihr', 'ihn', 'mein', 'sein', 'unser', 'euer', 'dein', 'niemand', 'mittels', 'sonder', 'manch', 'mein',
            'wurde', 'musste', 'wollte', 'durfte', 'machte'
        );

        foreach ( $beginings as $search )
        {
            $pos = strpos( $_word, $search );

            if ( $pos === false ) {
                continue;
            }

            if ( $pos == 0 ) {
                return false;
            }
        }

        /* *search  */
        $endings = array(
            'mal', 'sofern', 'soweit', 'samt', 'einander',
            'schließlich', 'züglich', 'weit', 'zwischen',
            'mitten', 'jenige', 'selbe'
        );

        foreach ( $endings as $search )
        {
            if ( preg_match('/(.*?)'. $search .'$/i', $word) ) {
                return false;
            }
        }

        return true;
    }

}