<?php

/**
* @package     jelix-scripts
* @author      Laurent Jouanneau
* @contributor Loic Mathaud
* @contributor Gildas Givaja (bug #83)
* @contributor Christophe Thiriot
* @contributor Bastien Jaillot
* @contributor Dominique Papin, Olivier Demah
* @copyright   2005-2011 Laurent Jouanneau, 2006 Loic Mathaud, 2007 Gildas Givaja, 2007 Christophe Thiriot, 2008 Bastien Jaillot, 2008 Dominique Papin
* @copyright   2011 Olivier Demah
* @link        http://www.jelix.org
* @licence     GNU General Public Licence see LICENCE file or http://www.gnu.org/licenses/gpl.html
*/

use Jelix\Core\App as App;

class createappCommand extends JelixScriptCommand {

   protected $commonOptions = array('-v'=>false);

    public  $name = 'createapp';
    public  $allowed_options=array('-nodefaultmodule'=>false,
                                   '-withcmdline'=>false,
                                   '-modulename' =>true,
                                   '-wwwpath'=>true);
    public  $allowed_parameters=array('path'=>true);

    public  $syntaxhelp = "[-nodefaultmodule] [-withcmdline] [-modulename a_name] [-wwwpath a_path]";
    public  $help='';
    public $commonSyntaxOptions = '[-v] ';
    public $commonOptionsHelp = array(
        'en'=>"
    Other options:
    -v: verbose mode
",
        'fr'=>"
    Autres options:
    -v: mode verbeux. Affiche plus d'informations.
"
    );

    public $applicationRequirement = 1;

    function __construct(){
        $this->help= array(
            'fr'=>"
    Crée une nouvelle application avec tous les répertoires nécessaires et un module
    du même nom que l'application. Le nom du module peut-être changé avec l'option -modulename.

    Si l'option -nodefaultmodule est présente, le module n'est pas créé.

    Si l'option -withcmdline est présente, crée un point d'entrée afin de
    développer des scripts en ligne de commande.

    Si l'option -wwwpath est présente, sa valeur définit le document root de votre application.
    wwwpath doit être relatif au répertoire de l'application (valeur par défaut www/).

    le répertoire de la future application doit être indiquée en paramètre.
    ",
            'en'=>"
    Create a new application with all directories and one module named as your application.
    The module name can be changed with -modulename.

    If you give -nodefaultmodule option, it won't create the module.

    If you give the -withcmdline option, it will create an entry point dedicated to
    command line scripts.

    If you give the -wwwpath option, it will replace your application default document root.
    wwwpath must be relative to your application directory (default value is 'www/').

    The application directory should be  indicated as parameter
    ",
    );
    }

    public function run() {
        $appPath = $this->getParam('path');
        $appPath = $this->getRealPath($appPath);
        $appName = basename($appPath);
        $appPath .= '/';

        if (file_exists($appPath.'/jelix-app.json') || file_exists($appPath.'/project.xml')) {
            throw new Exception("this application is already created");
        }

        $this->config = JelixScript::loadConfig($appName);
        $this->config->infoWebsite = $this->config->newAppInfoWebsite;
        $this->config->infoLicence = $this->config->newAppInfoLicence;
        $this->config->infoLicenceUrl = $this->config->newAppInfoLicenceUrl;
        $this->config->infoLocale = $this->config->newAppInfoLocale;
        $this->config->infoCopyright = $this->config->newAppInfoCopyright;
        $this->config->initAppPaths($appPath);

        App::setEnv('jelix-scripts');

        JelixScript::checkTempPath();

        if ($p = $this->getOption('-wwwpath')) {
            $wwwpath = path::real($appPath.$p, false).'/';
        }
        else {
            $wwwpath = App::wwwPath();
        }

        $this->createDir($appPath);
        $this->createDir(App::tempBasePath());
        $this->createDir($wwwpath);

        $varPath = App::varPath();
        $configPath = App::configPath();
        $this->createDir($varPath);
        $this->createDir(App::logPath());
        $this->createDir($configPath);
        $this->createDir($configPath.'index/');
        $this->createDir($varPath.'overloads/');
        $this->createDir($varPath.'themes/');
        $this->createDir($varPath.'themes/default/');
        $this->createDir($varPath.'uploads/');
        $this->createDir($varPath.'sessions/');
        $this->createDir($varPath.'mails/');
        $this->createDir($appPath.'install');
        $this->createDir($appPath.'modules');
        $this->createDir($appPath.'plugins');
        $this->createDir($appPath.'responses');
        $this->createDir($appPath.'tests');
        $this->createDir(App::scriptsPath());

        $param = array();

        if($this->getOption('-nodefaultmodule')) {
            $param['tplname']    = 'jelix~defaultmain';
            $param['modulename'] = 'jelix';
        }
        else {
            $moduleName = $this->getOption('-modulename');
            if (!$moduleName) {
                // note: since module name are used for name of generated name,
                // only this characters are allowed
                $moduleName = preg_replace('/([^a-zA-Z_0-9])/','_',$appName);
            }
            $param['modulename'] = $moduleName;
            $param['tplname']    = $moduleName.'~main';
        }

        $param['config_file'] = 'index/config.ini.php';

        $param['rp_temp']  = $this->getRelativePath($appPath, App::tempBasePath());
        $param['rp_var']   = $this->getRelativePath($appPath, App::varPath());
        $param['rp_log']   = $this->getRelativePath($appPath, App::logPath());
        $param['rp_conf']  = $this->getRelativePath($appPath, $configPath);
        $param['rp_www']   = $this->getRelativePath($appPath, $wwwpath);
        $param['rp_cmd']   = $this->getRelativePath($appPath, App::scriptsPath());
        $param['rp_jelix'] = $this->getRelativePath($appPath, JELIX_LIB_PATH);
        $param['rp_vendor'] = '';
        foreach (array(LIB_PATH. 'vendor/',   // jelix is installed from a zip/tgz package
                        LIB_PATH . '../vendor/', // jelix is installed from git
                        LIB_PATH. '../../../' // jelix is installed with Composer
                        ) as $path) {
           if (file_exists($path)) {
              $param['rp_vendor'] = $this->getRelativePath($appPath, realpath($path).'/');
              break;
           }
        }

        $param['rp_app']   = $this->getRelativePath($wwwpath, $appPath);

        $this->createFile(App::logPath().'.dummy', 'dummy.tpl', array());
        $this->createFile(App::varPath().'mails/.dummy', 'dummy.tpl', array());
        $this->createFile(App::varPath().'sessions/.dummy', 'dummy.tpl', array());
        $this->createFile(App::varPath().'overloads/.dummy', 'dummy.tpl', array());
        $this->createFile(App::varPath().'themes/default/.dummy', 'dummy.tpl', array());
        $this->createFile(App::varPath().'uploads/.dummy', 'dummy.tpl', array());
        $this->createFile($appPath.'plugins/.dummy', 'dummy.tpl', array());
        $this->createFile(App::scriptsPath().'.dummy', 'dummy.tpl', array());
        $this->createFile(App::tempBasePath().'.dummy', 'dummy.tpl', array());

        $this->createFile($appPath.'.htaccess', 'htaccess_deny', $param, "Configuration file for Apache");
        $this->createFile($appPath.'.gitignore','git_ignore.tpl', $param, ".gitignore");

        $this->createFile($appPath.'jelix-app.json','jelix-app.json.tpl', $param, "Project description file");
        $this->createFile($appPath.'composer.json','composer.json.tpl', $param, "Composer file");
        $this->createFile($appPath.'cmd.php','cmd.php.tpl', $param, "Script for developer commands");
        $this->createFile($configPath.'mainconfig.ini.php', 'var/config/mainconfig.ini.php.tpl', $param, "Main configuration file");
        $this->createFile($configPath.'localconfig.ini.php.dist', 'var/config/localconfig.ini.php.tpl', $param, "Configuration file for specific environment");
        $this->createFile($configPath.'profiles.ini.php', 'var/config/profiles.ini.php.tpl', $param, "Profiles file");
        $this->createFile($configPath.'profiles.ini.php.dist', 'var/config/profiles.ini.php.tpl', $param, "Profiles file for your repository");
        $this->createFile($configPath.'preferences.ini.php', 'var/config/preferences.ini.php.tpl', $param, "Preferences file");
        $this->createFile($configPath.'urls.xml', 'var/config/urls.xml.tpl', $param, "URLs mapping file");

        $this->createFile($configPath.'index/config.ini.php', 'var/config/index/config.ini.php.tpl', $param, "Entry point configuration file");
        $this->createFile($appPath.'responses/myHtmlResponse.class.php', 'responses/myHtmlResponse.class.php.tpl', $param, "Main response class");
        $this->createFile($appPath.'install/installer.php','installer/installer.php.tpl',$param, "Installer script");
        $this->createFile($appPath.'tests/runtests.php','tests/runtests.php', $param, "Tests script");

        $temp = dirname(rtrim(App::tempBasePath(),'/'));
        if ($temp != rtrim($appPath,'/')) {
            if (file_exists($temp.'/.gitignore')) {
                $gitignore = file_get_contents($temp.'/.gitignore'). "\n" .$appName."/*\n";
                file_put_contents($temp.'/.gitignore', $gitignore);
            }
            else {
                file_put_contents($temp.'/.gitignore', $appName."/*\n");
            }
        }
        else {
            $gitignore = file_get_contents($appPath.'.gitignore'). "\n".basename(rtrim(App::tempBasePath(),'/'))."/*\n";
            file_put_contents($appPath.'.gitignore', $gitignore);
        }

        $this->createFile($wwwpath.'index.php', 'www/index.php.tpl',$param, "Main entry point");
        $this->createFile($wwwpath.'.htaccess', 'htaccess_allow',$param, "Configuration file for Apache");

        $param['php_rp_temp'] = $this->convertRp($param['rp_temp']);
        $param['php_rp_var']  = $this->convertRp($param['rp_var']);
        $param['php_rp_log']  = $this->convertRp($param['rp_log']);
        $param['php_rp_conf'] = $this->convertRp($param['rp_conf']);
        $param['php_rp_www']  = $this->convertRp($param['rp_www']);
        $param['php_rp_cmd']  = $this->convertRp($param['rp_cmd']);
        $param['php_rp_jelix']  = $this->convertRp($param['rp_jelix']);
        if ($param['rp_vendor']) {
           $param['php_rp_vendor']  = $this->convertRp($param['rp_vendor']);
           $this->createFile($appPath.'application.init.php','application2.init.php.tpl',$param, "Bootstrap file");
        }
        else {
           $this->createFile($appPath.'application.init.php','application.init.php.tpl',$param, "Bootstrap file");
        }

        $installer = new \Jelix\Installer\Installer(new \Jelix\Installer\Reporter\Console('warning'));
        $installer->installApplication();

        $moduleok = true;

        if (!$this->getOption('-nodefaultmodule')) {
            try {
                $cmd = JelixScript::getCommand('createmodule', $this->config);
                $options = $this->getCommonActiveOption();
                $options['-addinstallzone'] = true;
                $options['-noregistration'] = true;
                $cmd->initOptParam($options, array('module'=>$param['modulename']));
                $cmd->run();
                $this->createFile($appPath.'modules/'.$param['modulename'].'/templates/main.tpl', 'module/main.tpl.tpl', $param, "Main template");
            } catch (Exception $e) {
                $moduleok = false;
                echo "The module has not been created because of this error: ".$e->getMessage()."\nHowever the application has been created\n";
            }
        }

        if ($this->getOption('-withcmdline')) {
            if(!$this->getOption('-nodefaultmodule') && $moduleok){
                $agcommand = JelixScript::getCommand('createctrl', $this->config);
                $options = $this->getCommonActiveOption();
                $options['-cmdline'] = true;
                $agcommand->initOptParam($options, array('module'=>$param['modulename'], 'name'=>'default','method'=>'index'));
                $agcommand->run();
            }
            $agcommand = JelixScript::getCommand('createentrypoint', $this->config);
            $options = $this->getCommonActiveOption();
            $options['-type'] = 'cmdline';
            $parameters = array('name'=>$param['modulename']);
            $agcommand->initOptParam($options, $parameters);
            $agcommand->run();
        }
    }

    protected function convertRp($rp) {
        if(strpos($rp, './') === 0)
            $rp = substr($rp, 2);
        if (strpos($rp, '../') !== false) {
            return 'realpath(__DIR__.\'/'.$rp."').'/'";
        }
        else if (DIRECTORY_SEPARATOR == '/' && $rp[0] == '/') {
            return "'".$rp."'";
        }
        else if (DIRECTORY_SEPARATOR == '\\' && preg_match('/^[a-z]\:/i', $rp)) { // windows
            return "'".$rp."'";
        }
        else {
            return '__DIR__.\'/'.$rp."'";
        }
    }
}
