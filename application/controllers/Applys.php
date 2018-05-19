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
        $uid = $_POST['uid'];
        $userame = $_POST['username'];
        $type = $_POST['type'];
        $address = $_POST['address'];
        $mobile = $_POST['mobile'];
        $token = $_POST['token'];

        $token_info = $this->token->get_one(['uid' => $uid], 'created_at desc');
        if(!$token_info || $token_info['expire_at'] < date('Y-m-d H:i:s', time())){
            $this->returnError('未登录', 401);
            exit();
        }

        $this->apply->insert([
            'uid' => $uid,
            'userame' => $userame,
            'type' => $type,
            'address' => $address,
            'mobile' => $mobile,
            'created_at' => date('Y-m-d H:i:s', time()),
        ]);

        $this->return_data(['result' => 1]);
    }
}
