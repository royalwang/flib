<?php

/**
 *
 * 作者: 范圣帅(fanshengshuai@gmail.com)
 *
 * 创建: 2011-04-18 22:35:29
 * vim: set expandtab sw=4 ts=4 sts=4 * 
 *
 * $Id: Flib.php 125 2012-08-05 03:31:14Z fanshengshuai $
 */
class Flib {
    private static $_instance = array();

    /**
     +----------------------------------------------------------
     * 应用程序初始化
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function Start() {

        global $_G;

        header("Content-type: text/html; charset=utf-8");
        define('SYS_ROOT', dirname(__FILE__) . '/');

        date_default_timezone_set('Asia/Chongqing');
        error_reporting(7);
        if(phpversion() < '5.3.0') {
            set_magic_quotes_runtime(0);
        }

        // 加载函数类
        require_once SYS_ROOT . "functions/function_core.php";
        require_once APP_ROOT . "config/global.php";

        if ($_config['global']['debug']) {
            $_G['debug'] = true;
        }

        if ($_REQUEST['refer']) {
            $_G['refer'] = $_REQUEST['refer'];
        } else {
            $_G['refer'] = $_SERVER['HTTP_REFERER'];
        }

        $_G['domain'] = $_SERVER['HTTP_HOST'];
        $_G['cname'] = substr($_G['domain'], 0, strpos($_G['domain'], '.'));

        if ($_REQUEST['in_ajax']) {
            $_G['in_ajax'] = true;
        }

        // 设定错误和异常处理
        set_error_handler(array('Flib','appError'));
        set_exception_handler(array('Flib','appException'));

        // 注册AUTOLOAD方法
        spl_autoload_register(array('Flib', 'autoload'));

        // 运行应用
        if (RUN_MODE == 'web') {
            App::run();
        }

        return ;
    }

    /**
     +----------------------------------------------------------
     * 系统自动加载Flib类库
     * 并且支持配置自动加载路径
     +----------------------------------------------------------
     * @param string $class 对象类名
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    public static function autoload($className) {
        global $_G;

        // if autoload Smarty, return false;
        if (strpos($className, 'Smarty') === 0) {
            return ;
        }

        $file = str_replace(
            array('_'),
            array('/'),
            $className
        );

        $inc_file = SYS_ROOT . $file . '.php';
        if (file_exists($inc_file)) {
            if ($_G['debug']) {
                $_G['debug_info']['autoload_files'][] = $inc_file;
            }
            return require_once($inc_file);
        }

        $file = str_replace(
            array('Service_', 'DAO_', 'Controller_'),
            array('services/', 'dao/', 'controllers/'),
            $className
        );

        $inc_file = $file = APP_ROOT . $file . '.php';
        //echo $file . "<br />";
        if (file_exists($file)) {
            if ($_G['debug']) {
                $_G['debug_info']['autoload_files'][] = $inc_file;
            }
            return require_once($file);
        }

        $class_expolde = explode('_', $className);
        $class_expolde_len = sizeof($class_expolde);

        foreach ($class_expolde as $key => $item) {
            if ($key < ($class_expolde_len - 1)) {
                $class_expolde[$key] = strtolower($item);
            }
        }

        $class_file = join('/', $class_expolde);

        $file = str_replace(
            array('service/', 'dao/', 'controller/'),
            array('services/', 'dao/', 'controllers/'),
            $class_file
        );

        $inc_file = $file = APP_ROOT . $file . '.php';
        if (file_exists($file)) {
            if ($_G['debug']) {
                $_G['debug_info']['autoload_files'][] = $inc_file;
            }
            return require_once($file);
        }
    }

    /**
     +----------------------------------------------------------
     * 自定义异常处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param mixed $e 异常对象
     +----------------------------------------------------------
     */
    static public function appException($e) {
        $exception = new FlibException;
        $exception->traceError($e);
        exit;
    }

    /**
     +----------------------------------------------------------
     * 自定义错误处理
     +----------------------------------------------------------
     * @access public
     +----------------------------------------------------------
     * @param int $errno 错误类型
     * @param string $errstr 错误信息
     * @param string $errfile 错误文件
     * @param int $errline 错误行数
     +----------------------------------------------------------
     * @return void
     +----------------------------------------------------------
     */
    static public function appError($errno, $errstr, $errfile, $errline) {

        $exception = new FlibException;

      switch ($errno) {
          case E_ERROR:
          case E_USER_ERROR:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            //if(C('LOG_RECORD')) Log::write($errorStr,Log::ERR);
            $exception->printMessage($errorStr);
            break;
          case E_STRICT:
          case E_USER_WARNING:
          case E_USER_NOTICE:
          default:
            $errorStr = "[$errno] $errstr ".basename($errfile)." 第 $errline 行.";
            break;
      }
    }

}

Flib::Start();
