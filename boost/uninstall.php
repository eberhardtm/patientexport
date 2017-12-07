<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

 $db = \phpws2\Database::getDB();
 $db->buildTable('patient_demographics')->drop(true);
 $db->buildTable('patient_encounters')->drop(true);
 $db->buildTable('patient_general_notes')->drop(true);
 $db->buildTable('patient_vitals')->drop(true);
 $db->buildTable('patient_problems')->drop(true);
 $db->buildTable('patient_medications')->drop(true);
 $db->buildTable('patient_allergies')->drop(true);
 return true;
