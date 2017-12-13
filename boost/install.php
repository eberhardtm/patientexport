<?php

function patientexport_install(&$content)
{
    include_once(PHPWS_SOURCE_DIR . "mod/patientexport/config/defines.php");
    
    $db = \phpws2\Database::getDB();
    $db->begin();
    try {
        /**
        createPatientTable($db, PATIENT_DEMOGRAPHICS, 'patient_demographics');
        importPatientData($db, PATIENT_DEMOGRAPHICS, 'patient_demographics');
        createPatientTable($db, PATIENT_ENCOUNTERS, 'patient_encounters');
        importPatientData($db, PATIENT_ENCOUNTERS, 'patient_encounters');
        createPatientTable($db, PATIENT_GENERAL_NOTES, 'patient_general_notes');
        importPatientData($db, PATIENT_GENERAL_NOTES, 'patient_general_notes');
        createPatientTable($db, PATIENT_PROBLEMS, 'patient_problems');
        importPatientData($db, PATIENT_PROBLEMS, 'patient_problems');
        createPatientTable($db, PATIENT_MEDICATIONS, 'patient_medications');
        importPatientData($db, PATIENT_MEDICATIONS, 'patient_medications');
        createPatientTable($db, PATIENT_ALLERGIES, 'patient_allergies');
        importPatientData($db, PATIENT_ALLERGIES, 'patient_allergies');
        createPatientTable($db, PATIENT_VITALS, 'patient_vitals');
        importPatientData($db, PATIENT_VITALS, 'patient_vitals');
        createPatientTable($db, PATIENT_GUARANTORS, 'patient_guarantors');
        importPatientData($db, PATIENT_GUARANTORS, 'patient_guarantors');
        createPatientTable($db, PATIENT_INSURANCE_PLANS, 'patient_insurance_plans');
        importPatientData($db, PATIENT_INSURANCE_PLANS, 'patient_insurance_plans');
         * 
         */
    } catch (\Exception $e) {
        \phpws2\Error::log($e);
        $db->rollback();
        throw $e;
    }
    $db->commit();
    $content[] = 'Tables created';
    return true;
}

function createPatientTable($db, $filename, $db_name)
{
    $columns = array();
    $tagToEntry = $db->buildTable($db_name);
    $filehandle = fopen(PHPWS_SOURCE_DIR . "mod/patientexport/boost/import-files/$filename", 'r');
    $fileline = fgets($filehandle);
    $lines = explode('|', $fileline);
    $count = 0;
    foreach($lines as $line){
        $line = rtrim($line);
        $count++;
        if(!empty($line)){
            if($line === 'GENERAL_NOTES_TEXT'){
                $tagToEntry->addDataType($line, 'text');
            }else{
                if($db_name === 'patient_medications' || $db_name === 'patient_vitals' || $db_name === 'patient_insurance_plans'){
                    $tagToEntry->addDataType($line, 'text');
                }else{
                    $tagToEntry->addDataType($line, 'varchar')->setSize('255');
                }
            }
        }
    }
    $tagToEntry->create();
    fclose($filehandle);
}

function importPatientData($db, $filename, $db_name)
{
    $file_contents = file(PHPWS_SOURCE_DIR . "mod/patientexport/boost/import-files/$filename");
    $mysqli_connection = $db->getPDO();
    $columns = explode("|", $file_contents[0]);
    $insert_columns = NULL;
    foreach($columns as $column){
        $column = trim($column);
        if($column === "SHOW" || $column === "STATUS"){
            $column .= "_MED";
        }
        if(empty($insert_columns)){
            $insert_columns = $column;
        }else{
            $insert_columns .= ",".$column;
        }
    }
    unset($file_contents[0]);
    foreach($file_contents as $row){
        $line = explode("|", $row);
        $data = NULL;
        foreach($line as $item){
            $item = $mysqli_connection->quote(trim($item));
            if(empty($data)){
                $data = $item;   
            }else{
                $data .= ",$item";             
            }
        }
        $query = "INSERT INTO $db_name ($insert_columns) VALUES($data)";
        $db->query($query);
    }
}