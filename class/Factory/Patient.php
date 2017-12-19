<?php

namespace patientexport\Factory;

use systemsinventory\Resource\SystemDevice as Resource;
use systemsinventory\Resource\PC as PCResource;
use systemsinventory\Resource\Camera as CameraResource;
use systemsinventory\Resource\DigitalSign as DigitalSignResource;
use systemsinventory\Resource\IPAD as IPADResource;
use systemsinventory\Resource\Printer as PrinterResource;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Ted Eberhard
 */
class Patient extends \phpws2\ResourceFactory {

    public static function form(\Request $request, $active_tab, $data) {
        include_once(PHPWS_SOURCE_DIR . "mod/systemsinventory/config/device_types.php");
        $vars = array();
        $req_vars = $request->getRequestVars();
        $vars['title'] = '';
        $system_id = NULL;
        $command = $data['command'];
        $action = NULL;
        if (!empty($data['action']))
            $action = $data['action'];

        if ($command == "editProfiles") {
            $message = "Profile Saved!";
            $template_name = 'Edit_Profile.html';
        } else {
            $message = "System Saved!";
            $template_name = 'Add_System.html';
        }

        javascript('jquery');
        \Form::requiredScript();

        if (!in_array($active_tab, array('systems-pc', 'ipad', 'printer', 'camera', 'digital-sign', 'time-clock'))) {
            $active_tab = 'systems-pc';
        }

        $js_string = <<<EOF
	  <script type='text/javascript'>var active_tab = '$active_tab';</script> 
EOF;
        \Layout::addJSHeader($js_string);
        $script = PHPWS_SOURCE_HTTP . 'mod/systemsinventory/javascript/systems.js';
        \Layout::addJSHeader("<script type='text/javascript' src='$script'></script>");


        if ($action == 'success') {
            $vars['message'] = $message;
            $vars['display'] = 'display: block;';
        } else {
            $vars['message'] = '';
            $vars['display'] = 'display: none;';
        }

        $system_locations = SystemDevice::getSystemLocations();
        $location_options = '<option value="1">Select Location</opton>';
        foreach ($system_locations as $key => $val) {
            $location_options .= '<option value="' . $val['id'] . '">' . $val['display_name'] . '</option>';
        }
        $vars['locations'] = $location_options;
        $system_dep = SystemDevice::getSystemDepartments();
        $dep_optons = '<option value="1">Select Department</opton>';
        foreach ($system_dep as $val) {
            $dep_optons .= '<option value="' . $val['id'] . '">' . $val['display_name'] . '</option>';
        }
        $vars['departments'] = $dep_optons;
        $system_profiles = SystemDevice::getSystemProfiles();
        $profile_optons = $printer_profile_options = '<option value="1">Select Profile</opton>';
        if (!empty($system_profiles)) {
            foreach ($system_profiles as $val) {
                if ($val['device_type_id'] == PC) {
                    $profile_optons .= '<option value="' . $val['id'] . '">' . $val['profile_name'] . '</option>';
                } else {
                    $printer_profile_options .= '<option value="' . $val['id'] . '">' . $val['profile_name'] . '</option>';
                }
            }
        }
        $vars['profiles'] = $profile_optons;
        $vars['printer_profiles'] = $printer_profile_options;
        $vars['form_action'] = "./systemsinventory/system/" . $command;
        $template = new \Template($vars);
        $template->setModuleTemplate('systemsinventory', $template_name);
        return $template->get();
    }

    public function postPatient(\Request $request) {
        
    }

    public static function initSystem($vars, $system_id) {
        
    }

    public static function getPatientDetails($patient_id, $row_index) {
        $patient_details = array();
        if (empty($patient_id)) {
            throw new Exception("Patient ID invalid.");
        }
        // get the common device attributes
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_demographics WHERE PR_ACCT_ID='$patient_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetch(\PDO::FETCH_ASSOC);
        $patient_details = $result;
        // get the device specific attributes
        //$purchase_date = $device_details['purchase_date'];
        //$patient_details["purchase_date"] = date('Y-m-d', $purchase_date);
        $patient_details['row_index'] = $row_index;
        return $patient_details;
    }

    public static function exportPatientRecords($patient_id) {
        $vars = array();
        $guarantor_id = "";
        if (empty($patient_id)) {
            throw new Exception("Patient ID invalid.");
        }
        $template_name = 'Demographics.html';
        $patient_demographics = Patient::getDemographics($patient_id);

        if (!empty($patient_demographics)) {
            $patient = $patient_demographics[0];
            $guarantor_id = $patient['GUARANTORID'];
            $vars = Patient::getDemographicsView($vars, $patient);
        } else {
            $vars['error'] = "There was a problem exporting the patient. Please contact your system administrator.";
            $template_name = 'error.html';
            $template = new \phpws2\Template($vars);
            $template->setModuleTemplate('patientexport', $template_name);
            return $template->get();
        }
        // get guarantor info
        $vars['guarantor_relationship'] = $patient['PATRELATIONSHIPTOGUARANTOR'];
        $vars = Patient::getGuarantorView($vars, $guarantor_id);

        // get contact info. As of now can't find where this data lives.        
        if (!empty($patient['EMERGENCYCONTACT1NAME'])) {
            $contact_vars['name'] = $patient['EMERGENCYCONTACT1NAME'];
            $contact_vars['home_phone'] = $patient['EMERGENCYCONTACT1HOMEPHONE'];
            $contact_vars['relationship'] = $patient['EMERGENCYCONTACT1NAME'];
        } else {
            $vars['contacts'] = ' ';
            $vars['contact_empty'] = "No Contacts Available";
        }
        $vars = Patient::getInsuranceView($vars, $patient_demographics);
        $vars['encounters'] = Patient::getEncounterView($patient);
        $vars['images'] = Patient::getScannedImages($patient_id);

        $template = new \phpws2\Template($vars);
        $template->setModuleTemplate('patientexport', $template_name);
        return $template->get();
    }

    public static function getDemographicsView($vars, $patient) {
        $vars['name'] = $patient['FIRSTNAME'] . " " . $patient['MIDDLENAME'] . " " . $patient['LASTNAME'];
        $vars['address1'] = $patient['ADDRESS1'];
        $vars['address2'] = $patient['ADDRESS2'];
        $vars['city_state'] = $patient['CITY'] . ", " . $patient['STATE'] . " " . $patient['ZIP'];
        $vars['acct_number'] = $patient['PR_ACCT_ID'];
        $vars['home_phone'] = $patient['HOMEPHONE'];
        $vars['cell_phone'] = $patient['CELLPHONE'];
        $vars['work_phone'] = $patient['WORKPHONE'];
        $vars['doctor'] = "Alan Keys";
        $vars['referral'] = "";
        $vars['chart_number'] = "";
        $vars['dob'] = $patient['BIRTHDATE'];
        $dob = new \DateTime(trim($patient['BIRTHDATE']));
        $now = new \DateTime();
        $age = $now->diff($dob);
        $age = $age->y;
        $vars['age'] = $age;
        $vars['ssn'] = $patient['SSN'];
        if ($patient['SEX'] == 'F') {
            $vars['gender'] = 'Female';
        } else {
            $vars['gender'] = 'Male';
        }
        $vars['email'] = $patient['EMAIL'];
        return $vars;
    }

    public static function getDemographics($patient_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_demographics WHERE PR_ACCT_ID='$patient_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getGuarantorView($vars, $guarantor_id) {
        $guarantor = Patient::getGuarantors($guarantor_id);
        if (!empty($guarantor)) {
            $vars['guarantor_name'] = $guarantor['GU_FNAME'] . " " . $guarantor['GU_MNAME'] . " " . $guarantor['GU_LNAME'];
            $vars['guarantor_acct_number'] = $guarantor['GU_ID'];
            $vars['guarantor_address1'] = $guarantor['GU_ADDR1'];
            $vars['guarantor_address2'] = $guarantor['GU_ADDR2'];
            $vars['guarantor_city_state'] = $guarantor['GU_CITY_NAME'] . ", " . $guarantor['GU_STATE'] . " " . $guarantor['GU_ZIP_CODE'];
            $vars['guarantor_dob'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $guarantor['GU_BIRTH_DT']);
            $vars['guarantor_ssn'] = $guarantor['GU_SSN'];
            $vars['guarantor_gender'] = $guarantor['GU_SEX'];
            $vars['guarantor_home_phone'] = $guarantor['GU_HOME_PHNO'];
            $vars['guarantor_cell_phone'] = $guarantor['CELL_PHONE'];
            $vars['guarantor_other_phone'] = $guarantor['GU_OTHER_PHNO'];
            $vars['guarantor_email'] = $guarantor['EMAIL_ADDRESS'];
        }
        return $vars;
    }

    public static function getGuarantors($guarantor_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_guarantors WHERE GU_ID='$guarantor_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getInsuranceView($vars, $patient_demographics) {
        $ins_vars = array();
        $ins_content = "";
        foreach ($patient_demographics as $patient) {
            $insurance_id = $patient['INSURANCEID'];
            $insurance = Patient::getInsurancePlan($insurance_id);
            if (!empty($insurance)) {
                $ins_vars['insurance_name'] = $insurance['IP_NAME'];
                $ins_vars['insurance_phone'] = $insurance['IP_PHNO1'];
                $ins_vars['policy_number'] = $patient['PI_POLICY_NUMBER'];
                $ins_vars['holder_name'] = $patient['PI_HOLDER_FNAME'] . " " . $patient['PI_HOLDER_MNAME'] . " " . $patient['PI_HOLDER_LNAME'];
                $ins_vars['rank'] = $patient['PI_RANK'];
                $ins_vars['relationship'] = $patient['PATRELATIONSHIP'];
                $ins_vars['dob'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $patient['POLICYHOLDERBIRTHDT']);
                $template = new \phpws2\Template($ins_vars);
                $template->setModuleTemplate('patientexport', 'Insurance.html');
                $ins_content .= $template->get();
            }
        }
        if (empty($ins_content))
            $vars['insurance_empty'] = 'No Insurance Available.';
        $vars['insurance'] = $ins_content;
        return $vars;
    }

    public static function getInsurancePlan($insurance_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_insurance_plans WHERE IP_ID='$insurance_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getEncounterView($patient) {
        $encounter_vars = array();
        $encounter_content = "";
        $patient_id = $patient['PR_ACCT_ID'];
        $encounters = Patient::getEncounters($patient_id);
        $header_vars['name'] = $patient['LASTNAME'] . ", " . $patient['FIRSTNAME'] . " " . $patient['MIDDLENAME'];
        $header_vars['dob'] = $patient['BIRTHDATE'];
        $dob = new \DateTime(trim($patient['BIRTHDATE']));
        $now = new \DateTime();
        $age = $now->diff($dob);
        $age = $age->y;
        $header_vars['age'] = $age;
        $header_vars['phone'] = $patient['HOMEPHONE'];
        $header_vars['acct_number'] = $patient_id;
        $header_vars['examiner'] = "Alan Keys, MD";

        foreach ($encounters as $encounter) {
            $header_vars['encounter_date'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $encounter['DATE_ENCOUNTER']);
            $template = new \phpws2\Template($header_vars);
            $template->setModuleTemplate('patientexport', 'Encounter_Header.html');
            $encounter_vars['header'] = $template->get();
            $encounter_id = $encounter['CHP_ENCOUNTER_ID'];
            $general_notes = Patient::getGeneralNotes($encounter_id);
            $vitals = Patient::getVitals($encounter_id);
            $problems = Patient::getProblems($encounter_id);
            $medications = Patient::getMedications($encounter_id);
            $allergies = Patient::getAllergies($encounter_id);
            $encounter_vars['complaint'] = $encounter['CHIEF_COMPLAINT'];
            $encounter_vars['general_notes'] = Patient::formatGeneralNotes($general_notes['GENERAL_NOTES_TEXT']);
            $encounter_vars['vitals'] = Patient::getVitalsView($vitals);
            $encounter_vars['problems'] = Patient::getProblemsView($problems);
            $encounter_vars['medications'] = Patient::getMedicationsView($medications);
            $encounter_vars['allergies'] = Patient::getAllergiesView($allergies);
            $template = new \phpws2\Template($encounter_vars);
            $template->setModuleTemplate('patientexport', 'Encounter.html');
            $encounter_content .= $template->get();
        }
        return $encounter_content;
    }

    public static function getEncounters($patient_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_encounters WHERE PATIENT_RSN='$patient_id' ORDER BY CHP_ENCOUNTER_ID ASC";
        $pdo = $db->query($query);
        $result = $pdo->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function formatGeneralNotes($note) {
        $notes = explode("    ", $note);
        $content = "";
        foreach ($notes as $line) {
            $content .= trim($line) . "<br /><br />";
        }
        return $content;
    }

    public static function getGeneralNotes($encounter_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_general_notes WHERE CHP_ENCOUNTER_ID='$encounter_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getVitalsView($vitals) {
        $systolic = $vitals['BP_SYSTOLIC'];
        $diastolic = $vitals['BP_DIASTOLIC'];
        if (!empty($systolic) && !empty($diastolic)) {
            $vars['bp'] = $systolic . "/" . $diastolic;
        } else {
            $vars['bp'] = '';
        }
        $vars['pulse'] = $vitals['HR_PULSE'];
        $vars['temp'] = $vitals['TEMP_TEMPERATURE'];
        $vars['weight'] = $vitals['WT_WEIGHT'];
        $feet = number_format($vitals['HT_HEIGHT'] / 12);
        $inches = $vitals['HT_HEIGHT'] % 12;
        $vars['height'] = $feet . "' " . $inches . '"';
        $vars['bmi'] = $vitals['BMI'];
        $vars['bsa'] = $vitals['BSA'];
        $vars['rr'] = $vitals['RR_RESPIRATION_RATE'];
        $vars['pain'] = $vitals['PA_LEVEL'];
        $vars['o2'] = $vitals['O2_SAT'];
        $vars['fio2'] = "";
        if (empty($vars['bp']) && empty($vars['pulse']) && empty($vars['weight'])) {
            $vitals_content = '';
        } else {
            $template = new \phpws2\Template($vars);
            $template->setModuleTemplate('patientexport', 'Vitals.html');
            $vitals_content = $template->get();
        }
        return $vitals_content;
    }

    public static function getVitals($encounter_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_vitals WHERE CHP_ENCOUNTER_ID='$encounter_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetch(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getProblemsView($problems) {
        $problem_content = '';
        $vars = array();
        foreach ($problems as $problem) {
            $vars['description'] = $problem['DESCRIPTION'];
            $vars['identified_date'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $problem['DATE_RECORDED']);
            $vars['modified_date'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $problem['DT_IDENTIFIED_MOD_DATE']);
            $vars['condition'] = $problem['PROBLEM_STATUS'];
            $vars['resolved_date'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $problem['RESOLVED_DATE_VALUE']);
            if (empty($problem['DESCRIPTION'])) {
                $problem_content = '';
            } else {
                $template = new \phpws2\Template($vars);
                $template->setModuleTemplate('patientexport', 'Problems.html');
                $problem_content .= $template->get();
            }
        }
        if(!empty($problem_content)){
            $template = new \phpws2\Template();
            $template->setModuleTemplate('patientexport', 'Problems_Header.html');
            $problems_header = $template->get();
            $problem_content = $problems_header.$problem_content.'<hr style="border-top: 1px solid #0e0e0e !important;"/>';
        }
        return $problem_content;
    }

    public static function getProblems($encounter_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_problems WHERE CHP_ENCOUNTER_ID='$encounter_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getMedicationsView($medications){
        $medications_content = '';
        $vars = array();
        foreach ($medications as $medication) {
            if($medication['ONSET_UNKNOWN'] == '1'){
                $vars['prescribed'] = 'Unknown';
            }else{
                $vars['prescribed'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $medication['DATE_ORDERED']);
            }
            $vars['medication'] = $medication['MEDICATION'];
            $vars['dispense'] = $medication['DISPENSE'];
            $vars['sig'] = $medication['SIG'];
            if(empty($medication['NO_OF_REFILLS'])){
                $vars['refills'] = '0';
            }else{
                $vars['refills'] = $medication['NO_OF_REFILLS'];
            }
            $vars['disc'] = $medication['DISC_RSN'];
            if (empty($medication['MEDICATION'])) {
                $medications_content = '';
            } else {
                $template = new \phpws2\Template($vars);
                $template->setModuleTemplate('patientexport', 'Medications.html');
                $medications_content .= $template->get();
            }
        }
        if(!empty($medications_content)){
            $template = new \phpws2\Template();
            $template->setModuleTemplate('patientexport', 'Medication_Header.html');
            $medications_header = $template->get();
            $medications_content = $medications_header.$medications_content.'<hr style="border-top: 1px solid #0e0e0e !important;"/>';
        }
        return $medications_content;
    }
    
    public static function getMedications($encounter_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_medications WHERE CHP_ENCOUNTER_ID='$encounter_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getAllergiesView($allergies){
        $allergies_content = '';
        $vars = array();
        foreach ($allergies as $allergy) {
            if(empty($allergy['DATE_OCCURRENCE'])){
                $vars['identified_date'] = 'Unknown';
            }else{
                $vars['identified_date'] = preg_replace("([0-9][0-9]\:[0-9][0-9]\:[0-9][0-9])", "", $allergy['DATE_OCCURRENCE']);
            }
            $vars['description'] = $allergy['DESCRIPTION'];
            $vars['allergic_reactions'] = "Other";
            $vars['adverse_reactions'] = "Other";
            if (empty($allergy['DESCRIPTION'])) {
                $allergies_content = '';
            } else {
                $template = new \phpws2\Template($vars);
                $template->setModuleTemplate('patientexport', 'Allergies.html');
                $allergies_content .= $template->get();
            }
        }
        if(!empty($allergies_content)){
            $template = new \phpws2\Template();
            $template->setModuleTemplate('patientexport', 'Allergies_Header.html');
            $allergies_header = $template->get();
            $allergies_content = $allergies_header.$allergies_content.'<hr style="border-top: 1px solid #0e0e0e !important;"/>';
        }
        return $allergies_content;
    }
    
    public static function getAllergies($encounter_id) {
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_allergies WHERE CHP_ENCOUNTER_ID='$encounter_id'";
        $pdo = $db->query($query);
        $result = $pdo->fetchAll(\PDO::FETCH_ASSOC);
        return $result;
    }

    public static function getScannedImages($patient_id) {
        include_once(PHPWS_SOURCE_DIR . "mod/patientexport/config/defines.php");
        $image_content = '';
        $db = \phpws2\Database::getDB();
        $query = "SELECT * FROM patient_chp_attachments_master WHERE PATIENT_RSN='$patient_id' AND ATTACH_TYPE != ''";
        $pdo = $db->query($query);
        $master_result = $pdo->fetchAll(\PDO::FETCH_ASSOC);
        foreach($master_result as $master){
            $chp_attachment_id = $master['CHP_ATTACHMENTS_ID'];
            $query = "SELECT * FROM patient_chp_attachments_image WHERE CHP_ATTACHMENTS_ID='$chp_attachment_id' ORDER BY DISPLAY_ORDER ASC";
            $pdo = $db->query($query);
            $image_result = $pdo->fetchAll(\PDO::FETCH_ASSOC);
            foreach($image_result as $image){
                $id = $image['CHP_ATTACHMENTS_IMAGE_ID'];
                $src = PHPWS_SOURCE_HTTP . SCAN_FOLDER . IMAGE_NAME . $id . ".blob.jpg";
                $image_content .= "<div><img class='img-fluid' src='$src'></img></div>";
            }
        }
        return $image_content;
    }

    public static function searchPhysicalID($physical_id) {
        $db = \Database::getDB();
        $system_table = $db->addTable("systems_device");
        $system_table->addFieldConditional('physical_id', $physical_id);
        $search_result = $db->select();
        $result = array('exists' => false);
        if ($search_result)
            $search_result = $search_result['0'];
        if (!empty($search_result['id']))
            $result['exists'] = true;
        return $result;
    }

    public static function getDeviceAttributes($type_id) {
        $systems_pc = array("patient_id" => NULL, "os" => "OS", "primary_monitor" => "Primary Monitor", "secondary_monitor" => "Secondary Monitor", "video_card" => "Video Card", "server_type" => NULL, "battery_backup" => NULL, "redundant_backup" => NULL, "touch_screen" => "Touch Screen", "smart_room" => "Smart Room", "dual_monitor" => "Dual Monitor", "system_usage" => NULL, "rotation" => "Rotation", "stand" => "Stand", "check_in" => "Check In");
        $systems_server = array("patient_id" => NULL, "os" => "OS", "primary_monitor" => "Primary Monitor", "secondary_monitor" => "Secondary Monitor", "video_card" => "Video Card", "server_type" => NULL, "battery_backup" => NULL, "redundant_backup" => NULL, "touch_screen" => "Touch Screen", "smart_room" => "Smart Room", "dual_monitor" => "Dual Monitor", "system_usage" => NULL, "rotation" => "Rotation", "stand" => "Stand", "check_in" => "Check In");
        $systems_ipad = array("patient_id" => NULL, "generation" => "Generation", "apple_id" => "Apple ID");
        $systems_printer = array("patient_id" => NULL, "toner_cartridge" => "Toner Cartridge", "color" => "Color", "network" => "Network", "duplex" => "Duplex");

        switch ($type_id) {
            case '1':
                $attr = $systems_pc;
                break;
            case '2':
                $attr = $systems_server;
                break;
            case '3':
                $attr = $systems_ipad;
                break;
            case '4':
                $attr = $systems_printer;
                break;
            case '5':
                $attr = $systems_camera;
                break;
            case '6':
                $attr = $digital_sign;
                break;
            case '7':
                $attr = $systems_timeclock;
                break;
            default:
                $attr = $systems_pc;
        }
        return $attr;
    }

    public static function getSystemType($type_id) {
        switch ($type_id) {
            case '1':
            case '2':
                $table = 'systems_pc';
                break;
            case '3':
                $table = 'systems_ipad';
                break;
            case '4':
                $table = 'systems_printer';
                break;
            case '5':
                $table = 'systems_camera';
                break;
            case '6':
                $table = 'systems_digital_sign';
                break;
            case '7':
                $table = NULL;
                break;
            default:
                $table = 'systems_pc';
        }
        return $table;
    }

    public static function getLocationByID($location_id) {
        $db = \Database::getDB();
        $tbl = $db->addTable('systems_location');
        $tbl->addField('description');
        $tbl->addFieldConditional('id', $location_id, '=');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result[0]['description'];
    }

    public static function getDepartmentByID($department_id) {
        $db = \Database::getDB();
        $tbl = $db->addTable('systems_department');
        $tbl->addField('description');
        $tbl->addFieldConditional('id', $department_id, '=');
        $result = $db->select();
        if (empty($result))
            return 'Not Found'; //should be exception

        return $result[0]['description'];
    }

    public static function getSystemLocations() {
        $db = \Database::getDB();
        $tbl = $db->addTable('systems_location');
        $tbl->addField('id');
        $tbl->addField('display_name');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result;
    }

    public static function getSystemTypes() {
        $db = \Database::getDB();
        $tbl = $db->addTable('systems_device_type');
        $tbl->addField('id');
        $tbl->addField('description');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result;
    }

    public static function getSystemDepartments() {
        $user_id = \Current_User::getId();
        $permission_db = \Database::getDB();
        $permissions_tbl = $permission_db->addTable('systems_permission');
        $permissions_tbl->addField('departments');
        $permissions_tbl->addField('user_id');
        $permissions_tbl->addFieldConditional('user_id', $user_id);
        $permission_result = $permission_db->select();
        $db = \Database::getDB();
        $tbl = $db->addTable('systems_department');
        $tbl->addField('id');
        $tbl->addField('display_name');
        $tbl->addFieldConditional('active', '1');
        $tbl->addFieldConditional('id', '1', '!=');
        $tbl->addOrderBy('display_name');
        if (!empty($permission_result)) {
            $dep = $permission_result[0]['departments'];
            $deps = explode(':', $dep);
            $cond = NULL;
            foreach ($deps as $val) {
                $tmp_cond = new \Database\Conditional($db, 'id', $val, '=');
                if (empty($cond))
                    $cond = $tmp_cond;
                else
                    $cond = new \Database\Conditional($db, $cond, $tmp_cond, 'OR');
            }
            $db->addConditional($cond);
        }
        $result = $db->select();

        if (empty($result))
            return 0; //should be exception
        return $result;
    }

    public static function getSystemProfiles() {
        $db = \Database::getDB();
        $tbl = $db->addTable('systems_device');
        $tbl->addFieldConditional('profile', 1);
        $tbl->addField('id');
        $tbl->addField('profile_name');
        $tbl->addField('device_type_id');
        $result = $db->select();
        if (empty($result))
            return 0; //should be exception
        return $result;
    }

}
