<?php

/**
 * This file contains QUI\Utils\Text\BBCode
 */

namespace QUI\Utils\Text;

use QUI;

/**
 * QUIQQER BBcode class
 *
 * @author  www.pcsg.de (Henning Leutz)
 * @package com.pcsg.qutils
 *
 * @todo    check the class, the class is realy old, maybe this can be done better
 * @todo    docu translation
 */
class BBCode extends QUI\QDOM
{
    /**
     * the project string
     *
     * @var string
     */
    protected $projects = [];

    /**
     * internal smiley list
     *
     * @var array
     */
    protected $smileys = [];

    /**
     * internal output smileys
     *
     * @var array
     */
    protected $output_smiley = [];

    /**
     * bbcode to html plugin list
     *
     * @var array
     */
    protected $plugins_bbcode_to_html = [];

    /**
     * html to bbcode plugin list
     *
     * @var array
     */
    protected $plugins_html_to_bbcode = [];

    /**
     * Wandelt HTML Tags in BBCode um
     *
     * @param string $html
     *
     * @return string
     */
    public function parseToBBCode($html)
    {
        // Normal HTML Elemente
        $bbcode = str_replace([
            '<b>',
            '</b>',
            '<strong>',
            '</strong>',
            '<i>',
            '</i>',
            '<u>',
            '</u>',
            '<del>',
            '</del>',
            '<strike>',
            '</strike>',
            '<ul>',
            '</ul>',
            '<li>',
            '</li>',
            '<br>',
            '<br />',
            '<h1>',
            '</h1>',
            '<h2>',
            '</h2>',
            '<h3>',
            '</h3>',
            '<h4>',
            '</h4>',
            '<h5>',
            '</h5>',
            '<h6>',
            '</h6>'
        ], [
            '[b]',
            '[/b]',
            '[b]',
            '[/b]',
            '[i]',
            '[/i]',
            '[u]',
            '[/u]',
            '[s]',
            '[/s]',
            '[s]',
            '[/s]',
            '[ul]',
            '[/ul]',
            '[li]',
            '[/li]',
            '[br]',
            '[br]',
            '[h1]',
            '[/h1]',
            '[h2]',
            '[/h2]',
            '[h3]',
            '[/h3]',
            '[h4]',
            '[/h4]',
            '[h5]',
            '[/h5]',
            '[h6]',
            '[/h6]'
        ], $html);

        // Block Elemente
        $bbcode = \preg_replace(
            [
                '/<p[^>]*>(.*?)<\/p>/i',
                '/<pre[^>]*>(.*?)<\/pre>/i',
                '/<b [^>]*>/i',
                '/<strong [^>]*>/i',
                '/<i [^>]*>/i',
                '/<u [^>]*>/i',
                '/<ul [^>]*>/i',
                '/<li [^>]*>/i'
            ],
            [
                '[p]\\1[/p]',
                '[code]\\1[/code]',
                '[b]',
                '[b]',
                '[i]',
                '[u]',
                '[ul]',
                '[li]'
            ],
            $bbcode
        );

        $_smileys            = $this->getSmileyArrays();
        $this->output_smiley = $_smileys['classes'];

        $bbcode = \preg_replace_callback(
            '#<span([^>]*)><span>(.*?)<\/span><\/span>#is',
            [&$this, "outputsmileys"],
            $bbcode
        );

        $bbcode = \preg_replace_callback(
            '#<div([^>]*)>(.*?)<\/div>#is',
            [&$this, "output"],
            $bbcode
        );

        $bbcode = \preg_replace_callback(
            '#<span([^>]*)>(.*?)<\/span>#is',
            [&$this, "output"],
            $bbcode
        );

        $bbcode = \preg_replace_callback(
            '#<a([^>]*)>(.*?)<\/a>#is',
            [&$this, "outputlink"],
            $bbcode
        );

        $bbcode = \preg_replace_callback(
            '#<img([^>]*)>#i',
            [&$this, "outputImages"],
            $bbcode
        );

        // delete Line breaks
        $bbcode = \str_replace(["\r\n", "\n", "\r"], '', $bbcode);
        $bbcode = \str_replace(["<br>", "<br />"], "\n", $bbcode);

        $bbcode = QUI\Utils\Security\Orthos::removeHTML($bbcode);

        return $bbcode;
    }

    /**
     * Enter description here...
     *
     * @param array $params
     * @return string
     */
    private function output($params)
    {
        $params[1] = \str_replace('\"', '"', $params[1]);

        if (\strpos($params[1], 'style="') === false) {
            if (\substr($params[0], 0, 4) == '<div') {
                return '<br />'.$params[2].'<br />';
            }

            return $params[2];
        }

        // Style auseinander frimmeln
        $str = $params[2];
        $_s  = $params[1];

        $_s = \preg_replace(
            ['/style="([^"]*)"/i'],
            ['\\1'],
            $_s
        );

        if (\strpos($_s, 'font-weight') && \strpos($_s, 'bold')) {
            $str = '[b]'.$str.'[/b]';
        }

        if (\strpos($_s, 'font-style') && \strpos($_s, 'italic')) {
            $str = '[i]'.$str.'[/i]';
        }

        if (\strpos($_s, 'text-decoration') && \strpos($_s, 'underline')) {
            $str = '[u]'.$str.'[/u]';
        }

        if (\strpos($_s, 'text-decoration') && \strpos($_s, 'line-through')) {
            $str = '[s]'.$str.'[/s]';
        }


        if (\strpos($_s, 'text-align') && \strpos($_s, 'center')) {
            $str = '[center]'.$str.'[/center]';
        }

        if (\strpos($_s, 'text-align') && \strpos($_s, 'left')) {
            $str = '[left]'.$str.'[/left]';
        }

        if (\strpos($_s, 'text-align') && \strpos($_s, 'right')) {
            $str = '[right]'.$str.'[/right]';
        }

        if (\substr($params[0], 0, 4) == '<div') {
            return '<br />'.$str.'<br />';
        }

        return $str;
    }

    /**
     * HTML Smileys in BBCode umwandeln
     *
     * @param string $_s - html string to replace
     * @return string
     */
    protected function outputsmileys($_s)
    {
        // Smileys
        $_s = \preg_replace(
            ['/.class="([^"]*)"/i'],
            ['\\1'],
            $_s
        );

        if (!isset($_s[1])) {
            return $_s;
        }

        if (isset($this->output_smiley[$_s[1]])) {
            return $this->output_smiley[$_s[1]];
        }

        return $_s[2];
    }

    /**
     * Parst Links in BBCode Links um
     *
     * @param array $params
     *
     * @return string
     */
    protected function outputlink($params)
    {
        $attributes = \str_replace('\"', '"', $params[1]);
        $cssclass   = 'extern';

        if (\strpos($attributes, 'class="intern"')) {
            $cssclass = 'intern';
        }

        $url  = \preg_replace('/(.*?)href="([^"]+).*"/is', '\\2', $attributes);
        $link = '[url="'.$url.'" class="'.$cssclass.'"]'.$params[2].'[/url]';

        return $link;
    }

    /**
     * Parst Bilder in BBCode Links um
     *
     * @param array $params
     *
     * @return string
     *
     * @deprecated
     */
    protected function outputImages($params)
    {
        $img = \str_replace('\"', '"', $params[0]);

        // Falls in der eigenen Sammlung schon vorhanden
        if (\strpos($img, 'image.php') !== false
            && \strpos($img, 'pms=1') !== false
        ) {
            $att = QUI\Utils\StringHelper::getHTMLAttributes($img);

            if (isset($att['src'])) {
                $src = \str_replace('&amp;', '&', $att['src']);
                $url = QUI\Utils\StringHelper::getUrlAttributes($src);

                if (isset($url['project']) && $url['id']) {
                    $project = $url['project'];
                    $id      = $url['id'];

                    if (!isset($this->projects[$project])) {
                        try {
                            $Project                  = new QUI\Projects\Project($project);
                            $this->projects[$project] = $Project;
                        } catch (QUI\Exception $e) {
                            return '';
                        }
                    }

                    /* @var $Project QUI\Projects\Project */
                    $Project = $this->projects[$project];
                    $Media   = $Project->getMedia();

                    try {
                        $Image = $Media->get((int)$id);
                        /* @var $Image QUI\Projects\Media\Image */
                    } catch (QUI\Exception $e) {
                        return '';
                    }

                    $str         = '[img="'.$Image->getUrl(true).'" ';
                    $_attributes = $this->size($att);

                    if (isset($_attributes['width'])) {
                        $str .= ' width="'.$_attributes['width'].'"';
                    }

                    if (isset($_attributes['height'])) {
                        $str .= ' height="'.$_attributes['height'].'"';
                    }

                    if (isset($att['align'])) {
                        $str .= ' align="'.$att['align'].'"';
                    }

                    $str .= ']';

                    return $str;
                }
            }
        }

        if (\strpos($img, '/media/cache/')
            || $this->getAttribute('extern_image')
        ) {
            $att = QUI\Utils\StringHelper::getHTMLAttributes($img);

            if (!isset($att['src'])) {
                return '';
            }

            $str = '[img="'.$att['src'].'"';

            $_attributes = $this->size($att);

            if (isset($_attributes['width'])) {
                $str .= ' width="'.$_attributes['width'].'"';
            }

            if (isset($_attributes['height'])) {
                $str .= ' height="'.$_attributes['height'].'"';
            }

            if (isset($att['align'])) {
                $str .= ' align="'.$att['align'].'"';
            }

            $str .= ']';

            return $str;
        }

        // externe Bilder werden nicht erlaubt
        return '';
    }

    /**
     * Enter description here...
     *
     * @param array $attributes
     *
     * @return string
     */
    protected function size($attributes)
    {
        $size = [];

        if (isset($attributes['style'])) {
            $style = QUI\Utils\StringHelper::splitStyleAttributes($attributes['style']);

            if (isset($style['width'])) {
                $size['width'] = (int)$style['width'];
            }

            if (isset($style['height'])) {
                $size['height'] = (int)$style['height'];
            }
        } else {
            if (isset($attributes['width'])) {
                $size['width'] = (int)$attributes['width'];
            }

            if (isset($attributes['height'])) {
                $size['height'] = (int)$attributes['height'];
            }
        }

        return $size;
    }

    /**
     * Entfernt HTML und wandelt BBCode in HTML um
     *
     * @param string $bbcode
     * @param boolean $delete_html - delete rest html which was not interpreted?
     *
     * @return string
     */
    public function parseToHTML($bbcode, $delete_html = true)
    {
        if ($delete_html) {
            $bbcode = QUI\Utils\Security\Orthos::removeHTML($bbcode);
        }

        // Normal HTML Elemente
        $html = \str_replace([
            '[b]',
            '[/b]',
            '[i]',
            '[/i]',
            '[u]',
            '[/u]',
            '[s]',
            '[/s]',
            '[li]',
            '[/li]',
            '[ul]',
            '[/ul]',
            '[center]',
            '[/center]',
            '[left]',
            '[/left]',
            '[right]',
            '[/right]',
            '[br]',
            '[h1]',
            '[/h1]',
            '[h2]',
            '[/h2]',
            '[h3]',
            '[/h3]',
            '[h4]',
            '[/h4]',
            '[h5]',
            '[/h5]',
            '[h6]',
            '[/h6]',
            /*
            ':-)', ':)',
            ':D', ':-D',
            ':-(', ':(',
            ':P', ':-P',
            ':confused:',
            ':shocked:'
            */
        ], [
            '<b>',
            '</b>',
            '<i>',
            '</i>',
            '<u>',
            '</u>',
            '<strike>',
            '</strike>',
            '<li>',
            '</li>',
            '<ul>',
            '</ul>',
            '<div style="text-align: center">',
            '</div>',
            '<div style="text-align: left">',
            '</div>',
            '<div style="text-align: right">',
            '</div>',
            '<br />',
            '<h1>',
            '</h1>',
            '<h2>',
            '</h2>',
            '<h3>',
            '</h3>',
            '<h4>',
            '</h4>',
            '<h5>',
            '</h5>',
            '<h6>',
            '</h6>',
            /*
            '<span class="smile_smile"><span>:-)</span></span>', '<span class="smile_smile"><span>:-)</span></span>',
            '<span class="smile_biggrin"><span>:D</span></span>', '<span class="smile_biggrin"><span>:D</span></span>',
            '<span class="smile_frown"><span>:-(</span></span>', '<span class="smile_frown"><span>:-(</span></span>',
            '<span class="smile_tongue"><span>:P</span></span>', '<span class="smile_tongue"><span>:P</span></span>',
            '<span class="smile_confused"><span>:S</span></span>',
            '<span class="smile_shocked"><span>:O</span></span>'
            */
        ], $bbcode);

        // Smileys
        $smileys = $this->getSmileyArrays();
        $html    = \str_replace($smileys['code'], $smileys['replace'], $html);

        // Block Elemente
        $html = \preg_replace(
            [
                '/\[p\](.*?)\[\/p\]/',
                '/\[code\](.*?)\[\/code\]/',
                '/\[php\](.*?)\[\/php\]/',
            ],
            [
                '<p>\\1</p>',
                '<pre class="code">\\1</pre>',
                '<pre class="php">\\1</pre>'
            ],
            $html
        );

        $html = \preg_replace_callback(
            '/\[url=([^\]]*)\](.*?)\[\/url\]/is',
            [&$this, "outputlinkhtml"],
            $html
        );

        $html = \preg_replace_callback(
            '/\[img=([^\]]*)]/is',
            [&$this, "outputImageHtml"],
            $html
        );

        $html = \preg_replace_callback(
            '/\[email([^\]]*)](.*?)\[\/email\]/is',
            [&$this, "outputMailHtml"],
            $html
        );

        // Line breaks
        $html = \str_replace(["\r\n", "\n", "\r"], "<br />", $html);

        return $html;
    }

    /**
     * Smileys Array
     *
     * @return array
     */
    protected function getSmileyArrays()
    {
        $_s_code    = [];
        $_s_replace = [];
        $_s_classes = [];
        $_smileys   = $this->smileys;

        foreach ($_smileys as $smiley => $class) {
            $_s_code[]    = $smiley;
            $_s_replace[] = '<span class="'.$class.'"><span>'.$smiley.'</span></span>';

            $_s_classes[$class] = $smiley;
        }

        return [
            'code'    => $_s_code,
            'replace' => $_s_replace,
            'classes' => $_s_classes
        ];
    }

    /**
     * Wandelt BBCode Links in HTML um
     *
     * @param array $params
     *
     * @return string
     */
    protected function outputlinkhtml($params)
    {
        $link = $params[2];
        $url  = \preg_replace('/"([^"]+).*"(.*?)/is', '\\1', $params[1]);

        $cssclass = 'extern';

        if (\strpos($url, 'http://') === false) {
            $cssclass = 'intern';
        }

        $url = \str_replace(['"', "'"], '', $url);

        return '<a href="'.$url.'" class="'.$cssclass.'">'.$link.'</a>';
    }

    /**
     * Wandelt BBCode Images in HTML um
     *
     * @param array $params
     *
     * @return string
     */
    protected function outputImageHtml($params)
    {
        $str = '<img ';
        $p   = \explode(' ', $params[1]);

        $str .= 'src="'.\str_replace('"', '', $p[0]).'" ';
        unset($p[0]);

        foreach ($p as $value) {
            if (!empty($value)) {
                $str .= $value.' ';
            }
        }

        $str .= '/>';

        return $str;
    }

    /**
     * Wandelt BBCode Email in HTML um
     *
     * @param array $params
     *
     * @return string
     */
    protected function outputMailHtml($params)
    {
        $str  = '<a ';
        $mail = \str_replace('=', '', $params[1]);

        if (empty($mail)) {
            $mail = $params[2];
        }

        $str .= 'href="mailto:'.$mail.'"';
        $str .= '>'.$params[2].'</a>';

        return $str;
    }

    /**
     * Fügt ein Smileys ein
     *
     * @param string $bbcode
     * @param string $cssclass
     */
    public function addSmiley($bbcode, $cssclass)
    {
        $this->smileys[$bbcode] = $cssclass;
    }

    /**
     * Entfernt ein Smiley Code
     *
     * @param string $bbcode
     *
     * @return boolean
     */
    public function removeSmiley($bbcode)
    {
        if (isset($this->smileys[$bbcode])) {
            unset($this->smileys[$bbcode]);
        }

        return true;
    }
}
