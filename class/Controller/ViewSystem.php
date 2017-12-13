<?php

namespace patientexport\Controller;

/**
 * @license http://opensource.org/licenses/lgpl-3.0.html
 * @author Matthew McNaney <mcnaney at gmail dot com>
 */
class ViewSystem extends \Canopy\Http\Controller
{

    public function get(\Request $request)
    {
        $data = array();
        $view = $this->getView($data, $request);
        $response = new \Response($view);
        return $response;
    }

    protected function getHtmlView($data, \Request $request)
    {
        $content = \patientexport\Factory\ContactInfo::form($request, 'view_system');
        $view = new \View\HtmlView(\PHPWS_ControlPanel::display($content));
        return $view;
    }

}
