<?php

/**
 * This file contains \QUI\Utils\Translation\PSpell
 */

namespace QUI\Utils\Translation;

use PSpell\Dictionary;
use QUI;
use QUI\Exception;

use function function_exists;
use function pspell_config_create;
use function pspell_config_mode;
use function pspell_config_personal;
use function pspell_new;
use function pspell_suggest;

/**
 * Easier Access to pspell
 *
 * @author  www.pcsg.de (Henning Leutz)
 *
 * @uses    pspell
 * @todo    check it, class is at the moment not in use
 *
 * @example $Trans = new \QUI\Utils\Translation\PSpell(array(
 *        'lang'    => 'en',
 *        'dialect' => 'american'
 * ));
 *
 * $Trans->translate('House');
 */
class PSpell extends QUI\QDOM
{
    /**
     * internal pspell object
     *
     * @var Dictionary|false $Spell
     */
    protected Dictionary|false $Spell;

    /**
     * Constructor
     *
     * @param array $settings - array(
     *                        lang
     *                        dialect
     *                        personal
     *                        );
     */
    public function __construct(array $settings)
    {
        // defaults
        $this->setAttribute('lang', 'en');
        $this->setAttribute('dialect', 'american');

        $this->setAttributes($settings);


        // PSpell Config
        $Config = pspell_config_create(
            $this->getAttribute('lang'),
            $this->getAttribute('dialect')
        );

        pspell_config_mode($Config, "PSPELL_FAST");

        if ($this->getAttribute('personal')) {
            pspell_config_personal($Config, $this->getAttribute('personal'));
        }

        $this->Spell = pspell_new($Config);
    }

    /**
     * Check if pspell is installed
     *
     * @return boolean
     * @throws Exception
     */
    public static function check(): bool
    {
        if (!function_exists('pspell_new')) {
            throw new Exception('PSpell is not installed');
        }

        return true;
    }

    /**
     * Translate a String
     *
     * @param string $word
     *
     * @return array
     */
    public function translate(string $word): array
    {
        return pspell_suggest($this->Spell, $word);
    }
}
