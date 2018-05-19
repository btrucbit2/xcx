<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller
{

    public function __construct()
    {
        parent::__construct();
        $this->load->model('token');
        $this->load->model('users');
    }

    public function index()
    {
        $secret = 'd761bb5a6c5d931500bf7e6b8d3d85cc';
        $appid = 'wx0ca8054103d57ed4';
        $code = $_GET['code'];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid=' . $appid . '&secret=' . $secret . '&js_code=' . $code . '&grant_type=authorization_code';
        $response = file_get_contents($url);
        if ($response) {
            $res = json_decode($response, true);
            $openid = $res['openid'];
            $session_key = $res['session_key'];
            $user_info = $this->users->get_one(['openid' => $openid]);
            if ($user_info) {
                $uid = $user_info['uid'];
                $this->users->insert([
                    'openid' => $openid,
                    'created_at' => date('Y-m-d H:i:s', time()),
                    'uid' => $this->string_make_guid(),
                ]);
            }
            $token = md5($openid . $session_key);

            $this->token->insert([
                'uid' => $uid,
                'openid' => $openid,
                'token' => $token,
                'created_at' => date('Y-m-d H:i:s', time()),
                'expire_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
        }
        echo json_encode(['token' => $token, 'uid' => $uid]);
    }

    /**
     * 生成GUID
     */
    function string_make_guid()
    {
        // 1、去掉中间的“-”，长度有36变为32
        // 2、字母由“大写”改为“小写”
        if (function_exists('com_create_guid') === true) {
            return strtolower(str_replace('-', '', trim(com_create_guid(), '{}')));
        }

        return sprintf('%04x%04x%04x%04x%04x%04x%04x%04x', mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(16384, 20479), mt_rand(32768, 49151), mt_rand(0, 65535), mt_rand(0, 65535), mt_rand(0, 65535));
    }
}
