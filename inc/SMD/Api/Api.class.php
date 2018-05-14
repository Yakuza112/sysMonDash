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

namespace SMD\Api;

use SMD\Core\Config;
use SMD\Core\sysMonDash;

/**
 * Class Api para la implementación de un backend de sysMonDash remoto
 *
 * @package SMD\Api
 */
class Api
{
    const ACTION_EVENTS = 1;
    const ACTION_DOWNTIMES = 2;
    const ACTION_CHECK = 10;

    /**
     * @var bool
     */
    private $useJson;

    /**
     * Api constructor.
     * @param bool $useJson
     */
    public function __construct($useJson = false)
    {
        $this->useJson = $useJson;
    }

    /**
     * Devolver los eventos serializados y codificados en base64
     *
     * @return string
     * @throws \Exception
     */
    public function getEvents()
    {
        $SMD = new sysMonDash();
        $SMD->setCallType(sysMonDash::CALL_TYPE_API);

        if ($this->useJson) {
            return base64_encode(json_encode($SMD->getRawEvents()));
        }

        return base64_encode(serialize($SMD->getRawEvents()));
    }

    /**
     * Devolver las paradas programadas serializados y codificados en base64
     *
     * @return string
     * @throws \Exception
     */
    public function getDowntimes()
    {
        $SMD = new sysMonDash();
        $SMD->setCallType(sysMonDash::CALL_TYPE_API);

        if ($this->useJson) {
            return base64_encode(json_encode($SMD->getRawDowntimes()));
        }

        return base64_encode(serialize($SMD->getRawDowntimes()));
    }

    /**
     * Comprobar el token de seguridad
     *
     * @param $token
     * @return bool
     */
    public function checkToken($token)
    {
        return ($token === Config::getConfig()->getAPIToken());
    }
}