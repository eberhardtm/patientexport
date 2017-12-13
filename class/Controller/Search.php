<?php

namespace patientexport\Controller;

use patientexport\Factory\Search as Factory;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Ted Eberhard <eberhardtm at appstate dot edu>
 */
class Search extends \phpws2\Http\Controller {

    public $search_params = NULL;

    public function get(\Canopy\Request $request) {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Canopy\Response($view);
        return $response;
    }

    protected function getHtmlView($data, \Canopy\Request $request) {
        $content = Factory::form($request);
        $view = new \phpws2\View\HtmlView($content);
        return $view;
    }

    protected function getJsonView($data, \Canopy\Request $request) {
        $db = \phpws2\Database::newDB();
        $sd = $db->addTable('patient_demographics');
        $conditional = $this->createSearchConditional($db);
        if (!empty($conditional))
            $db->addConditional($conditional);
        $result = $db->select();
        $dbpager = new \phpws2\DatabasePager($db);
        $dbpager->setHeaders(array('PR_ACCT_ID' => 'Patient ID', 'LASTNAME' => 'Last Name', 'FIRSTNAME' => 'First Name','BIRTHDATE' => 'DOB'));
        $tbl_headers['PR_ACCT_ID'] = $sd->getField('PR_ACCT_ID');
        $tbl_headers['LASTNAME'] = $sd->getField('LASTNAME');
        $tbl_headers['FIRSTNAME'] = $sd->getField('FIRSTNAME');
        $tbl_headers['BIRTHDATE'] = $sd->getField('BIRTHDATE');
        $dbpager->setTableHeaders($tbl_headers);
        $dbpager->setId('patient-list');
        $dbpager->setRowIdColumn('PR_ACCT_ID');
       // $dbpager->setCallback(array('\patientexport\Controller\Search', 'alterSearchRow'));
        $data = $dbpager->getJson();
        return parent::getJsonView($data, $request);
    }

    private function createSearchConditional($db) {
        $conditional = NULL;
        if (empty($_SESSION['patient_search_vars'])) {
            $conditional = NULL;
        } else {
            $search_vars = $_SESSION['patient_search_vars'];
            if (!empty($search_vars['patient_id'])) {
                $tmp_cond = new \phpws2\Database\Conditional($db, 'PR_ACCT_ID', $search_vars['patient_id'], '=');
                $conditional = $this->addSearchConditional($db, $conditional, $tmp_cond, 'AND');                                
            }
            if (!empty($search_vars['first_name'])) {
                $tmp_cond = new \phpws2\Database\Conditional($db, 'FIRSTNAME', "%".$search_vars['first_name']."%", 'like');
                $conditional = $this->addSearchConditional($db, $conditional, $tmp_cond, 'AND');                                
            }
            if (!empty($search_vars['last_name'])) {
                $tmp_cond = new \phpws2\Database\Conditional($db, 'LASTNAME', "%" . $search_vars['last_name'] . "%", 'like');
                $conditional = $this->addSearchConditional($db, $conditional, $tmp_cond, 'AND');                                
            }
        }
        return $conditional;
    }
    
    private function addSearchConditional($db, $conditional, $tmp_cond, $operator){
        if (empty($conditional)){
            $conditional = $tmp_cond;
        }else{
            $conditional = new \phpws2\Database\Conditional($db, $conditional, $tmp_cond, $operator);    
        }
        return $conditional;
    }
    
    /**
     * Format the search row by translating id's to names and formatting the date to human readable
     * 
     * @param array $row
     * @return array
     */
    public static function alterSearchRow($row) {
        $row['department_id'] = \patientexport\Factory\SystemDevice::getDepartmentByID($row['department_id']);
        $row['location_id'] = \patientexport\Factory\SystemDevice::getLocationByID($row['location_id']);
        $row['purchase_date'] = date('n/d/Y', $row['purchase_date']);
        return $row;
    }

    /**
     * Handle the submit from the search form.
     * 
     * @param \Request $request
     * @return \Response
     */
    public function post(\Canopy\Request $request) {
        $script = PHPWS_SOURCE_HTTP . 'mod/patientexport/javascript/sys_pager.js';
        $source_http = PHPWS_SOURCE_HTTP;
        \Layout::addJSHeader("<script type='text/javascript'>var source_http = '$source_http';</script>");
        \Layout::addLink("<script type='text/javascript' src='$script'></script>");

        $factory = new Factory;
        $search_vars = $request->getVars();
        if(empty($_SESSION['patient_search_vars']) || !isset($search_vars['vars']['search_back'])){
            $_SESSION['patient_search_vars'] = $search_vars['vars'];
        }
        \phpws2\Pager::prepare();
        $template = new \phpws2\Template;
        $template->setModuleTemplate('patientexport', 'search_results.html');

        $view = new \phpws2\View\HtmlView($template->get());
        $response = new \Canopy\Response($view);
        return $response;
    }

}
