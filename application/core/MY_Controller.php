<?php

/**
 * api基础类
 *
 * @version    1.0.0
 * @since	1.0
 * */
defined('BASEPATH') OR exit('No direct script access allowed');


/**
 * 所有API的基类
 *
 * @author     william
 * @since	1.0
 */
class MY_Controller extends CI_Controller {

    public $model = NULL;

    function __construct()
    {
		header("Access-Control-Allow-Origin: *");
        parent::__construct();
        //全局路径
        define('APP_URL', $this->config->item('url_domain'));
        define('UPLOAD_URL', APP_URL . 'statics/uploads/');
    }




    /**
     * 统一API参数检验方法
     *
     * 调用示例 check_param(array('money' => array('required', 'integer', 'greater_than_equal_to[1]', 'less_than_equal_to[200]')));
     *
     * @access public
     * @param array $arr
     * @since 1.0
     * @return  boolean
     */
    public function check_param($arr, $data = array(), $method = 'get')
    {
        /**
         * 设置要验证的请求数据
         */
        if (!empty($arr)) {
            $key_arr = array();
            $rule_arr = array();
            foreach ($arr as $key => $value) {
                $temp_arr = explode(",", $key);
                if (!is_array($value)) {
                    $value = explode("|", $value);
                }
                $key_arr = array_merge($key_arr, $temp_arr);
                if (!empty($temp_arr)) {
                    foreach ($temp_arr as $temp_value) {
                        if (!empty($rule_arr[$temp_value])) {
                            $rule_arr[$temp_value] = array_merge($rule_arr[$temp_value], $value);
                        } else {
                            $rule_arr[$temp_value] = $value;
                        }
                    }
                }
            }
            $key_arr = array_unique($key_arr);
            if (!empty($rule_arr)) {
                foreach ($rule_arr as $rule_key => $rule_value) {
                    $rule_arr[$rule_key] = array_unique($rule_value);
                }
            }
        }
        if ($method === 'post' || $method === 'POST') {
            $request_data = $this->input->post($key_arr, TRUE);
        } else {
            $request_data = $this->input->get($key_arr, TRUE);
        }

        if ('get_post' == $method) {
            $request_data = [];
            foreach ($key_arr as $one_key) {
                $request_data[$one_key] = $this->input->get_post($one_key, TRUE);
            }
        }

        $raw_input_stream = $this->input->raw_input_stream;
//        $raw_input_stream = stripslashes($raw_input_stream);

        $input_stream = json_decode($raw_input_stream, TRUE);
        if (json_last_error() === JSON_ERROR_NONE) {
            foreach ($key_arr as $one_key) {
                if (NULL === $request_data[$one_key] and isset($input_stream[$one_key])) {
                    $request_data[$one_key] = $input_stream[$one_key];
                }
            }
        }

        $this->form_validation->set_data($request_data);
        /**
         * 设置验证规则
         */
        if (!empty($rule_arr)) {
            $lang = $this->lang->line('txt_base_validation');
            foreach ($rule_arr as $rule_key => $rule_value) {
                $this->form_validation->set_rules($rule_key, '', $rule_value, array('required' => $lang[0]
                    , 'integer' => $lang[1]
                    , 'regex_match' => $lang[2]
                    , 'greater_than' => $lang[3]
                    , 'max_length' => $lang[4]
                    , 'min_length' => $lang[5]
                ));
            }
        }
        /**
         * 开始验证
         */
        if (!$this->form_validation->run()) {
            //验证失败处理逻辑            
            $errmsg = validation_errors(' ', ' ');
            if (!empty($arr) && !empty($data)) {
                foreach ($arr as $arr_key => $arr_value) {
                    $errmsg = str_replace($arr_key, $data[$arr_key], $errmsg);
                }
            }
            $this->returnError($errmsg . $this->lang->line('txt_base_error_msg'), '400');
            return FALSE;
        }
        return $request_data;
    }

    /**
     * api返回数据缓存
     *
     * @access public
     * @param array $data 返回数组
     * @param int $cache_time 缓存时间
     * @param string $msg 提示信息
     * @param int $code 返回码
     * @since 1.0
     * @return  json
     */
    public function returnDataCache($data, $cache_time = 60, $msg = '', $code = self::HTTP_OK)
    {
        $method = $this->input->method();
        $key = md5(uri_string() . current_url() . implode('', $this->input->$method()));
        $this->load->driver('cache', array('adapter' => 'redis', 'backup' => 'file'));
        $cache_data = [
            'data' => $data,
            'msg' => $msg,
            'code' => $code,
            'return_fun' => 'returnData',
        ];
        $this->cache->save($key, serialize($cache_data), $cache_time);
        $this->returnData($data, $msg, $code);
    }

    /**
     * API返回列表数据格式
     *
     * @access public
     * @param array $data 返回数组
     * @param string $msg 提示信息
     * @param int $code 返回码
     * @since 1.0
     * @return  json
     */
    public function returnListData($data, $msg = '', $code = self::HTTP_OK, $continue = TRUE)
    {
        $empty_data = [
            'page' => '1',
            'records' => '0',
            'rows' => [],
            'total' => '0',
            'query_sql' => '',
            'count_sql' => '',
            'querytime' => '',
            'counttime' => '',
        ];
        // 将数据填入空数组
        foreach ((array) $data as $key => $val) {
            $empty_data[$key] = $val;
        }
        $ret_data = array(
            'code' => $code . '',
            'msg' => $msg,
            'data' => $empty_data,
        );
        $http_code = $code; //新的api让code跟http_code保持一致
        set_data_string($ret_data);
        $this->response($ret_data, $http_code, $continue);
    }

    /**
     * API返回列表数据格式
     *
     * @access public
     * @param array $data 返回数组
     * @param string $msg 提示信息
     * @param int $code 返回码
     * @since 1.0
     * @return  json
     */
    public function returnListDataCache($data, $cache_time = 60, $msg = '', $code = self::HTTP_OK)
    {

        $method = $this->input->method();
        $key = md5(uri_string() . current_url() . implode('', $this->input->$method()));
        $this->load->driver('cache', array('adapter' => 'redis', 'backup' => 'file'));
        $cache_data = [
            'data' => $data,
            'msg' => $msg,
            'code' => $code,
            'return_fun' => 'returnListData',
        ];
        $this->cache->save($key, serialize($cache_data), $cache_time);
        $this->returnListData($data, $msg, $code);
    }

    /**
     * 管理后台数据返回API
     *
     * @access public
     * @param array $data 返回数组
     * @param string $msg 提示信息
     * @param int $code 返回码
     * @since 1.0
     * @return  json
     * @author Allen
     */
    public function return_data($data, $msg = '', $code = '200')
    {
        $ret_data = array(
            'code' => $code . '',
            'msg' => $msg,
            'data' => $data,
        );
        $http_code = $code; //新的api让code跟http_code保持一致
        echo json_encode($ret_data);
    }

    /**
     * 获取数据失败提示
     *
     * @access public
     * @param string $msg 提示信息
     * @param int $code 返回码
     * @since 1.0
     * @return  json
     */
    public function returnError($msg = '', $code = '400')
    {
        echo json_encode(['code' => $code . '', 'msg' => $msg, 'data' => []]);
//        $this->returnData(array(), $msg, $code);
    }

    /**
     * 获取数据失败提示2(上一个方法提供数据给Android有时候会返回异常数据)
     *
     * @access public
     * @param string $msg 提示信息
     * @param int $code 返回码
     * @since 1.0
     * @return  json
     */
    public function returnError2($msg = '', $code = '500')
    {
//        $this->output->set_status_header($code);
        echo json_encode(['code' => $code . '', 'msg' => $msg, 'data' => []]);
        exit;
    }

    /*
     * 根据uid 查看是否有这个uid或者是否过期
     */
    public function is_uid($uid,$type = ''){
        $this->load->model('user');
        $this->load->model('access_token');
        $is_user = $this->user->get_one(['uid'=>$uid]);
        if($is_user){
            $is_access_token = $this->access_token->get_one(['uid'=>$uid],' expire_time desc');
            if($is_access_token){
                if($is_access_token['expire_time'] < time()){
                    return -1;
                }else{
                    $type = $type ? $type : $is_user['type'];
                    if($is_user && ($type == 1) && (!$is_user['mobile'])){
                        return -2;
                    }
                    return 1;
                }
            }
        }else{
            return -1;
        }
    }
}
