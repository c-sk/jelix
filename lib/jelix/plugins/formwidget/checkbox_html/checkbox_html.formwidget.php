<?php
/**
* @package     jelix
* @subpackage  formwidgets
* @author      Claudio Bernardes
* @contributor Laurent Jouanneau, Julien Issler, Dominique Papin
* @copyright   2012 Claudio Bernardes
* @copyright   2006-2012 Laurent Jouanneau, 2008-2011 Julien Issler, 2008 Dominique Papin
* @link        http://www.jelix.org
* @licence     http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public Licence, see LICENCE file
*/

/**
 * HTML form builder
 * @package     jelix
 * @subpackage  jelix-plugins
 * @link http://developer.jelix.org/wiki/rfc/jforms-controls-plugins
 */

class checkbox_htmlFormWidget extends jFormsHtmlWidgetBuilder {
    function outputJs() {
        $js = "c = new ".$this->builder->getjFormsJsVarName()."ControlBoolean('".$this->ctrl->ref."', ".$this->escJsStr($this->ctrl->label).");\n";
        $this->builder->jsContent .= $js;
        $this->commonJs($this->ctrl);
    }

    function outputControl() {
        $attr = $this->getControlAttributes();

        if($this->ctrl->valueOnCheck == $this->getValue($this->ctrl)){
            $attr['checked'] = "checked";
        }
        $attr['value'] = $this->ctrl->valueOnCheck;
        $attr['type'] = 'checkbox';
        echo '<input';
        $this->_outputAttr($attr);
        echo '/>';
    }
}
