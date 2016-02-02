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

use SMD\Core\Config;
use SMD\Core\Language;
use SMD\Core\sysMonDash;
use SMD\Http\Request;
use SMD\Util\Util;

define('APP_ROOT', '..');

require APP_ROOT . DIRECTORY_SEPARATOR . 'inc' . DIRECTORY_SEPARATOR . 'Base.php';

Request::checkCORS();

$type = Request::analyze('t', VIEW_FRONTLINE);
$timeout = Request::analyze('to', Config::getConfig()->getRefreshValue());

try {
    $Backend = sysMonDash::getBackend();

    // Obtener los avisos desde la monitorización
    $items = $Backend->getProblems();

    if ($items === false) {
        throw new Exception('No hay datos desde el backend');
    }

    $downtimes = $Backend->getScheduledDowntimesGroupped();
} catch (Exception $e) {
    error_log(implode(';', $e->getTrace()[0]));
    header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error - ' . utf8_decode(Language::t($e->getMessage())), true, 500);
    exit();
}

ob_start();

// Array con los avisos filtrados
$res = sysMonDash::getItems($items);

if ($type !== 1) {
    $showAll = '<a href="index.php?t=' . VIEW_ALL . '" title="' . Language::t('Mostrar los avisos ocultos') . '">' . Language::t('Mostrar Todos') . '</a>';
} else {
    $showAll = '<a href="index.php?t=' . VIEW_FRONTLINE . '" title="' . Language::t('Mostrar sólo avisos importantes') . '">' . Language::t('Mostrar Menos') . '</a>';
}

?>
    <table id="tblBoard" width="90%" border="0" class="boldtable" align="center">
        <thead class="head">
        <th width="3%"><?php echo Language::t('Estado'); ?></th>
        <?php if (Config::getConfig()->isColLastcheck()): ?>
            <th width="13%"><?php echo Language::t('Desde'); ?></th>
        <?php endif; ?>
        <?php if (Config::getConfig()->isColHost()): ?>
            <th width="25%"><?php echo Language::t('Host'); ?></th>
        <?php endif; ?>
        <?php if (Config::getConfig()->isColStatusInfo()): ?>
            <th width="30%"><?php echo Language::t('Información de Estado'); ?></th>
        <?php endif; ?>
        <?php if (Config::getConfig()->isColService()): ?>
            <th width="20%"><?php echo Language::t('Servicio'); ?></th>
        <?php endif; ?>
        </thead>

        <?php if (sysMonDash::$displayedItems === 0): ?>
            <tr>
                <td colspan="5">
                    <div id="nomessages">
                        <img src="imgs/smile.png"/>
                        <br>
                        <?php echo Language::t('No hay avisos para mostrar'); ?>
                    </div>
                </td>
            </tr>
            <script>jQuery("#tblBoard thead").hide()</script>
        <?php elseif (sysMonDash::$displayedItems > Config::getConfig()->getMaxDisplayItems()): ?>
            <tr>
                <td colspan="5">
                    <div id="nomessages" class="error">
                        <?php echo Language::t('Upss...parece que hay problemas'); ?>
                        <br>
                        <?php echo Language::t('Demasiados avisos'); ?> (<?php echo sysMonDash::$displayedItems; ?>)
                        <br>
                        <a href="<?php echo Config::getConfig()->getMonitorServerUrl(); ?>"><?php echo Language::t('Revisar incidencias en web de monitorización'); ?></a>
                    </div>
                </td>
            </tr>
            <script>jQuery("#tblBoard thead").hide()</script>
        <?php else: ?>
            <?php foreach ($res as $line): ?>
                <?php echo $line; ?>
            <?php endforeach; ?>
            <script>jQuery("#tblBoard thead").show()</script>
        <?php endif; ?>
        <tr id="total">
            <td colspan="5">
                <?php printf('%s | %d@%.4fs | auto %ds | %s', date('H:i:s', time()), sysMonDash::$displayedItems, microtime(true) - $time_start, $timeout, Config::getConfig()->getBackend()); ?>
                |
                <?php printf('%d/%d %s %s', sysMonDash::$displayedItems, sysMonDash::$totalItems, Language::t('avisos'), $showAll); ?>
            </td>
        </tr>
    </table>

<?php if (count($downtimes) > 0): ?>
    <div class="title"><?php echo Language::t('Apagados Programados'); ?></div>

    <table id="tblDowntime" border="0" align="center">
        <thead class="head">
        <tr>
            <th><?php echo Language::t('Servidor'); ?></th>
            <th><?php echo Language::t('Servicio'); ?></th>
            <th><?php echo Language::t('Estado'); ?></th>
            <th><?php echo Language::t('Inicio'); ?></th>
            <th><?php echo Language::t('Fin'); ?></th>
            <th><?php echo Language::t('Autor'); ?></th>
            <th><?php echo Language::t('Comentarios'); ?></th>
        </tr>
        </thead>
        <tbody>
        <?php foreach ($downtimes as $downtime): ?>
            <?php /** @var $downtime \SMD\Backend\Event\DowntimeInterface */ ?>
            <?php $tiempoRestante = $downtime->getStartTime() - time(); ?>
            <tr>
                <td><?php echo $downtime->getHostName(); ?></td>
                <td><?php echo ($downtime->getServiceDisplayName()) ? $downtime->getServiceDisplayName() : $downtime->getHostName(); ?></td>
                <td><?php echo ($tiempoRestante > 0) ? sprintf(Language::t('Quedan %s'), Util::timeElapsed($tiempoRestante)) : Language::t('En parada'); ?></td>
                <td><?php echo date('d-m-Y H:i', $downtime->getStartTime()); ?></td>
                <td><?php echo date('d-m-Y H:i', $downtime->getEndTime()); ?></td>
                <td><?php echo $downtime->getAuthor(); ?></td>
                <td><?php echo $downtime->getComment(); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<?php ob_end_flush(); ?>

<?php if (Util::checkRefreshSession()): ?>
    <script>
        console.info('RELOAD');
        window.location.href = window.location.href;
    </script>
<?php endif; ?>