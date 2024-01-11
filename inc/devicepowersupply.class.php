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

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

/// Class DevicePowerSupply
class DevicePowerSupply extends CommonDevice {

   static protected $forward_entity_to = ['Item_DevicePowerSupply', 'Infocom'];

   static function getTypeName($nb = 0) {
      return _n('Power supply', 'Power supplies', $nb);
   }


   function getAdditionalFields() {

      return array_merge(
         parent::getAdditionalFields(),
         [
            __('ATX') => [
               'name'  => 'is_atx',
               'type'  => 'checkbox',
               'value' => $this->fields['is_atx']
            ],
            __('Power') => [
               'name'  => 'power',
               'type'  => 'text',
               'value' => $this->fields['power']
            ],
            _n('Model', 'Models', 1) => [
               'name'  => 'devicepowersupplymodels_id',
               'type'  => 'select',
               'values' => getOptionForItems('DevicePowerSupplyModel'),
               'value' => $this->fields['devicepowersupplymodels_id'],
               'actions' => getItemActionButtons(['info', 'add'], 'DevicePowerSupplyModel')
            ]
         ]
      );
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'is_atx',
         'name'               => __('ATX'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '12',
         'table'              => $this->getTable(),
         'field'              => 'power',
         'name'               => __('Power'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '13',
         'table'              => 'glpi_devicepowersupplymodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
      ];

      return $tab;
   }


   static function getHTMLTableHeader($itemtype, HTMLTableBase $base,
                                      HTMLTableSuperHeader $super = null,
                                      HTMLTableHeader $father = null, array $options = []) {

      $column = parent::getHTMLTableHeader($itemtype, $base, $super, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($itemtype) {
         case 'Computer' :
            Manufacturer::getHTMLTableHeader(__CLASS__, $base, $super, $father, $options);
            break;
      }
   }


   function getHTMLTableCellForItem(HTMLTableRow $row = null, CommonDBTM $item = null,
                                    HTMLTableCell $father = null, array $options = []) {

      $column = parent::getHTMLTableCellForItem($row, $item, $father, $options);

      if ($column == $father) {
         return $father;
      }

      switch ($item->getType()) {
         case 'Computer' :
            Manufacturer::getHTMLTableCellsForItem($row, $this, null, $options);
      }
   }

   public static function rawSearchOptionsToAdd($itemtype, $main_joinparams) {
      $tab = [];

      $tab[] = [
         'id'                 => '39',
         'table'              => 'glpi_devicepowersupplies',
         'field'              => 'designation',
         'name'               => static::getTypeName(1),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'string',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicepowersupplies',
               'joinparams'         => $main_joinparams
            ]
         ]
      ];

      return $tab;
   }


   static function getIcon() {
      return "fas fa-bolt";
   }
}
