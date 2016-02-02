<?php
/**
 * sysMonDash
 *
 * @author    nuxsmin
 * @link      http://cygnux.org
 * @copyright 2014-2016 Rubén Domínguez nuxsmin@cygnux.org
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
 *
 */

namespace SMD\Core;

use SMD\Backend\Event\EventInterface;
use SMD\Backend\Livestatus;
use SMD\Backend\Status;
use SMD\Backend\Zabbix;
use SMD\Util\Util;

class sysMonDash
{
    public static $totalItems;
    public static $displayedItems;
    /**
     * @var array Los eventos a mostrar
     */
    private static $_outData;

    /**
     * Función para mostrar los avisos
     *
     * @param array $items Los elementos obtenidos desde Nagios/Icinga
     * @return array Con el número total de elementos y mostrados
     */
    public static function getItems(&$items)
    {
        // Ordenar los items por tiempo de último cambio
        Util::arraySortByProperty($items, 'lastHardStateChange');

        $newItemTime = Config::getConfig()->getNewItemTime();

        // Contador del no. de elementos
        self::$totalItems = 0;
        // Contador de elementos mostrados
        self::$displayedItems = 0;

        // Recorremos el array y mostramos los elementos
        foreach ($items as $item) {
            /** @var $item EventInterface */

            $newItemUp = ($item->getState() === 0 && ($item->getLastTimeUp() || $item->getLastTimeOk())) ? (abs(time() - $item->getLastHardStateChange()) < $newItemTime / 2) : false;

            // Detectar si es un elemento nuevo, no se trata de un "RECOVERY" y no está "ACKNOWLEDGED"
            $newItem = (time() - $item->getLastHardState() <= $newItemTime && !$newItemUp && !$item->isAcknowledged());

            // Mostrar elemento
            if (self::dashDisplay($item, $newItem, $newItemUp)) {
                self::$displayedItems++;
            }

            self::$totalItems++;
        }

        return self::$_outData;
    }

    /**
     * Función para mostrar los elementos del Dashboard
     *
     * @param EventInterface $item El elemento que contiene los datos.
     * @param bool $newItem Si es un nuevo elemento
     * @param bool $newItemUp Si es un nuevo elemento recuperado
     * @return bool
     */
    private static function dashDisplay(EventInterface $item, $newItem = false, $newItemUp = false)
    {
        global $type;

        $statusId = $item->getState();
        $ack = $item->isAcknowledged();
        $lastStateTime = date("m-d-Y H:i:s", $item->getLastHardStateChange());
        $lastStateDuration = Util::timeElapsed(time() - $item->getLastHardStateChange());
        $lastCheckDuration = Util::timeElapsed(time() - $item->getLastCheck());
        $serviceDesc = ($item->getDisplayName()) ? $item->getDisplayName() : $item->getCheckCommand();
        $hostname = ($item->getHostDisplayName()) ? $item->getHostDisplayName() : $item->getDisplayName();
        $hostAlias = ($item->getHostAlias()) ? $item->getHostAlias() : (($item->getAlias()) ? $item->getAlias() : $hostname);
        $scheduled = ($item->getScheduledDowntimeDepth() >= 1 || ($item->getHostScheduledDowntimeDepth() >= 1));
        $tdClass = '';
        $statusName = '';


        if (($type === VIEW_FRONTLINE || $type === VIEW_DISPLAY)
            && $newItem === false
            && $newItemUp === false
            && self::filterItems($item) === false
        ) {
            return false;
        }

        switch ($statusId) {
            case 0:
                $trClass = "new-up";
                $statusName = Language::t('OK');
                break;
            case 1:
                $trClass = "warning";
                $statusName = Language::t('AVISO');
                break;
            case 2:
                $trClass = "critical";
                $statusName = Language::t('CRITICO');
                break;
            case 3:
                $trClass = "unknown";
                $statusName = Language::t('DESCONOCIDO');
                break;
        }

        if (($item->getHostLastTimeUnreachable() > $item->getHostLastTimeUp() && !$newItemUp) ||
            ($item->getLastTimeUnreachable() > $item->getLastCheck() && $item->getStateType() === 1)
        ) {
//            $trTitle = Language::t("INALCANZABLE - Verificar objeto padre");
            $trClass = "unknown";
            $statusName = Language::t('INALCANZABLE');
        }

        if ($scheduled) {
//            $trTitle = Language::t("PROGRAMADO - Parada programada");
            $trClass = "downtime";
            $statusName = Language::t('PROGRAMADO');
        }

        if ($newItem === true && $ack === 0 && !$scheduled && !$newItemUp) {
            $tdClass = "new";
        } elseif ($newItemUp
            && time() - $item->getLastHardStateChange() <= Config::getConfig()->getNewItemTime() / 2
        ) {
//            $trTitle = Language::t("OK - Recuperado");
            $trClass = "new-up";
            $statusName = Language::t('RECUPERADO');
        } elseif ($item->isIsFlapping()) {
//            $trTitle = Language::t("CAMBIANTE - Frecuente cambio entre estados");
            $trClass = "flapping";
            $statusName = Language::t('CAMBIANTE');
        } elseif ($ack === 1) {
//            $trTitle = Language::t("RECONOCIDO - Error conocido");
            $trClass = "acknowledged";
            $statusName = Language::t('RECONOCIDO');
        }

//        $actionHostLink = (isset($item['pnpgraph_present']) && $item['pnpgraph_present'] !== -1) ? '<a href="/pnp4nagios/index.php/graph?host=' . $hostname . '&srv=_HOST_" rel="/pnp4nagios/index.php/popup?host=' . $hostname . '&srv=_HOST_" class="action-link" target="blank"><img src="imgs/graph.png" /></a>' : '';
        $actionHostLink = '';

        // Si 'host_display_name' está presente, el item es un servicio
        if (!empty($item->getHostDisplayName())) {
            $link = Config::getConfig()->getCgiURL() . '/extinfo.cgi?type=1&host=' . $hostname;
            $actionServiceLink = '';
        } else {
            $link = Config::getConfig()->getCgiURL() . '/extinfo.cgi?type=2&host=' . $hostname . '&service=' . urlencode($serviceDesc);
            $actionServiceLink = '';
        }

        $line = '<tr class="item-data ' . $trClass . '" title="' . sprintf(Language::t('Estado %s desde %s'), $statusName, $lastStateTime) . '">' . PHP_EOL;
        $line .= '<td class="center">' . $statusName . '</td>';

        if (Config::getConfig()->isColLastcheck()) {
            $line .= '<td title="' . sprintf('%s : %s', Language::t('Último check'), $lastCheckDuration) . '" class="center ' . $tdClass . '">' . $lastStateDuration . '</td>' . PHP_EOL;
        }

        if (Config::getConfig()->isColHost()) {
            $line .= '<td><a href="' . $link . '" target="blank" title="' . $hostname . '">' . $hostAlias . '</a>' . $actionHostLink . '</td>' . PHP_EOL;
        }

        if (Config::getConfig()->isColStatusInfo()) {
            $line .= '<td class="statusinfo">' . $item->getPluginOutput() . '</td>' . PHP_EOL;
        }

        if (Config::getConfig()->isColService()) {
            $line .= '<td class="center">' . $serviceDesc . $actionServiceLink . '</td>' . PHP_EOL;
        }

        $line .= '</tr>' . PHP_EOL;

        self::$_outData[] = $line;

        return true;
    }

    /**
     * Función para filtrar los avisos a mostrar
     *
     * @param EventInterface $item El elemento a verificar
     * @return bool
     */
    private static function filterItems(EventInterface $item)
    {
        $hostname = ($item->getHostDisplayName()) ? $item->getHostDisplayName() : $item->getDisplayName();

        if ($item->isAcknowledged()
            || (!preg_match(Config::getConfig()->getRegexHostShow(), $hostname) && !in_array($hostname, Config::getConfig()->getCriticalItems()))
            || (Config::getConfig()->getRegexServiceNoShow()
                && is_array(Config::getConfig()->getCriticalItems())
                && preg_match(Config::getConfig()->getRegexServiceNoShow(), $item->getDisplayName())
                && !in_array($item->getDisplayName(), Config::getConfig()->getCriticalItems()))
            || ($item->getCurrentAttempt() <= $item->getMaxCheckAttempts() && $item->getStateType() === 0 && !$item->isIsFlapping())
            || ($item->getHostState() && $item->getState() > SERVICE_WARNING && $item->getHostState() >= HOST_DOWN)
            || ($item->getStateType() === 1 && $item->getLastTimeUnreachable() > $item->getLastCheck())
        ) {
            return false;
        }

        return true;
    }

    /**
     * Seleccionar el backend
     *
     * @return Livestatus|Status|Zabbix
     * @throws \Exception
     */
    public static function getBackend()
    {
        $backend = strtolower(Config::getConfig()->getBackend());

        switch ($backend) {
            case 'livestatus':
                return new Livestatus();
            case 'status':
                return new Status();
            case 'zabbix':
                return new Zabbix(Config::getConfig()->getZabbixVersion(), Config::getConfig()->getZabbixUrl(), Config::getConfig()->getZabbixUser(), Config::getConfig()->getZabbixPass());
                break;
            default:
                throw new \Exception(sprintf('Backend no soportado (%s)', $backend));
        }
    }
}