<?php
namespace Tiny;
/**
 * TinyPHP 应用程序类 执行应用过程管理
 */
class App
{
    public static function run(){
        //全局粗过滤
        // array_walk_recursive($_GET,'tiny_fliter');
        // array_walk_recursive($_POST,'tiny_fliter');
        // array_walk_recursive($_REQUEST,'tiny_fliter');

        // 开启session
        APP::set_session();
        //执行应用程序
        APP::execute();
    }

    /**
     * 正式执行应用程序
     */
    static private function execute(){
        //解析PATH_INFO
        $pathinfo=explode("/",$_SERVER[PATH_INFO]);
        if($pathinfo[1]=='')
            $controller=DEFAULT_CONTROLLER;
        else{
            //安全过滤
            $controller=$pathinfo[1];
        }
        if($pathinfo[2]=='')
            $action=DEFAULT_ACTION;
        else{
            //安全过滤
            $action=$pathinfo[2];
        }

        //运行控制器
        $module=controller($controller);
        if(!$module)
            exit("can not open controller");

        //执行当前操作
        if (!method_exists($module,$action)) exit("can not find action");
        $method=new \ReflectionMethod($module,$action);
        if ($method->isPublic()&&!$method->isStatic()){
            //$class=\ReflectionClass($module);
            define('ACTION_NAME',$action);
            define('CONTROLLER_NAME',$controller);

            $method->invoke($module);
        }else{
            exit("action is not public or action is static");
        }
    }

    /**
     * 读取session配置并且开启session机制
     */
    static public function set_session(){
        // 读取session配置
        $option=load_config(APP_CONF.'session.php');
        ini_set('session.name',$option['SESSION_NAME']);
        ini_set('session.save_path',$option['SESSION_PATH_NAME']);
        // 开启session
        session_start();
    }
}
