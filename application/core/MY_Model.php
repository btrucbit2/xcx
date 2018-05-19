<?php

defined('BASEPATH') OR exit('No direct script access allowed');


class MY_Model extends CI_Model {

    public $_table = '';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * 添加一行数据
     */
    public function add($data)
    {
        return $this->db->insert($this->_table, $data, TRUE);
    }

    /**
     * 根据ID读取一条数据
     * @param int $id 数据ID
     */
    public function read($id)
    {
        $this->db->from($this->_table);
        $this->db->where('id', $id, TRUE);
        $result = $this->db->get();

        return $result->num_rows() ? $result->row_array() : NULL;
    }

    /**
     * 根据查询条件和排序方式查询一条数据
     * @param array     $where  查询条件
     * @param string    $order  排序方式
     */
    public function get_one($where, $order = NULL, $select = '*', $group = NULL)
    {
        if (empty($where)) {
            return NULL;
        }

        $this->db->select($select);
        $this->db->from($this->_table);
        $this->db->where($where, NULL, TRUE);
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group)) {
            $this->db->group_by($group);
        }
        $this->db->limit(1);
        $result = $this->db->get();

        return $result->num_rows() ? $result->row_array() : NULL;
    }

    /**
     * 根据查询条件获取列表数据
     * @param array     $where      查询条件
     * @param int       $pagesize   获取的数据大小
     * @param int       $offset     获取数据时的位置偏移量
     * @param string    $order      排序条件
     */
    public function get_list($where = NULL, $pagesize = 20, $offset = 0, $order = NULL, $select = '*', $group = NULL)
    {
        $this->db->select($select);
        $this->db->from($this->_table);

        if (!empty($where)) {
            $this->db->where($where, NULL, TRUE);
        }
        if ($pagesize > 0) {
            $this->db->limit($pagesize, $offset);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group)) {
            $this->db->group_by($group);
        }
        $result = $this->db->get();

        return $result->num_rows() ? $result->result_array() : [];
    }

    /**
     * 向表中插入一条数据
     * @param string $table 表名
     * @param array  $data  字段
     *
     * @return int| boolean 产生的ID|插入失败:false
     */

    public function insert_field($table = '', $data = [])
    {
        $result = false;
        if (empty($table)) {
            return $result;
        }
        if (!$this->db->table_exists($table)) {
            exit('table ' . $table . '  not exists');
        };
        $res = $this->db->insert($table, $data, TRUE);
        if ($res) {
            $result = $this->db->insert_id();
        }
        return $result;
    }

    /**
     * 更新表数据
     * @param string $table 表名
     * @param array  $data  字段
     * @param array  $where 条件
     *
     * @return int 受影响的记录数
     */
    public function update_field($table = '', $data = [], $where = [])
    {
        $result = false;
        if (empty($table) or empty($where) or ! is_array($where)) {
            return $result;
        }
        if (!$this->db->table_exists($table)) {
            exit('table ' . $table . '  not exists');
        }
        //2.构造条件
        foreach ($where as $key => $val) {
            switch (true) {
                //当条件中没有'op'时， 默认为 '=' 操作
                case is_array($val) === false:
                    $this->db->where($key, $val);
                    break;
                case $val['op'] == '=':
                case $val['op'] == '>=':
                case $val['op'] == '<=':
                    $this->db->where("{$val['key']} {$val['op']}", $val['value']);
                    break;
                case $val['op'] == 'in':
                case $val['op'] == 'IN':
                    $this->db->where_in($val['key'], $val['value']);
                    break;
                case $val['op'] == '!=':
                    $this->db->where_not_in($val['key'], $val['value']);
                    break;
                case $val['op'] == 'or':
                    $this->db->or_where($val['key'], $val['value']);
                    break;
                //TODO
                default :
                    break;
            }
        }
        $this->db->update($table, $data);
        return $this->db->affected_rows();
    }

    /**
     * 根据查询条件获取总数
     * @param array $where 查询条件
     */
    public function get_total($where = NULL, $select = '*', $distinct = FALSE)
    {
        $this->db->from($this->_table);
        if ($select) {
            $this->db->select($select);
        }
        if ($distinct) {
            $this->db->distinct($distinct);
        }

        if (!empty($where)) {
            $this->db->where($where, NULL, TRUE);
        }

        return $this->db->count_all_results();
    }

    /**
     * 更新一行记录
     * @param type $id          // 记录ID
     * @param type $data        // 更新数组
     */
    public function update($id, $data)
    {
        $this->db->where('id', $id, TRUE);
        $this->db->limit(1);
        $this->db->update($this->_table, $data);

        return $this->db->affected_rows();
    }

    /**
     * 删除一行记录
     * @param type $id          // 记录ID
     */
    public function delete_by_id($id)
    {
        $this->db->where('id', $id);
        $this->db->delete($this->_table);

        return $this->db->affected_rows();
    }

    /**
     * 获取当前条件查询的总记录数
     * @return mixed
     */
    public function get_select_total()
    {
        $limit_sql = $this->db->last_query();
        $sql = strstr($limit_sql, 'LIMIT', true);
        $res = $this->db->query($sql);
        return [
            'records' => $res->num_rows(),
            'count_sql' => $sql,
            'query_sql' => $limit_sql
        ];
    }

    /* ----------------------------------------------------------- 全局方法 ----------------------------------------------------------- */

    public function cs_get_one($select = '*', $filter = NULL, $order = NULL, $group_by = NULL)
    {
        $this->db->select($select);
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group_by)) {
            $this->db->group_by($group_by);
        }
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }

    public function cs_get_all($select = '*', $filter = NULL, $order = NULL, $group_by = NULL, $page_size = 0, $page = 1)
    {
        $this->db->select($select);
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        if (!empty($order)) {
            $this->db->order_by($order);
        }
        if (!empty($group_by)) {
            $this->db->group_by($group_by);
        }
        if (isset($page_size) && $page_size > 0) {
            $this->db->limit($page_size, ($page - 1) * $page_size);
        }
        return $this->db->get()->result_array();
    }

    //获取统计数量
    public function cs_get_count_one($filter = NULL, $count_select_column = '', $count_select_aliax = '')
    {
        if (empty($count_select_column)) {
            return false;
        }
        $count_select_aliax = !empty($count_select_aliax) ? $count_select_aliax : $count_select_column;
        $this->db->from($this->_table);
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        $this->db->select_sum($count_select_column, $count_select_aliax);
        $this->db->limit(1);
        return $this->db->get()->row_array();
    }


    public function cs_add($insert_data = [])
    {
        $this->db->insert($this->_table, $insert_data);
        return $this->db->insert_id();
    }


    public function cs_update($id, $filter = NULL, $post_data = [])
    {
        if (empty($id) && empty($filter)) {
            return false;
        }
        if (!empty($id)) {
            $this->db->where('id', $id, TRUE);
        }
        if (!empty($filter)) {
            $this->db->where($filter);
        }
        $this->db->update($this->_table, $post_data);
        return $this->db->affected_rows();
    }
}
