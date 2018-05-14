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

namespace SMD\Backend;

use SMD\Core\ConfigBackend;

abstract class Backend
{
    /** @var bool */
    protected $allHeaders = false;
    /** @var  ConfigBackend */
    protected $backend;

    /**
     * @return boolean
     */
    public function isAllHeaders()
    {
        return $this->allHeaders;
    }

    /**
     * @param boolean $allHeaders
     */
    public function setAllHeaders($allHeaders)
    {
        $this->allHeaders = $allHeaders;
    }

    /**
     * @param mixed $backend
     */
    public function setBackend($backend)
    {
        $this->backend = $backend;
    }

    /**
     * @return ConfigBackend
     */
    public function getBackend()
    {
        return $this->backend;
    }

    /**
     * Mapear los nombres de los campos con sus valores
     *
     * @param array $fields Los campos
     * @param array $data Los datos
     * @return array
     */
    protected function mapDataValues($fields, &$data)
    {
        $fulldata = array();

        foreach ($data as $eventData) {
            $fulldata[] = array_combine($fields, $eventData);
        }

        return $fulldata;
    }
}