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


/**
 * Phone Class
**/
class Phone extends CommonDBTM {
   use Glpi\Features\Clonable;

   // From CommonDBTM
   public $dohistory                   = true;

   static protected $forward_entity_to = ['Infocom', 'NetworkPort', 'ReservationItem',
                                          'Item_OperatingSystem', 'Item_Disk'];

   static $rightname                   = 'phone';
   protected $usenotepad               = true;

   public function getCloneRelations() :array {
      return [
         Item_OperatingSystem::class,
         Item_Devices::class,
         Infocom::class,
         NetworkPort::class,
         Contract_Item::class,
         Document_Item::class,
         Computer_Item::class,
         KnowbaseItem_Item::class
      ];
   }

   static function getTypeName($nb = 0) {
      //TRANS: Test of comment for translation (mark : //TRANS)
      return _n('Phone', 'Phones', $nb);
   }


   /**
    * @see CommonDBTM::useDeletedToLockIfDynamic()
    *
    * @since 0.84
   **/
   function useDeletedToLockIfDynamic() {
      return false;
   }


   function defineTabs($options = []) {

      $ong = [];
      $this->addDefaultFormTab($ong);
      $this->addImpactTab($ong, $options);
      $this->addStandardTab('Item_OperatingSystem', $ong, $options);
      $this->addStandardTab('Item_SoftwareVersion', $ong, $options);
      $this->addStandardTab('Item_Devices', $ong, $options);
      $this->addStandardTab('Item_Disk', $ong, $options);
      $this->addStandardTab('Computer_Item', $ong, $options);
      $this->addStandardTab('NetworkPort', $ong, $options);
      $this->addStandardTab('Infocom', $ong, $options);
      $this->addStandardTab('Contract_Item', $ong, $options);
      $this->addStandardTab('Document_Item', $ong, $options);
      $this->addStandardTab('KnowbaseItem_Item', $ong, $options);
      $this->addStandardTab('Ticket', $ong, $options);
      $this->addStandardTab('Item_Problem', $ong, $options);
      $this->addStandardTab('Change_Item', $ong, $options);
      $this->addStandardTab('Link', $ong, $options);
      $this->addStandardTab('Notepad', $ong, $options);
      $this->addStandardTab('Reservation', $ong, $options);
      $this->addStandardTab('Domain_Item', $ong, $options);
      $this->addStandardTab('Appliance_Item', $ong, $options);
      $this->addStandardTab('Log', $ong, $options);

      return $ong;
   }


   function prepareInputForAdd($input) {

      if (isset($input["id"]) && ($input["id"] > 0)) {
         $input["_oldID"] = $input["id"];
      }
      unset($input['id']);
      unset($input['withtemplate']);

      return $input;
   }


   function cleanDBonPurge() {

      $this->deleteChildrenAndRelationsFromDb(
         [
            Computer_Item::class,
            Item_Project::class,
         ]
      );

      Item_Devices::cleanItemDeviceDBOnItemDelete($this->getType(), $this->fields['id'],
                                                  (!empty($this->input['keep_devices'])));
   }


   /**
    * Print the phone form
    *
    * @param $ID integer ID of the item
    * @param $options array
    *     - target filename : where to go when done.
    *     - withtemplate boolean : template or basic item
    *
    * @return boolean item found
   **/
   function showForm($ID, $options = []) {
      global $CFG_GLPI;

      $isNew = $this->isNewID($ID) || (isset($options['withtemplate']) && $options['withtemplate'] == 2);

      $form = [
         'action' => $this->getFormURL(),
         'buttons' => [
            isset($this->fields["is_deleted"]) && $this->fields["is_deleted"] == 1 && self::canDelete() ? [
              'type' => 'submit',
              'name' => 'restore',
              'value' => __('Restore'),
              'class' => 'btn btn-secondary'
            ] : ($this->canUpdateItem() ? [
              'type' => 'submit',
              'name' => $isNew ? 'add' : 'update',
              'value' => $isNew ? __('Add') : __('Update'),
              'class' => 'btn btn-secondary'
            ] : []),
            !$isNew && !$this->isDeleted() && $this->canDeleteItem() ? [
              'type' => 'submit',
              'name' => 'delete',
              'value' => __('Put in trashbin'),
              'class' => 'btn btn-danger'
            ] : (!$isNew && self::canPurge() ? [
              'type' => 'submit',
              'name' => 'purge',
              'value' => __('Delete permanently'),
              'class' => 'btn btn-danger'
            ] : []),
          ],
         'content' => [
            __('General') => [
               'visible' => true,
               'inputs' => [
                  __("Name") => [
                     'name' => 'name',
                     'type' => 'text',
                     'value' => $this->fields['name'],
                     'placeholder' => ''
                  ],
                  __('Status') => [
                     'name' => 'states_id',
                     'type' => 'select',
                     'itemtype' => State::class,
                     'conditions' => ['is_visible_phone' => 1],
                     'value' => $this->fields['states_id'],
                     'actions' => getItemActionButtons(['info', 'add'], "State"),
                  ],
                  __('Location') => [
                     'name' => 'locations_id',
                     'type' => 'select',
                     'itemtype' => Location::class,
                     'value' => $this->fields['locations_id'],
                     'actions' => getItemActionButtons(['info', 'add'], "Location"),
                  ],
                  _n('Type', 'Types', 1) => [
                     'name' => 'computertypes_id',
                     'type' => 'select',
                     'values' => getOptionForItems('PhoneType'),
                     'value' => $this->fields['phonetypes_id'],
                     'actions' => getItemActionButtons(['info', 'add'], "PhoneType"),
                  ],
                  __("Technician in charge of the hardware") => [
                     'name' => 'users_id_tech',
                     'type' => 'select',
                     'values' => getOptionsForUsers('own_ticket', ['entities_id' => $this->fields['entities_id']]),
                     'value' => $this->fields['users_id_tech'],
                     'actions' => getItemActionButtons(['info'], "User"),
                  ],
                  Manufacturer::getTypeName(1) => [
                     'name' => 'manufacturers_id',
                     'type' => 'select',
                     'values' => getOptionForItems('Manufacturer'),
                     'value' => $this->fields['manufacturers_id'],
                     'actions' => getItemActionButtons(['info', 'add'], "Manufacturer"),
                  ],
                  __('Group in charge of the hardware') => [
                     'name' => 'groups_id_tech',
                     'type' => 'select',
                     'itemtype' => Group::class,
                     'conditions' => ['is_assign' => 1],
                     'value' => $this->fields['groups_id_tech'],
                     'actions' => getItemActionButtons(['info', 'add'], "Group"),
                  ],
                  _n('Model', 'Models', 1) => [
                     'name' => 'phonemodels_id',
                     'type' => 'select',
                     'values' => getOptionForItems('PhoneModel'),
                     'value' => $this->fields['phonemodels_id'],
                     'actions' => getItemActionButtons(['info', 'add'], "PhoneModel"),
                  ],
                  __('Alternate username number') => [
                     'name' => 'contact_num',
                     'type' => 'text',
                     'value' => $this->fields['contact_num'],
                  ],
                  __('Serial number') => [
                     'name' => 'serial',
                     'type' => 'text',
                     'value' => $this->fields['serial'],
                  ],
                  __('Inventory number') => [
                     'name' => 'otherserial',
                     'type' => 'text',
                     'value' => $this->fields['otherserial'],
                  ],
                  __('Alternate username') => [
                     'name' => 'contact',
                     'type' => 'text',
                     'value' => $this->fields['contact'],
                  ],
                  User::getTypeName(1) => [
                     'name' => 'users_id',
                     'type' => 'select',
                     'values' => getOptionsForUsers('all', ['entities_id' => $this->fields['entities_id']]),
                     'value' => $this->fields['users_id'],
                     'actions' => getItemActionButtons(['info'], "User"),
                  ],
                  __('Management Type') => [
                     'name' => 'is_global',
                     'type' => 'select',
                     'values' => [
                           0 => __('Unit Management'),
                           1 => __('Global Management'),
                     ],
                     'disabled' => $CFG_GLPI['phones_management_restrict'] != 2 ,
                     'value' => $this->fields['is_global'],
                  ],
                  Group::getTypeName(1) => [
                     'name' => 'groups_id',
                     'type' => 'select',
                     'itemtype' => Group::class,
                     'conditions' => ['is_assign' => 1],
                     'value' => $this->fields["groups_id"],
                     'actions' => getItemActionButtons(['info', 'add'], "Group"),
                  ],
                  __('Brand') => [
                     'name' => 'brand',
                     'type' => 'text',
                     'value' => $this->fields['brand'],
                  ],
                  PhonePowerSupply::getTypeName(1) => [
                     'name' => 'phonepowersupplies_id',
                     'type' => 'select',
                     'values' => getOptionForItems('PhonePowerSupply'),
                     'value' => $this->fields['phonepowersupplies_id'],
                     'actions' => getItemActionButtons(['info', 'add'], "PhonePowerSupply"),
                  ],
                  _x('quantity', 'Number of lines') => [
                     'name' => 'number_line',
                     'type' => 'text',
                     'value' => $this->fields['number_line'],
                  ],
                  __("Comments") => [
                     'name' => 'comment',
                     'type' => 'textarea',
                     'value' => $this->fields['comment'],
                  ],
               ]
            ],
            __('Flags') => [
               'visible' => true,
               'inputs' => [
                  __('Headset') => [
                     'name' => 'have_headset',
                     'type' => 'checkbox',
                     'value' => $this->fields['have_headset'],
                  ],
                  __('Speaker') => [
                     'name' => 'have_hp',
                     'type' => 'checkbox',
                     'value' => $this->fields['have_hp'],
                  ],
               ]
            ],
         ]
      ];
      
      ob_start();
      Plugin::doHook("post_item_form", ['item' => $this, 'options' => [
         'colspan'      => 2,
         'withtemplate' => '',
         'candel'       => true,
         'canedit'      => true,
         'addbuttons'   => [],
         'formfooter'   => null,
         ]]);
      $additionnalHtml = ob_get_clean();
         
      renderTwigForm($form, $additionnalHtml, $this->fields);
      return true;
   }


   /**
    * Return the linked items (in computers_items)
    *
    * @return an array of linked items  like array('Computer' => array(1,2), 'Printer' => array(5,6))
    * @since 0.84.4
   **/
   function getLinkedItems() {
      global $DB;

      $iterator = $DB->request([
         'SELECT' => 'computers_id',
         'FROM'   => 'glpi_computers_items',
         'WHERE'  => [
            'itemtype'  => $this->getType(),
            'items_id'  => $this->fields['id']
         ]
      ]);
      $tab = [];
      while ($data = $iterator->next()) {
         $tab['Computer'][$data['computers_id']] = $data['computers_id'];
      }
      return $tab;
   }


   /**
    * @see CommonDBTM::getSpecificMassiveActions()
   **/
   function getSpecificMassiveActions($checkitem = null) {

      $actions = parent::getSpecificMassiveActions($checkitem);
      if (static::canUpdate()) {
         Computer_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);
         $actions += [
            'Item_SoftwareLicense'.MassiveAction::CLASS_ACTION_SEPARATOR.'add'
               => "<i class='ma-icon fas fa-key' aria-hidden='true'></i>".
                  _x('button', 'Add a license')
         ];
         KnowbaseItem_Item::getMassiveActionsForItemtype($actions, __CLASS__, 0, $checkitem);
      }

      return $actions;
   }


   function rawSearchOptions() {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'                 => '2',
         'table'              => $this->getTable(),
         'field'              => 'id',
         'name'               => __('ID'),
         'massiveaction'      => false,
         'datatype'           => 'number'
      ];

      $tab = array_merge($tab, Location::rawSearchOptionsToAdd());

      $tab[] = [
         'id'                 => '4',
         'table'              => 'glpi_phonetypes',
         'field'              => 'name',
         'name'               => _n('Type', 'Types', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '40',
         'table'              => 'glpi_phonemodels',
         'field'              => 'name',
         'name'               => _n('Model', 'Models', 1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '31',
         'table'              => 'glpi_states',
         'field'              => 'completename',
         'name'               => __('Status'),
         'datatype'           => 'dropdown',
         'condition'          => ['is_visible_phone' => 1]
      ];

      $tab[] = [
         'id'                 => '5',
         'table'              => $this->getTable(),
         'field'              => 'serial',
         'name'               => __('Serial number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '6',
         'table'              => $this->getTable(),
         'field'              => 'otherserial',
         'name'               => __('Inventory number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '7',
         'table'              => $this->getTable(),
         'field'              => 'contact',
         'name'               => __('Alternate username'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '8',
         'table'              => $this->getTable(),
         'field'              => 'contact_num',
         'name'               => __('Alternate username number'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '9',
         'table'              => $this->getTable(),
         'field'              => 'number_line',
         'name'               => _x('quantity', 'Number of lines'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '70',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'name'               => User::getTypeName(1),
         'datatype'           => 'dropdown',
         'right'              => 'all'
      ];

      $tab[] = [
         'id'                 => '71',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'name'               => Group::getTypeName(1),
         'condition'          => ['is_itemgroup' => 1],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '19',
         'table'              => $this->getTable(),
         'field'              => 'date_mod',
         'name'               => __('Last update'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '121',
         'table'              => $this->getTable(),
         'field'              => 'date_creation',
         'name'               => __('Creation date'),
         'datatype'           => 'datetime',
         'massiveaction'      => false
      ];

      $tab[] = [
         'id'                 => '16',
         'table'              => $this->getTable(),
         'field'              => 'comment',
         'name'               => __('Comments'),
         'datatype'           => 'text'
      ];

      $tab[] = [
         'id'                 => '11',
         'table'              => $this->getTable(),
         'field'              => 'brand',
         'name'               => __('Brand'),
         'datatype'           => 'string',
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '23',
         'table'              => 'glpi_manufacturers',
         'field'              => 'name',
         'name'               => Manufacturer::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '32',
         'table'              => 'glpi_devicefirmwares',
         'field'              => 'version',
         'name'               => _n('Firmware', 'Firmware', 1),
         'forcegroupby'       => true,
         'usehaving'          => true,
         'massiveaction'      => false,
         'datatype'           => 'dropdown',
         'joinparams'         => [
            'beforejoin'         => [
               'table'              => 'glpi_items_devicefirmwares',
               'joinparams'         => [
                  'jointype'           => 'itemtype_item',
                  'specific_itemtype'  => 'Phone'
               ]
            ]
         ]
      ];

      $tab[] = [
         'id'                 => '24',
         'table'              => 'glpi_users',
         'field'              => 'name',
         'linkfield'          => 'users_id_tech',
         'name'               => __('Technician in charge of the hardware'),
         'datatype'           => 'dropdown',
         'right'              => 'own_ticket'
      ];

      $tab[] = [
         'id'                 => '49',
         'table'              => 'glpi_groups',
         'field'              => 'completename',
         'linkfield'          => 'groups_id_tech',
         'name'               => __('Group in charge of the hardware'),
         'condition'          => ['is_assign' => 1],
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '42',
         'table'              => 'glpi_phonepowersupplies',
         'field'              => 'name',
         'name'               => DevicePowerSupply::getTypeName(1),
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '43',
         'table'              => $this->getTable(),
         'field'              => 'have_headset',
         'name'               => __('Headset'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '44',
         'table'              => $this->getTable(),
         'field'              => 'have_hp',
         'name'               => __('Speaker'),
         'datatype'           => 'bool'
      ];

      $tab[] = [
         'id'                 => '61',
         'table'              => $this->getTable(),
         'field'              => 'template_name',
         'name'               => __('Template name'),
         'datatype'           => 'text',
         'massiveaction'      => false,
         'nosearch'           => true,
         'nodisplay'          => true,
         'autocomplete'       => true,
      ];

      $tab[] = [
         'id'                 => '80',
         'table'              => 'glpi_entities',
         'field'              => 'completename',
         'name'               => Entity::getTypeName(1),
         'massiveaction'      => false,
         'datatype'           => 'dropdown'
      ];

      $tab[] = [
         'id'                 => '82',
         'table'              => $this->getTable(),
         'field'              => 'is_global',
         'name'               => __('Global management'),
         'datatype'           => 'bool',
         'massiveaction'      => false
      ];

      $tab = array_merge($tab, Notepad::rawSearchOptionsToAdd());

      return $tab;
   }


   static function getIcon() {
      return "fas fa-phone";
   }
}
