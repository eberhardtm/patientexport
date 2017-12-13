<?php

namespace patientexport\Controller;

use patientexport\Factory\PC as PCFactory;
use patientexport\Factory\IPAD as IPADFactory;
use patientexport\Factory\Printer as PrinterFactory;
use patientexport\Factory\Camera as CameraFactory;
use patientexport\Factory\DigitalSign as DigitalSignFactory;
use patientexport\Factory\Patient as PatientFactory;
use patientexport\Resource;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Ted Eberhard <eberhardtm at appstate dot edu>
 */
class Patient extends \phpws2\Http\Controller {

    public function get(\Canopy\Request $request) {
        $data = array();
        $data['command'] = $request->shiftCommand();
        $view = $this->getView($data, $request);
        $response = new \Canopy\Response($view);
        return $response;
    }

    protected function getHtmlView($data, \Canopy\Request $request) {
        if (empty($data['command']))
            $data['command'] = 'export';
        $req_vars = $request->getRequestVars();
        $patient_id = $req_vars['patient_id'];
        if($data['command'] == 'export'){
            $content = PatientFactory::exportPatientRecords($patient_id);
        }
        $view = new \phpws2\View\HtmlView($content);
        return $view;
    }

    public function post(\Canopy\Request $request) {
        $sdfactory = new PatientFactory;
        $vars = $request->getRequestVars();
        $isJSON = false;
        $data['command'] = $request->shiftCommand();

        if (!empty($vars['patient_id']))
            $isJSON = true;

        if ($isJSON) {
            $view = new \phpws2\View\JsonView(array('success' => TRUE));
        } else {
            $view = $this->getHtmlView($data, $request);
        }
        $response = new \Canopy\Response($view);
        return $response;
    }

    public function postSpecificDevice(\Canopy\Request $request, $device_type, $patient_id) {
        include_once(PHPWS_SOURCE_DIR . "mod/patientexport/config/device_types.php");

        switch ($device_type) {
            case SERVER:
            case PC:
                $pcfactory = new PCFactory;
                $pcfactory->postNewPC($request, $patient_id);
                break;
            case IPAD:
                $ipadfactory = new IPADFactory;
                $ipadfactory->postNewIPAD($request, $patient_id);
                break;
            case PRINTER:
                $printerfactory = new PrinterFactory;
                $printerfactory->postNewPrinter($request, $patient_id);
                break;
            case CAMERA:
                $camerafactory = new CameraFactory;
                $camerafactory->postNewCamera($request, $patient_id);
                break;
            case DIGITAL_SIGN:
                $digitalsignfactory = new DigitalSignFactory;
                $digitalsignfactory->postNewDigitalSign($request, $patient_id);
                break;
            case TIME_CLOCK:
                break;
            default:
                break;
        }
    }

    public static function loadAdminBar() {
        $auth = \Current_User::getAuthorization();

        $nav_vars['is_deity'] = \Current_user::isDeity();
        $nav_vars['logout_uri'] = $auth->logout_link;
        $nav_vars['username'] = \Current_User::getDisplayName();
        if (\Current_User::allow('patientexport', 'view'))
            $nav_vars['search'] = '<a href="patientexport/search"><i class="fa fa-search"></i> Search Patients</a>';
        if (\Current_User::allow('patientexport', 'settings'))
            $nav_vars['settings'] = '<a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"><i class="fa fa-cog"></i> Settings</a>';


        $nav_bar = new \phpws2\Template($nav_vars);
        $nav_bar->setModuleTemplate('patientexport', 'navbar.html');
        $content = $nav_bar->get();
        \Layout::plug($content, 'NAV_LINKS');
    }

    protected function getJsonView($data, \Canopy\Request $request) {
        $vars = $request->getRequestVars();
        $command = '';
        if (!empty($data['command']))
            $command = $data['command'];

        if ($command == 'getDetails' && \Current_User::allow('patientexport', 'view')) {
            $result = PatientFactory::getPatientDetails($vars['patient_id'], $vars['row_index']);
        } else if (\Current_User::allow('patientexport', 'edit')) {
            $system_details = '';
            switch ($command) {
                case 'export':
                    $result = PatientFactory::exportPatientRecords($vars['patient_id']);
                    break;
                case 'getUser':
                    $result = PatientFactory::getUserByUsername($vars['username']);
                    break;
                case 'getProfile':
                    $result = PatientFactory::getProfile($vars['profile_id']);
                    break;
                case 'searchPhysicalID':
                    $result = PatientFactory::searchPhysicalID($vars['physical_id']);
                    break;
                default:
                    throw new Exception("Invalid command received in system controller getJsonView. Command = $command");
            }
        } else {
            $result = array('Error');
        }
        
        $view = new \phpws2\View\JsonView($result);
        return $view;
    }

}
