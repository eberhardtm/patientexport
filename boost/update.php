<?php

function runDbMigration($fileName)
{
    $db = new PHPWS_DB();
    $result = $db->importFile(PHPWS_SOURCE_DIR . 'mod/patientexport/boost/updates/' . $fileName);
    if (PEAR::isError($result)) {
        throw new \Exception($result->toString());
    }
}
function systemsinventory_update($content, $currentVersion){
    switch($currentVersion){
        case version_compare($currentVersion,'1.0.0','<'):
            runDbMigration('update_1_0_1.sql');
    }
   
    return TRUE;
}