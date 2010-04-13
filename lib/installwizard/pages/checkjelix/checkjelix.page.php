<?php

/**
* page for Installation wizard
*
* @package     InstallWizard
* @subpackage  pages
* @author      Laurent Jouanneau
* @copyright   2010 Laurent Jouanneau
* @link        http://jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/

class checkjelixWizPage extends installWizardPage {
    
    /**
     * action to display the page
     * @param jTpl $tpl the template container
     */
    function show ($tpl) {
    }
    
    /**
     * action to process the page after the submit
     */
    function process() {
        if ( isset($_POST['confirm']) && $_POST['confirm'] == 'ok')
            return 0;
        $this->errors ['error1'] = 'you should check';
        return false;
    }

}