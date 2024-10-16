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

/**
 * @since 9.1
 */

use Glpi\Event;

include('../inc/includes.php');

Session::checkRight("slm", READ);

if (empty($_GET["id"])) {
    $_GET["id"] = "";
}

$ola = new OLA();

if (isset($_POST["add"])) {
    $ola->check(-1, CREATE, $_POST);

    if ($newID = $ola->add($_POST)) {
        Event::log(
            $newID,
            "olas",
            4,
            "setup",
            sprintf(__('%1$s adds the item %2$s'), $_SESSION["glpiname"], $_POST["name"])
        );
        if ($_SESSION['glpibackcreated']) {
            Html::redirect($ola->getLinkURL());
        }
    }
    Html::back();
} elseif (isset($_POST["purge"])) {
    $ola->check($_POST["id"], PURGE);
    $ola->delete($_POST, 1);

    Event::log(
        $_POST["id"],
        "olas",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s purges an item'), $_SESSION["glpiname"])
    );
    $ola->redirectToList();
} elseif (isset($_POST["update"])) {
    $ola->check($_POST["id"], UPDATE);
    $ola->update($_POST);

    Event::log(
        $_POST["id"],
        "olas",
        4,
        "setup",
        //TRANS: %s is the user login
        sprintf(__('%s updates an item'), $_SESSION["glpiname"])
    );
    Html::back();
} else {
    Html::header(OLA::getTypeName(1), $_SERVER['PHP_SELF'], "config", "slm", "ola");

    $ola->display(['id' => $_GET["id"]]);
    Html::footer();
}
