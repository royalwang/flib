<?php

/**
 *
 * 作者: 范圣帅(fanshengshuai@gmail.com)
 *
 * 创建: 2011-04-18 22:35:29
 * vim: set expandtab sw=4 ts=4 sts=4 *
 *
 * $Id: Flib.php 178 2012-08-10 03:35:55Z fanshengshuai $
 */
class Flib {
    private static $_instance = array();

    /**
     * 应用程序初始化
     *
     * @access public
     */
    static public function Start() {
        global $_G, $_F;

        // 加载函数类
        require_once FLIB_ROOT . "functions/function_core.php";

        if (Config::get('global.flib_compress')) {
            if (!file_exists(APP_ROOT . "data/_flib_min.php")) {
                self::createFlibMin();
            }
            include_once(APP_ROOT . "data/_flib_min.php");
        }

        if (!$_G ['uri']) {
            FDispatcher::getURI();
        }


        if (FLIB_RUN_MODE != 'manual') {
            self::StartApp();
        }

        return;
    }

    public static function StartApp() {
        global $_G;

        // 是否开了子域名
        if (Config::get('global.sub_domain.status') == 'on' && $_G['cname'] != Config::get('global.sub_domain.default')) {
            foreach (Config::get('global.sub_domain.sub_domain_rewrite') as $key => $value) {
                if ($key == $_G['cname']) {
                    $_G['module'] = $value;
                }

                if ($key == '*') {
                    $default_module = $value;
                }
            }

            if (!$_G['module']) {
                $_G['module'] = $default_module;
            }

            if ($_G['cname'] != 'www') {
                define('ROUTER', $_G['module']);
            }
        }

        App::run();
    }

    /**
     * 系统自动加载Flib类库，并且支持配置自动加载路径
     *
     * @param string $class
     *            对象类名
     */
    public static function autoLoad($className) {
        global $_G;

        // if autoload Smarty, return false;
        if (strpos($className, 'Smarty') === 0) {
            return;
        }

        $class_explode = explode('_', $className);
        $class_explode_len = sizeof($class_explode);
        foreach ($class_explode as $key => $item) {
            if ($key < ($class_explode_len - 1)) {
                $class_explode [$key] = strtolower($item);
            }
        }
        $class_file = join('/', $class_explode) . ".php";

        $file = str_replace(
            array('service/', 'dao/', 'controller/'),
            array('services/', 'dao/', 'c/'),
            $class_file);

        // 查是不是 flib 的 class
        $inc_file = FLIB_ROOT . $file;
        if (file_exists($inc_file)) {
            if ($_G ['debug']) {
                $_G ['debug_info'] ['autoload_files'] [] = $inc_file;
            }

            return require_once($inc_file);
        }

        // 查是不是 App 的 class
        if ($_G['module']) {
            $inc_file = APP_ROOT . 'modules/' .$_G['module'] . '/' . $file;
        }

        if (file_exists($inc_file)) {
            if ($_G ['debug']) {
                $_G ['debug_info'] ['autoload_files'] [] = $inc_file;
            }

            return require_once($inc_file);
        }

        if (count(spl_autoload_functions()) == 1) {
            throw new Exception('File no found: ' . $inc_file);

            if ($_G ['debug']) {
                $_G ['debug_info'] ['autoload_files'] [] = "<span style='color:red'>{$inc_file} <strong>[ FAILED ]</strong></span><br /> Class: {$className}";
            }
        } else {
            spl_autoload_unregister(array('Flib', 'autoLoad'));
        }
    }

    /**
     * 自定义异常处理
     *
     * @access public
     *
     * @param mixed $e
     *            异常对象
     */
    static public function appException($e) {
        $exception = new FException ();
        $exception->traceError($e);
        exit ();
    }

    /**
     * 自定义错误处理
     *
     * @access public
     *
     * @param int    $errno
     *            错误类型
     * @param string $errstr
     *            错误信息
     * @param string $errfile
     *            错误文件
     * @param int    $errline
     *            错误行数
     */
    static public function appError($err_no, $err_str, $err_file, $err_line) {
        global $_G;

        switch ($err_no) {
            case E_ERROR :
            case E_USER_ERROR :
                $errorStr = "[$err_no] $err_str " . basename($err_file) . " 第 $err_line 行.";
                // if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
                $exception = new FException ();
                $exception->printMessage($errorStr);
                break;
            case E_STRICT :
                $_G['errors']['STRICT'][] = "[$err_no] $err_str " . basename($err_file) . " 第 $err_line 行.";
                break;
            case E_WARNING:
            case E_USER_WARNING :
                $_G['errors']['WARNING'][] = "[$err_no] $err_str " . basename($err_file) . " 第 $err_line 行.";
                break;
            case E_NOTICE:
            case E_USER_NOTICE :
                $_G['errors']['NOTICE'][] = "[$err_no] $err_str " . basename($err_file) . " 第 $err_line 行.";
                break;
            default :
                $_G['errors']['OTHER'][] = "[$err_no] $err_str " . basename($err_file) . " 第 $err_line 行.";
                break;
        }
    }

    public static function createFlibMin() {
        $files = "DB/Table, FCookie, FFile, FView, DAO, App, FDB, Pager, FCache, FException, FDispatcher, FController, C, Cache";
        $files = explode(',', $files); /*Config,*/

        $flib_str = '';
        foreach ($files as $f) {
            $f = FLIB_ROOT . trim($f) . '.php';
            $_content = file_get_contents($f);
            $flib_str .= $_content;
        }

        $flib_str = str_replace('<?php', '', $flib_str);
        $flib_str = preg_replace('#/\*.+?\*/#si', '', $flib_str);
        $flib_str = preg_replace('#//.+?$#sim', '', $flib_str);
        $flib_str = preg_replace("#\s{2,}#si", ' ', $flib_str);

        file_put_contents(APP_ROOT . "data/_flib_min.php", "<?php {$flib_str}");
    }

    public static function init() {
        global $_G;

        $_G ['config'] = array();
        header("Content-type: text/html; charset=utf-8");
        header("Access-Control-Allow-Origin: *");

        if (!defined('FLIB_ROOT')) {
            define ('FLIB_ROOT', dirname(__FILE__) . '/');
        }

        date_default_timezone_set('Asia/Chongqing');
        error_reporting(7);
        if (phpversion() < '5.3.0') set_magic_quotes_runtime(0);

        // 注册AUTOLOAD方法
        spl_autoload_register(array('Flib', 'autoLoad'));

        // 设定错误和异常处理
        set_error_handler(array('Flib', 'appError'));
        set_exception_handler(array('Flib', 'appException'));

        $_G ['user_agent'] = $_SERVER ['HTTP_USER_AGENT'];
        $_G ['query_string'] = $_SERVER ['QUERY_STRING'];
        $_G ['http_host'] = $_SERVER ['HTTP_HOST'];
        $_G ['top_domain'] = substr($_G ['domain'], strpos($_G ['domain'], '.') + 1);
        $_G ['cookie_domain'] = substr($_G ['http_host'], strpos($_G ['http_host'], '.'));
        $_G ['cname'] = substr($_G ['http_host'], 0, strpos($_G ['http_host'], '.'));

        $_G ['refer'] = $_REQUEST ['refer'] ? $_REQUEST ['refer'] : $_SERVER ['HTTP_REFERER'];

        $_G ['in_ajax'] = ($_REQUEST['in_ajax'] || $_GET ['in_ajax'] || $_POST ['in_ajax']) ? true : false;

//        define('IS_CGI',substr(PHP_SAPI, 0,3)=='cgi' ? 1 : 0 );
//        define('IS_WIN',strstr(PHP_OS, 'WIN') ? 1 : 0 );
//        define('IS_CLI',PHP_SAPI=='cli'? 1   :   0);
    }
}

Flib::init();
Flib::Start();