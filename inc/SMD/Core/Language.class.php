<?php
/**
 * sysMonDash
 *
 * @author     nuxsmin
 * @link       https://github.com/nuxsmin/sysMonDash
 * @copyright  2012-2018 Rubén Domínguez nuxsmin@cygnux.org
 *
 * This file is part of sysMonDash.
 *
 * sysMonDash is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * sysMonDash is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with sysMonDash. If not, see <http://www.gnu.org/licenses/gpl-3.0-standalone.html>.
 */

namespace SMD\Core;

class Language
{
    /**
     * @var string
     */
    private static $_lang = '';

    /**
     * Traducir una cadena
     *
     * @param $string
     * @return mixed
     */
    public static function t($string)
    {
        self::$_lang = self::getGlobalLang();

        return (self::$_lang === 'es_ES') ? $string : self::getTranslation($string);
    }

    /**
     * Establece el lenguaje de la aplicación.
     * Esta función establece el lenguaje según esté definido en la configuración o en el navegador.
     */
    private static function getGlobalLang()
    {
        $language = Config::getConfig()->getLanguage();
        $browserLang = self::getBrowserLang();

        // Establecer a es_ES si no existe la traducción o no está establecido el lenguaje
        if (!empty($language)
            && (self::checkLangFile($language)
                || $language = 'es_ES')
        ) {
            return $language;
        }

        if (stripos($browserLang, 'es_') === 0) {
            return 'es_ES';
        }

        if (self::checkLangFile($browserLang)
        ) {
            return $browserLang;
        }
    }

    /**
     * Devolver el lenguaje que acepta el navegador
     *
     * @return mixed
     */
    private static function getBrowserLang()
    {
        return str_replace('-', '_', substr($_SERVER['HTTP_ACCEPT_LANGUAGE'], 0, 5));
    }

    /**
     * Comprobar si el archivo de lenguaje existe
     *
     * @param string $lang El lenguaje a comprobar
     * @return bool
     */
    private static function checkLangFile($lang)
    {
        return file_exists(self::getLangFile($lang));
    }

    /**
     * Devolver el nombre del archivo de idioma
     *
     * @param $lang
     * @return string
     */
    private static function getLangFile($lang)
    {
        return LOCALES_PATH . DIRECTORY_SEPARATOR . "$lang.inc";
    }

    /**
     * Obtener la traducción desde la sesión o el archivo de idioma
     *
     * @param $string
     * @return mixed
     */
    private static function getTranslation($string)
    {
        $sessionLang = Session::getLanguage();

        if ($sessionLang === false
            && self::checkLangFile(self::$_lang)
        ) {
            $sessionLang = include_once self::getLangFile(self::$_lang);

            if (!is_array($sessionLang)) {
                return $string;
            }

            Session::setLanguage($sessionLang);
        }

        return isset($sessionLang[$string]) ? $sessionLang[$string] : $string;
    }
}