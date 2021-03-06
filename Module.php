<?php

/**
 * @author Ted Eberhard <eberhardtm at appstate dot edu>
 */

namespace patientexport;

class Module extends \Canopy\Module implements \Canopy\SettingDefaults {

    public function __construct() {
        parent::__construct();
        $this->setTitle('sysinventory');
        $this->setProperName('Systems Inventory');
    }

    public function getController(\Canopy\Request $request) {
        $cmd = $request->shiftCommand();

        if (\Current_User::allow('sysinventory')) {

            switch ($cmd) {
                case 'patient':
                        $system = new \patientexport\Controller\Patient($this);
                        return $system;                    
                case 'settings':
                    if (\Current_User::allow('systemsinventory', 'settings')) {
                        $settings = new \systemsinventory\Controller\Settings($this);
                        return $settings;
                    }
                default:
                    $search = new \patientexport\Controller\Search($this);
                    return $search;
            }
        } else {
            \Current_User::requireLogin();
        }
    }

    public function runTime(\Canopy\Request $request) {
        if (\Current_User::allow('sysinventory'))
            \patientexport\Controller\Patient::loadAdminBar();

        if (\PHPWS_Core::atHome() && \Current_User::isLogged()) {
            $path = $_SERVER['SCRIPT_NAME'] . '?module=patientexport';
            header('HTTP/1.1 303 See Other');
            header("Location: $path");
            exit();
        }
    }

    public function getSettingDefaults() {
        // ContactInfo
        $settings['building'] = null;
        $settings['room_number'] = null;
        $settings['phone_number'] = null;
        $settings['fax_number'] = null;
        $settings['email'] = null;

        // Physical Address
        $settings['street'] = null;
        $settings['post_box'] = null;
        $settings['city'] = null;
        $settings['state'] = 'NC';
        $settings['zip'] = null;

        // Offsite
        $settings['links'] = null;

        // Map
        $settings['thumbnail_map'] = null;
        $settings['latitude'] = null;
        $settings['longitude'] = null;
        $settings['full_map_link'] = null;

        $settings['zoom'] = 17;
        $settings['dimension_x'] = '300';
        $settings['dimension_y'] = '300';

        return $settings;
    }

}

?>
