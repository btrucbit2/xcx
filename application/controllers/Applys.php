<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Applys extends MY_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('token');
        $this->load->model('users');
        $this->load->model('apply');
    }

    /**
     * 申请信用卡
     */
    public function add()
    {
        $userame = '222'.rand(1, 100000);
        $type = 'ta';
        $address = '22222';
        $mobile = '3333';
        $token = $_GET['token'];

        $token_info = $this->token->get_one(['token' => $token], 'created_at desc');
        if(!$token_info || $token!=$token_info['token'] || $token_info['expire_at'] < date('Y-m-d H:i:s', time())){
            $this->returnError('未登录', 401);
            exit();
        }

        $this->apply->add([
            'uid' => $token_info['uid'],
            'username' => $userame,
            'type' => $type,
            'address' => $address,
            'mobile' => $mobile,
            'created_at' => date('Y-m-d H:i:s', time()),
        ]);

        $this->return_data(['result' => 1]);
    }
}
