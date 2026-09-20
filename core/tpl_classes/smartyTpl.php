<?php
/******************************************************************************/
//                                                                            //
//                           InstantCMS v1.10.6                               //
//                        http://www.instantcms.ru/                           //
//                                                                            //
//                   written by InstantCMS Team, 2007-2015                    //
//                produced by InstantSoft, (www.instantsoft.ru)               //
//                                                                            //
//                        LICENSED BY GNU/GPL v2                              //
//                                                                            //
/******************************************************************************/
/**
 * Класс инициализации шаблонизатора Smarty
 */
class smartyTpl{

    private static $i_smarty;
    private $smarty;

    public function __construct($tpl_folder, $tpl_file){

        global $_LANG;

        $this->smarty = $this->loadSmarty();

        $is_exists_tpl_file = file_exists(TEMPLATE_DIR . $tpl_folder.'/'.$tpl_file);

        $template_dir = $is_exists_tpl_file ? TEMPLATE_DIR : DEFAULT_TEMPLATE_DIR;

        $this->smarty->setTemplateDir($template_dir.'/'.$tpl_folder);

        $this->smarty->compile_id = $is_exists_tpl_file ? TEMPLATE : pathinfo(DEFAULT_TEMPLATE_DIR, PATHINFO_BASENAME);
        $this->smarty->assign('LANG', $_LANG);

    }

    private function loadSmarty(){

        if(isset(self::$i_smarty)){
            return self::$i_smarty;
        }

        cmsCore::includeFile('/includes/smarty/Smarty.class.php');

        $smarty = new Smarty();

        // Функции PHP, используемые в шаблонах как модификаторы: регистрируем явно
        foreach (array('ceil', 'floor', 'round', 'abs', 'str_repeat', 'icms_ucfirst') as $modifier) {
            if (function_exists($modifier)) {
                $smarty->registerPlugin('modifier', $modifier, $modifier);
            }
        }

        // Кастомные плагины CMS из includes/smarty/plugins.
        // В Smarty 5 каталог plugins больше не сканируется автоматически — регистрируем вручную.
        foreach (array('function', 'modifier', 'block', 'compiler') as $plugin_type) {

            foreach ((array)glob(PATH.'/includes/smarty/plugins/'.$plugin_type.'.*.php') as $plugin_file) {

                if (!preg_match('/^'.preg_quote($plugin_type, '/').'\.(.+)\.php$/', basename($plugin_file), $matches)) { continue; }

                require_once $plugin_file;

                $plugin_callback = 'smarty_'.$plugin_type.'_'.$matches[1];

                if (function_exists($plugin_callback) || class_exists($plugin_callback)) {
                    $smarty->registerPlugin($plugin_type, $matches[1], $plugin_callback);
                }
            }
        }

        $smarty->setCompileDir(PATH.'/cache/');
        $smarty->setCacheDir(PATH.'/cache/');
        $smarty->assign('is_ajax', cmsCore::isAjax());
        $smarty->assign('is_auth', cmsUser::getInstance()->id);

        self::$i_smarty = $smarty;

        return $smarty;

    }

    public function __set($name, $value){
        $this->smarty->{$name} = $value;
    }

    public function __get($name){
        return $this->smarty->{$name};
    }

    public function __call($name, $arguments){
        return call_user_func_array(array($this->smarty, $name), $arguments);
    }

}