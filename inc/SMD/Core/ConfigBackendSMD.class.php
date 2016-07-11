<?php
/**
 * sysMonDash
 *
 * @author    nuxsmin
 * @link      http://cygnux.org
 * @copyright 2012-2016 Rubén Domínguez nuxsmin@cygnux.org
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
 * along with sysMonDash.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace SMD\Core;

class ConfigBackendSMD extends ConfigBackend
{
    /**
     * @var string
     */
    private $token = '';

    /**
     * ConfigBackendStatus constructor.
     * @param $url
     * @param $token
     * @param int $level
     */
    public function __construct($url, $token, $level = 0)
    {
        $this->setType(self::TYPE_SMD);
        $this->setUrl($url);
        $this->setToken($token);
        $this->setLevel($level);
    }

    /**
     * @return string
     */
    public function getToken()
    {
        return $this->token;
    }

    /**
     * @param string $token
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * @param int $level
     * @return mixed
     */
    public function setLevel($level)
    {
        $this->level = $level;
    }
}