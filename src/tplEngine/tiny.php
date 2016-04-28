<?php

class FView_Tiny {
    protected $tpl_parser;

    public function __construct() {
        $this->tpl_parser = new FTemplate(array('tpl_path_root' => F_APP_ROOT . 'tpl/','template_c' => 'data/template_c/'));
//        $this->tpl_parser->debug = 1;
    }

    public function assign($var, $value) {
        $this->tpl_parser->assign($var, $value);
    }

    public function display($tpl) {
        global $_F;

        $this->tpl_parser->display($tpl);
    }
}