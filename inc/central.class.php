<?php
/**
 * ---------------------------------------------------------------------
 * GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2015-2022 Teclib' and contributors.
 *
 * http://glpi-project.org
 *
 * based on GLPI - Gestionnaire Libre de Parc Informatique
 * Copyright (C) 2003-2014 by the INDEPNET Development Team.
 *
 * ---------------------------------------------------------------------
 *
 * LICENSE
 *
 * This file is part of GLPI.
 *
 * GLPI is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * GLPI is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with GLPI. If not, see <http://www.gnu.org/licenses/>.
 * ---------------------------------------------------------------------
 */

use Glpi\Event;
use PharIo\Manifest\License;
use Sabre\HTTP\HttpException;

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/**
 * Central class
**/
class Central extends CommonGLPI {


   static function getTypeName($nb = 0) {

      // No plural
      return __('Standard interface');
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addStandardTab(__CLASS__, $ong, $options);

      return $ong;
   }


   function getTabNameForItem(CommonGLPI $item, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         $tabs = [
            0 => __('Dashboard'),
            1 => __('Personal View'),
            2 => __('Group View'),
            3 => __('Global View'),
            4 => _n('RSS feed', 'RSS feeds', Session::getPluralNumber()),
         ];
         return $tabs;
      }
      return '';
   }


   static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0) {

      if ($item->getType() == __CLASS__) {
         switch ($tabnum) {
            case 0 :
               $item->showGlobalDashboard();
               break;

            case 1 :
               $item->showMyView();
               break;

            case 2 :
               $item->showGroupView();
               break;

            case 3 :
               $item->showGlobalView();
               break;

            case 4 :
               $item->showRSSView();
               break;
         }
      }
      return true;
   }

   public function showGlobalDashboard() {
      echo "<table class='tab_cadre_central' aria-label='Tickets by Status Table'>";
      Plugin::doHook('display_central');
      echo "</table>";

      $dashboard = new Dashboard();
      if ($dashboard->getForUser()) {
         $dashboard->show();
      } else {
         $grid = [
            __('General') => [
                'right' => true,
                'items' => [
                    Entity::class,
                    User::class,
                    Budget::class,
                ]
            ],
            __('Assets') => [
                'right' => true,
                'items' => [
                    Computer::class,
                    Monitor::class,
                    NetworkEquipment::class,
                    Peripheral::class,
                    Phone::class,
                    Printer::class,
                    Software::class,
                ]
            ],
            __('Tickets') => [
                'right' => true,
                'items' => [
                    Ticket::class,
                    Problem::class,
                    Change::class,
                ]
            ],
         ];
         foreach ($grid as $title => $section) {
            if (!$section['right'])
               continue;
            echo <<<HTML
                <div class="card my-3">
                    <div class="card-header">
                        <h5 class="card-title">$title</h5>
                    </div>
                    <div class="card-body">
            HTML;
            $dashboard = ['widgetGrid' => [[]]];
            foreach ($section['items'] as $item) {
               $dashboard['widgetGrid'][0][] = [
                     'title' => $item::getTypeName(2),
                     'value' => countElementsInTableForMyEntities($item::getTable()),
                     'icon' => $item::getIcon(),
               ];
            }
            renderTwigTemplate('dashboard/dashboard.twig', $dashboard);
            echo "</div></div>";
         }
      }

      Html::accessibilityHeader();
   }


   /**
    * Show the central global view
   **/
   static function showGlobalView() {

      $showticket  = Session::haveRight("ticket", Ticket::READALL);
      $showproblem = Session::haveRight("problem", Problem::READALL);

      echo "<table class='tab_cadre_central'><tr class='noHover' aria-label='Global View Outer Table'>";
      echo "<td class='top' width='50%'>";
      echo "<table class='central' aria-label='Global View Inner Table'>";
      echo "<tr class='noHover'><td>";
      if ($showticket) {
         Ticket::showCentralCount();
      } else {
         Ticket::showCentralCount(true);
      }
      if ($showproblem) {
         Problem::showCentralCount();
      }
      if (Contract::canView()) {
         Contract::showCentral();
      }
      echo "</td></tr>";
      echo "</table></td>";

      if (Session::haveRight("logs", READ)) {
         echo "<td class='top'  width='50%'>";

         //Show last add events
         Event::showForUser($_SESSION["glpiname"]);
         echo "</td>";
      }
      echo "</tr></table>";

      if ($_SESSION["glpishow_jobs_at_login"] && $showticket) {
         echo "<br>";
         Ticket::showCentralNewList();
      }
   }


   /**
    * Show the central personal view
   **/
   static function showMyView() {
      global $CFG_GLPI;
      $objects = $CFG_GLPI['globalsearch_types'];
      asort($objects);
      $values = [];
      foreach ($objects as $object) {
         $values[$object] = ((string) $object)::getTypeName();
      }
      $jsUpdate = <<<JS
         $.ajax({
            url: "{$CFG_GLPI['root_doc']}/src/dashboard/dashboard.ajax.php",
            data: {
               action: "getSearch",
               itemtype: $('#ItemTypeDropdownForDashboard').val(),
            },
            success: function(data) {
               $('#data-selection-search-content').html(data);
               $('#data-selection-search-content form').attr('action', "#");
               fetchPreview("{$CFG_GLPI['root_doc']}/src/dashboard/dashboard.ajax.php");
            }
         });
      JS;
      ob_start();
      renderTwigTemplate('macros/wrappedInput.twig', [
         'title' => __('Itemtype'),
         'input' => [
            'type' => 'select',
            'id' => 'ItemTypeDropdownForDashboard',
            'values' => $values,
            'value' => array_key_first($values),
            'col_lg' => 12,
            'col_md' => 12,
            'init' => $jsUpdate,
            'hooks' => [
               'change' => $jsUpdate,
            ],
            'noLib' => true,
         ]
      ]);
      renderTwigTemplate('dashboard/dashboard.twig', [
        'edit' => true,
        'dataSelection' => ob_get_clean(),
        'widgetGrid' => [
            [
                ['title' => 'test', 'value' => 'test', 'icon' => 'fa fa-cogs'],
            ],
        ]
      ]);
      $showticket  = Session::haveRightsOr("ticket",
                                           [Ticket::READMY, Ticket::READALL, Ticket::READASSIGN]);

      $showproblem = Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);

      echo "<table class='tab_cadre_central' aria-label='My View Outer Table'>";

      echo "<tr><th colspan='2'>";
      echo "</th></tr>";

      echo "<tr class='noHover'><td class='top' width='50%'><table class='central' aria-label='My View Inner Table'>";
      echo "<tr class='noHover'><td>";
      if (Session::haveRightsOr('ticketvalidation', TicketValidation::getValidateRights())) {
         Ticket::showCentralList(0, "tovalidate", false);
      }
      if ($showticket) {

         if (Ticket::isAllowedStatus(Ticket::SOLVED, Ticket::CLOSED)) {
            Ticket::showCentralList(0, "toapprove", false);
         }

         Ticket::showCentralList(0, "survey", false);

         Ticket::showCentralList(0, "validation.rejected", false);
         Ticket::showCentralList(0, "solution.rejected", false);
         Ticket::showCentralList(0, "requestbyself", false);
         Ticket::showCentralList(0, "observed", false);

         Ticket::showCentralList(0, "process", false);
         Ticket::showCentralList(0, "waiting", false);

         TicketTask::showCentralList(0, "todo", false);

      }
      if ($showproblem) {
         Problem::showCentralList(0, "process", false);
         ProblemTask::showCentralList(0, "todo", false);
      }
      echo "</td></tr>";
      echo "</table></td>";
      echo "<td class='top'  width='50%'><table class='central' aria-label='central Table'>";
      echo "<tr class='noHover'><td>";
      Planning::showCentral(Session::getLoginUserID());
      Reminder::showListForCentral();
      if (Session::haveRight("reminder_public", READ)) {
         Reminder::showListForCentral(false);
      }
      echo "</td></tr>";
      echo "</table></td></tr></table>";
      $twig = Twig::load(GLPI_ROOT . "/templates/dashboard", false, true);
      echo $twig->render('RadarChart.twig');
   }


   /**
    * Show the central RSS view
    *
    * @since 0.84
   **/
   static function showRSSView() {

      echo "<table class='tab_cadre_central' aria-label='RSS View Table'>";

      echo "<tr class='noHover'><td class='top' width='50%'>";
      RSSFeed::showListForCentral();
      echo "</td><td class='top' width='50%'>";
      if (RSSFeed::canView()) {
         RSSFeed::showListForCentral(false);
      } else {
         echo "&nbsp;";
      }
      echo "</td></tr>";
      echo "</table>";
   }


   /**
    * Show the central group view
   **/
   static function showGroupView() {

      $showticket = Session::haveRightsOr("ticket", [Ticket::READALL, Ticket::READASSIGN]);

      $showproblem = Session::haveRightsOr('problem', [Problem::READALL, Problem::READMY]);

      echo "<table class='tab_cadre_central' aria-label='Group Table'>";
      echo "<tr class='noHover'><td class='top' width='50%'><table class='central' aria-label='group view'>";
      echo "<tr class='noHover'><td>";
      if ($showticket) {
         Ticket::showCentralList(0, "process", true);
         TicketTask::showCentralList(0, "todo", true);
      }
      if (Session::haveRight('ticket', Ticket::READGROUP)) {
         Ticket::showCentralList(0, "waiting", true);
      }
      if ($showproblem) {
         Problem::showCentralList(0, "process", true);
         ProblemTask::showCentralList(0, "todo", true);
      }

      echo "</td></tr>";
      echo "</table></td>";
      echo "<td class='top' width='50%'><table class='central' aria-label='Group View'>";
      echo "<tr class='noHover'><td>";
      if (Session::haveRight('ticket', Ticket::READGROUP)) {
         Ticket::showCentralList(0, "observed", true);
         Ticket::showCentralList(0, "toapprove", true);
         Ticket::showCentralList(0, "requestbyself", true);
      } else {
         Ticket::showCentralList(0, "waiting", true);
      }
      echo "</td></tr>";
      echo "</table></td></tr></table>";
   }


   static function showMessages() {
      global $DB, $CFG_GLPI;

      $warnings = [];

      Plugin::doHook('display_central');
      $user = new User();
      $user->getFromDB(Session::getLoginUserID());
      if ($user->fields['authtype'] == Auth::DB_GLPI && $user->shouldChangePassword()) {
         $expiration_msg = sprintf(
            __('Your password will expire on %s.'),
            Html::convDateTime(date('Y-m-d H:i:s', $user->getPasswordExpirationTime()))
         );
         $warnings[] = $expiration_msg
            . ' '
            . '<a href="' . $CFG_GLPI['root_doc'] . '/front/updatepassword.php">'
            . __('Update my password')
            . '</a>';
      }

      if (Session::haveRight("config", UPDATE)) {
         $logins = User::checkDefaultPasswords();
         $user   = new User();
         if (!empty($logins)) {
            $accounts = [];
            foreach ($logins as $login) {
               $user->getFromDBbyNameAndAuth($login, Auth::DB_GLPI, 0);
               $accounts[] = $user->getLink();
            }
            $warnings[] = sprintf(__('For security reasons, please change the password for the default users: %s'),
                               implode(" ", $accounts));
         }

         if (file_exists(GLPI_ROOT . "/install/install.php")) {
            $warnings[] = sprintf(__('For security reasons, please remove file: %s'),
                               "install/install.php");
         }

         $myisam_tables = $DB->getMyIsamTables();
         if (count($myisam_tables)) {
            $warnings[] = sprintf(
               __('%1$s tables not migrated to InnoDB engine.'),
               count($myisam_tables)
            );
         }
         if ($DB->areTimezonesAvailable()) {
            $not_tstamp = $DB->notTzMigrated();
            if ($not_tstamp > 0) {
                $warnings[] = sprintf(
                    __('%1$s columns are not compatible with timezones usage.'),
                    $not_tstamp
                );
            }
         }
      }

      if ($DB->isSlave()
          && !$DB->first_connection) {
         $warnings[] = __('SQL replica: read only');
      }

      if (count($warnings)) {
      ?>
         <div class='alert alert-warning'>
            <?php echo "<ul><li>" . implode('</li><li>', $warnings) . "</li></ul>" ?>
         </div>
      <?php
      }
   }

}
