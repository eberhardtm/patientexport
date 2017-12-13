<?php

namespace patientexport\Factory;

use patientexport\Resource\SystemDevice as Resource;
/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Ted Eberhard
 */
class Search extends \phpws2\ResourceFactory
{

  public static function form(\Canopy\Request $request, $command=null)
    {
        javascript('jquery');
        \phpws2\Form::requiredScript();     

	if(empty($command))
	  $command = 'run_search';
	$vars['form_action'] = "./patientexport/search/".$command;
        $template = new \phpws2\Template($vars);
        $template->setModuleTemplate('patientexport', 'Search_Patient.html');
        return $template->get();
    }
  
}