<?php
/**
 * Zikula Application Framework
 *
 * @copyright  (c) Zikula Development Team
 * @link       http://www.zikula.org
 * @version    $Id$
 * @license    GNU/GPL - http://www.gnu.org/copyleft/gpl.html
 * @author     sven schomacker <hilope@gmail.com>
 * @category   Zikula_Extension
 * @package    Utilities
 * @subpackage scribite!
 */

// load some scripts for scribite!

// load prototype as js onload loader
PageUtil::AddVar('javascript', 'javascript/ajax/prototype.js');
PageUtil::AddVar('javascript', 'javascript/ajax/scriptaculous.js');

// main function
function scribite_admin_main()
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // Create output object
    $render = pnRender::getInstance('scribite', false);

    // get the output of the main function
    $render->assign('main', scribite_admin_modifyconfig(array()));

    // Return the output that has been generated by this function
    return $render->fetch('scribite_admin_main.htm');

}

// modify scribite! configuration
function scribite_admin_modifyconfig($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get configs for modules
    $modconfig = pnModAPIFunc('scribite', 'user', 'getModuleConfig', array('modulename' => "list"));

    // create template and fill vars
    $render = pnRender::getInstance('scribite', false);
    // get module vars
    $render->assign(pnModGetVar('scribite'));
    // load all editors to array
    $render->assign('editor_list', pnModAPIFunc('scribite', 'user', 'getEditors', array('editorname' => 'list')));
    // get default editor
    $render->assign('DefaultEditor', pnModGetVar('scribite', 'DefaultEditor'));
    $render->assign('modconfig', $modconfig);

    // check for activated js quicktags - will cause problems with editors
    $jsquicktags = pnModGetVar('/PNConfig', 'jsquicktags');
    if ($jsquicktags == true) {
        $render->assign('jsquicktags', true);
    } else {
        $render->assign('jsquicktags', false);
    }

    return $render->fetch('scribite_admin_modifyconfig.htm');
}

function scribite_admin_updateconfig($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
    return true;
    }

    // modify editors in db
    $modconfig = FormUtil::getPassedValue('modconfig', null, 'REQUEST');
    foreach ($modconfig as $mod) {
        pnModAPIFunc('scribite', 'admin', 'editmoduledirect', $mod);
    }

    // modify editors path in db
    $editors_path = FormUtil::getPassedValue('editors_path', 'javascript/scribite_editors', 'REQUEST');
    if(!pnModSetVar('scribite', 'editors_path', $editors_path)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    // modify default editor
    $DefaultEditor = FormUtil::getPassedValue('DefaultEditor', '-', 'REQUEST');
    if(!pnModSetVar('scribite', 'DefaultEditor', $DefaultEditor)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }

    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));
    return pnRedirect(pnModURL('scribite', 'admin', 'main'));
}

// add new module config to scribite
function scribite_admin_newmodule($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create smarty instance and fill vars
    $render = pnRender::getInstance('scribite', false);
    // get all editors
    $render->assign('editor_list', pnModAPIFunc('scribite', 'user', 'getEditors', array('editorname' => 'list')));
    return $render->fetch('scribite_admin_addmodule.htm');

}

// add new module to database
function scribite_admin_addmodule($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    // get args from template
    $modulename = FormUtil::getPassedValue('modulename', null, 'REQUEST');
    $modfuncs   = FormUtil::getPassedValue('modfuncs',   null, 'REQUEST');
    $modareas   = FormUtil::getPassedValue('modareas',   null, 'REQUEST');
    $modeditor  = FormUtil::getPassedValue('modeditor',  null, 'REQUEST');

    // create new module in db
    $mid = pnModAPIFunc('scribite', 'admin', 'addmodule', array('modulename' => $modulename,
                                'modfuncs'  => $modfuncs,
                                'modareas'  => $modareas,
                                'modeditor' => $modeditor));

    // Error tracking
    if ($mid != false) {
        // Success
        LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));
    } else {
        // Error
        LogUtil::registerStatus (__('Configuration not updated', $dom));
    }

// return to main form
return pnRedirect(pnModURL('scribite', 'admin', 'main'));

}

// edit module config
function scribite_admin_modifymodule($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $mid = FormUtil::getPassedValue('mid', null, 'REQUEST');

    // get config for current module
    $modconfig = pnModAPIFunc('scribite', 'admin', 'getModuleConfigfromID', array('mid' => $mid));

    $modules = pnModGetAllMods();

    // create smarty instance
    $render = pnRender::getInstance('scribite', false);
    // get all editors
    $render->assign('editor_list', pnModAPIFunc('scribite', 'user', 'getEditors', array('editorname' => 'list')));
    $render->assign('mid', $modconfig['mid']);
    $render->assign('modulename', $modconfig['modname']);
    $render->assign('modfuncs', implode(',', unserialize($modconfig['modfuncs'])));
    $render->assign('modareas', implode(',', unserialize($modconfig['modareas'])));
    $render->assign('modeditor', $modconfig['modeditor']);

    return $render->fetch('scribite_admin_modifymodule.htm');

}

// update module config in database
function scribite_admin_updatemodule($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    // get passed args and store to array
    $modconfig['mid']        = FormUtil::getPassedValue('mid',        null, 'REQUEST');
    $modconfig['modulename'] = FormUtil::getPassedValue('modulename', null, 'REQUEST');
    $modconfig['modfuncs']   = FormUtil::getPassedValue('modfuncs',   null, 'REQUEST');
    $modconfig['modareas']   = FormUtil::getPassedValue('modareas',   null, 'REQUEST');
    $modconfig['modeditor']  = FormUtil::getPassedValue('modeditor',  null, 'REQUEST');

    $mod = pnModAPIFunc('scribite', 'admin', 'editmodule', $modconfig);

    // error tracking
    if ($mod != false) {
        // Success
        LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));
    } else {
        // Error
        LogUtil::registerStatus (__('Configuration not updated', $dom));
    }

    return pnRedirect(pnModURL('scribite', 'admin', 'main'));

}

//
function scribite_admin_delmodule($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Securty check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get module id
    $mid = FormUtil::getPassedValue('mid', null, 'REQUEST');

    // get module config and name from id
    $modconfig  = pnModAPIFunc('scribite', 'admin', 'getModuleConfigfromID', array('mid' => $mid));

    // create smarty instance
    $render = pnRender::getInstance('scribite', false);
    $render->assign('mid', $mid);
    $render->assign('modulename', $modconfig['modname']);
    return $render->fetch('scribite_admin_delmodule.htm');

}

// del module config in database
function scribite_admin_removemodule($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    // get passed args
    $args['mid'] = FormUtil::getPassedValue('mid', null, 'REQUEST');

    // remove module entry from scribite! table
    $mod = pnModAPIFunc('scribite', 'admin', 'delmodule', array('mid' => $args['mid']));

    if ($mod != false) {
        // Success
        LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));
    }

    // return to main page
    return pnRedirect(pnModURL('scribite', 'admin', 'main'));

}

function scribite_admin_modifyxinha($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create smarty instance
    $render = pnRender::getInstance('scribite', false);
    $render->assign(pnModGetVar('scribite'));
    $render->assign('xinha_langlist', pnModAPIFunc('scribite', 'admin', 'getxinhaLangs'));
    $render->assign('xinha_skinlist', pnModAPIFunc('scribite', 'admin', 'getxinhaSkins'));
    $render->assign('xinha_allplugins', pnModAPIFunc('scribite', 'admin', 'getxinhaPlugins'));
    return $render->fetch('scribite_admin_modifyxinha.htm');

}

function scribite_admin_updatexinha($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $xinha_language      = FormUtil::getPassedValue('xinha_language', 'en', 'REQUEST');
    $xinha_skin          = FormUtil::getPassedValue('xinha_skin', 'blue-look', 'REQUEST');
    $xinha_barmode       = FormUtil::getPassedValue('xinha_barmode', 'reduced', 'REQUEST');
    $xinha_width         = FormUtil::getPassedValue('xinha_width', 'auto', 'REQUEST');
    $xinha_height        = FormUtil::getPassedValue('xinha_height', 'auto', 'REQUEST');
    $xinha_style         = FormUtil::getPassedValue('xinha_style', 'modules/scribite/pnconfig/xinha/editor.css', 'REQUEST');
    $xinha_converturls   = FormUtil::getPassedValue('xinha_converturls', '0', 'REQUEST');
    $xinha_showloading   = FormUtil::getPassedValue('xinha_showloading', '0', 'REQUEST');
    $xinha_statusbar     = FormUtil::getPassedValue('xinha_statusbar', 1, 'REQUEST');
    $xinha_activeplugins = FormUtil::getPassedValue('xinha_activeplugins', null, 'REQUEST');

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    if (!pnModSetVar('scribite', 'xinha_language', $xinha_language)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'xinha_skin', $xinha_skin)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'xinha_barmode', $xinha_barmode)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $xinha_width = rtrim($xinha_width, 'px');
    if (!pnModSetVar('scribite', 'xinha_width', $xinha_width)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $xinha_height = rtrim($xinha_height, 'px');
    if (!pnModSetVar('scribite', 'xinha_height', $xinha_height)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $xinha_style = ltrim($xinha_style, '/');
    if (!pnModSetVar('scribite', 'xinha_style', $xinha_style)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'xinha_converturls', $xinha_converturls)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'xinha_showloading', $xinha_showloading)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'xinha_statusbar', $xinha_statusbar)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!empty($xinha_activeplugins)) {
        $xinha_activeplugins = serialize($xinha_activeplugins);
    }
    if (!pnModSetVar('scribite', 'xinha_activeplugins', $xinha_activeplugins)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }

    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));
    return pnRedirect(pnModURL('scribite', 'admin', 'modifyxinha'));

}

function scribite_admin_modifyopenwysiwyg($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create smarty instance
    $render = pnRender::getInstance('scribite', false);
    $render->assign(pnModGetVar('scribite'));

    return $render->fetch('scribite_admin_modifyopenwysiwyg.htm');

}

function scribite_admin_updateopenwysiwyg($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $openwysiwyg_barmode = FormUtil::getPassedValue('openwysiwyg_barmode', 'small', 'REQUEST');
    $openwysiwyg_width   = FormUtil::getPassedValue('openwysiwyg_width', '500px', 'REQUEST');
    $openwysiwyg_height  = FormUtil::getPassedValue('openwysiwyg_height', '300px', 'REQUEST');

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    if (!pnModSetVar('scribite', 'openwysiwyg_barmode', $openwysiwyg_barmode)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $openwysiwyg_width = rtrim($openwysiwyg_width, 'px');
    if (!pnModSetVar('scribite', 'openwysiwyg_width', $openwysiwyg_width)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $openwysiwyg_height = rtrim($openwysiwyg_height, 'px');
    if (!pnModSetVar('scribite', 'openwysiwyg_height', $openwysiwyg_height)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }

    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('scribite', 'admin', 'modifyopenwysiwyg'));

}
// TinyMCE is deprecated - function deprecated
function scribite_admin_modifytinymce($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create smarty instance
    $render = pnRender::getInstance('scribite', false);
    $render->assign(pnModGetVar('scribite'));
    $render->assign('tinymce_langlist', pnModAPIFunc('scribite', 'admin', 'gettinymceLangs'));
    $render->assign('tinymce_themelist', pnModAPIFunc('scribite', 'admin', 'gettinymceThemes'));
    $render->assign('tinymce_allplugins', pnModAPIFunc('scribite', 'admin', 'gettinymcePlugins'));

    return $render->fetch('scribite_admin_modifytinymce.htm');

}
// TinyMCE is deprecated - function deprecated
function scribite_admin_updatetinymce($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $tinymce_language      = FormUtil::getPassedValue('tinymce_language', 'en', 'REQUEST');
    $tinymce_style         = FormUtil::getPassedValue('tinymce_style', 'modules/scribite/pnconfig/tiny_mce/editor.css', 'REQUEST');
    $tinymce_theme         = FormUtil::getPassedValue('tinymce_theme', 'advanced', 'REQUEST');
    $tinymce_width         = FormUtil::getPassedValue('tinymce_width', '75%', 'REQUEST');
    $tinymce_height        = FormUtil::getPassedValue('tinymce_height', '400', 'REQUEST');
    $tinymce_activeplugins = FormUtil::getPassedValue('tinymce_activeplugins', 'en', 'REQUEST');
    $tinymce_dateformat    = FormUtil::getPassedValue('tinymce_dateformat', '%Y-%m-%d', 'REQUEST');
    $tinymce_timeformat    = FormUtil::getPassedValue('tinymce_timeformat', '%H:%M:%S', 'REQUEST');

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    if (!pnModSetVar('scribite', 'tinymce_language', $tinymce_language)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $tinymce_style = ltrim($tinymce_style, '/');
    if (!pnModSetVar('scribite', 'tinymce_style', $tinymce_style)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'tinymce_theme', $tinymce_theme)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $tinymce_width = rtrim($tinymce_width, 'px');
    if (!pnModSetVar('scribite', 'tinymce_width', $tinymce_width)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $tinymce_height = rtrim($tinymce_height, 'px');
    if (!pnModSetVar('scribite', 'tinymce_height', $tinymce_height)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!empty($tinymce_activeplugins)) {
        $tinymce_activeplugins = serialize($tinymce_activeplugins);
    }
    if (!pnModSetVar('scribite', 'tinymce_activeplugins', $tinymce_activeplugins)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'tinymce_dateformat', $tinymce_dateformat)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'tinymce_timeformat', $tinymce_timeformat)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }

    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('scribite', 'admin', 'modifytinymce'));

}
// FCKeditor is deprecated - function deprecated
function scribite_admin_modifyfckeditor($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $render = pnRender::getInstance('scribite', false);
    $render->assign(pnModGetVar('scribite'));
    $render->assign('fckeditor_barmodelist', pnModAPIFunc('scribite', 'admin', 'getfckeditorBarmodes'));
    $render->assign('fckeditor_langlist', pnModAPIFunc('scribite', 'admin', 'getfckeditorLangs'));

    return $render->fetch('scribite_admin_modifyfckeditor.htm');

}
// FCKeditor is deprecated - function deprecated
function scribite_admin_updatefckeditor($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $fckeditor_language = FormUtil::getPassedValue('fckeditor_language', 'en', 'REQUEST');
    $fckeditor_barmode  = FormUtil::getPassedValue('fckeditor_barmode', 'Default', 'REQUEST');
    $fckeditor_width    = FormUtil::getPassedValue('fckeditor_width', '500', 'REQUEST');
    $fckeditor_height   = FormUtil::getPassedValue('fckeditor_height', '400', 'REQUEST');
    $fckeditor_autolang = FormUtil::getPassedValue('fckeditor_autolang', 0, 'REQUEST');

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    if (!pnModSetVar('scribite', 'fckeditor_language', $fckeditor_language)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'fckeditor_barmode', $fckeditor_barmode)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $fckeditor_width = rtrim($fckeditor_width, 'px');
    if (!pnModSetVar('scribite', 'fckeditor_width', $fckeditor_width)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    $fckeditor_height = rtrim($fckeditor_height, 'px');
    if (!pnModSetVar('scribite', 'fckeditor_height', $fckeditor_height)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'fckeditor_autolang', $fckeditor_autolang)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }

    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('scribite', 'admin', 'modifyfckeditor'));

}

function scribite_admin_modifynicedit($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create smarty instance
    $render = pnRender::getInstance('scribite', false);
    $render->assign(pnModGetVar('scribite'));

    return $render->fetch('scribite_admin_modifynicedit.htm');
}

function scribite_admin_updatenicedit($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $nicedit_fullpanel = FormUtil::getPassedValue('nicedit_fullpanel', 0, 'REQUEST');
    $nicedit_xhtml     = FormUtil::getPassedValue('nicedit_xhtml', 0, 'REQUEST');

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    if (!pnModSetVar('scribite', 'nicedit_fullpanel', $nicedit_fullpanel)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'nicedit_xhtml', $nicedit_xhtml)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }

    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('scribite', 'admin', 'modifynicedit'));

}

function scribite_admin_modifyyui($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // create smarty instance
    $render = pnRender::getInstance('scribite', false);
    $render->assign(pnModGetVar('scribite'));

    // Get yui types
    $render->assign('yui_types', pnModAPIFunc('scribite', 'admin', 'getyuitypes'));

    return $render->fetch('scribite_admin_modifyyui.htm');
}

function scribite_admin_updateyui($args)
{
    $dom = ZLanguage::getModuleDomain('scribite');
    // Security check
    if (!SecurityUtil::checkPermission( 'scribite::', '::', ACCESS_ADMIN)) {
        return LogUtil::registerPermissionError();
    }

    // get passed args
    $yui_type     = FormUtil::getPassedValue('yui_type', 'Simple', 'REQUEST');
    $yui_width    = FormUtil::getPassedValue('yui_width', 'auto', 'REQUEST');
    $yui_height   = FormUtil::getPassedValue('yui_height', 'auto', 'REQUEST');
    $yui_dombar   = FormUtil::getPassedValue('yui_dombar', false, 'REQUEST');
    $yui_animate  = FormUtil::getPassedValue('yui_animate', false, 'REQUEST');
    $yui_collapse = FormUtil::getPassedValue('yui_collapse', false, 'REQUEST');

    if (!SecurityUtil::confirmAuthKey()) {
        LogUtil::registerStatus (__("Invalid 'authkey':  this probably means that you pressed the 'Back' button, or that the page 'authkey' expired. Please refresh the page and try again.", $dom));
        pnRedirect(pnModURL('scribite', 'admin', 'main'));
        return true;
    }

    if (!pnModSetVar('scribite', 'yui_type', $yui_type)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'yui_width', $yui_width)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'yui_height', $yui_height)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'yui_dombar', $yui_dombar)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'yui_animate', $yui_animate)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    if (!pnModSetVar('scribite', 'yui_collapse', $yui_collapse)) {
        LogUtil::registerStatus (__('Configuration not updated', $dom));
        return false;
    }
    // the module configuration has been updated successfuly
    LogUtil::registerStatus (__('Done! Module configuration updated.', $dom));

    return pnRedirect(pnModURL('scribite', 'admin', 'modifyyui'));

}

