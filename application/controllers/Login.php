<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Login extends CI_Controller {

	public function index()
	{
        $appid = 'd761bb5a6c5d931500bf7e6b8d3d85cc
';
        $secret = 'wx0ca8054103d57ed4';
        $code = $_GET['code'];
        $url = 'https://api.weixin.qq.com/sns/jscode2session?appid='.$appid.'&secret='.$secret.'&js_code='.$code.'&grant_type=authorization_code';
        $response = file_get_contents($url);
        return $response;
	}
}
