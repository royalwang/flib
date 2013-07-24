<?php

if (!function_exists('redirect')) {
    function redirect($url, $target = '') {
        global $_G;

        if ($_G['in_ajax']) {
            $c = new Controller;
            $c->ajaxRedirect($url);
        } else {

            if ($target) {
                echo "<script> {$target}.location.href = '{$url}'; </script>";
            } else {
                header("location: " . $url);
            }
        }

        exit;
    }
}

// 判断是否为手机访问
function is_mobile() {
    $user_agent = $_SERVER['HTTP_USER_AGENT'];
    $mobile_agents = Array("240x320", "acer", "acoon", "acs-", "abacho","ahong","airness","alcatel","amoi","android","anywhereyougo.com","applewebkit/525","applewebkit/532","asus","audio","au-mic","avantogo","becker","benq","bilbo","bird","blackberry","blazer","bleu","cdm-","compal","coolpad","danger","dbtel","dopod","elaine","eric","etouch","fly ","fly_","fly-","go.web","goodaccess","gradiente","grundig","haier","hedy","hitachi","htc","huawei","hutchison","inno","ipad","ipaq","ipod","jbrowser","kddi","kgt","kwc","lenovo","lg ","lg2","lg3","lg4","lg5","lg7","lg8","lg9","lg-","lge-","lge9","longcos","maemo","mercator","meridian","micromax","midp","mini","mitsu","mmm","mmp","mobi","mot-","moto","nec-","netfront","newgen","nexian","nf-browser","nintendo","nitro","nokia","nook","novarra","obigo","palm","panasonic","pantech","philips","phone","pg-","playstation","pocket","pt-","qc-","qtek","rover","sagem","sama","samu","sanyo","samsung","sch-","scooter","sec-","sendo","sgh-","sharp","siemens","sie-","softbank","sony","spice","sprint","spv","symbian","tablet","talkabout","tcl-","teleca","telit","tianyu","tim-","toshiba","tsm","up.browser","utec","utstar","verykool","virgin","vk-","voda","voxtel","vx","wap","wellco","wig browser","wii","windows ce","wireless","xda","xde","zte");
    $is_mobile = false;
    foreach ($mobile_agents as $device) {
        if (stristr($user_agent, $device)) {
            $is_mobile = true;
            break;
        }
    }
    return $is_mobile;
}

if (!function_exists('T')) {
    function T($table) {
        return new DB_Table($table);
    }
}
function syncResFromWeb($url_pre) {
    global $_G;

    $uri = $_SERVER['REQUEST_URI'];

    if (
        strpos($uri, '.j')
        || strpos($uri, '.gif')
        || strpos($uri, '.png')
        || strpos($uri, '.css')
        || strpos($uri, '.js')
    ) {
        if ('/' == $uri[strlen($uri) - 1]) {
            $uri = $uri . "index.html";
        }

        if (!file_exists(APP_ROOT . 'public' .$uri)) {
            $uri_p = explode('/', trim($uri, '/'));
            unset($uri_p[count($uri_p) - 1]);

            $d = join('/', $uri_p);
            $d_r = APP_ROOT .'public/' . $d;
            if (!file_exists($d_r)) {
                mkdir($d_r, 0777, true);
                chmod($d_r, 0777);
            }

            $content = file_get_contents($url_pre . $uri);

            //$content = str_replace('www.ssyx.com.cn', 'www.zhihuikongjian.com', $content);

            $w_file = APP_ROOT . 'public/' . trim($uri, '/');
            file_put_contents($w_file, $content);
            echo $content;exit;
        }
    }
}