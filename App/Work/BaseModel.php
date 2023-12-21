<?php
/**
 * @desc
 * @author     文明<wenming@ecgtool.com>
 * @date       2021-11-18 15:53
 */
namespace App\Work;

use EasySwoole\ORM\AbstractModel;

/**
 * 用户天赋模型
 */
class BaseModel extends AbstractModel
{

    public function getList($params, $parentParams)
    {
        $page = !empty($parentParams['page']) ? $parentParams['page'] : 1;
        $size = !empty($parentParams['page_size']) ? $parentParams['page_size'] : 10;
        $model = $this->_clone();
        if (!empty($params['order'])) {
            if (is_string($params['order'])) {
                $order = explode(' ', $params['order']);
                $model->order($order[0], $order[1]);
            } else if (is_array($params['order'])) {
                foreach ($params['order'] as $order) {
                    $order = explode(' ', $order);
                    $model->order($order[0], $order[1]);
                }
            }
        }
        $delArr = ['page', 'page_size', 'order'];
        foreach ($delArr as $key) {
            unset($params[$key]);
        }
        $where = $params;

        foreach ($where as $field => $item) {
            if ($item === '' || $item === null) {
                continue;
            }

            // 可以执行 sql，如 (id > 10 or id <2)
            if ($field == 'sql') {
                if (is_array($item)) {
                    foreach ($item as $sql) {
                        $model->where(' (' . $sql . ') ');
                    }
                } else {
                    $model->where(' (' . $item . ') ');
                }
                continue;
            }

            // 解析出操作符 operator (IN, NOT IN, LIKE 等)
            $fields = explode(' ', trim($field));
            $field = $fields[0];
            if (count($fields) == 1) {
                $operator = '=';
            } else {
                array_shift($fields);
                $operator = implode(' ', $fields);
            }

            // 数组形式
            if (is_array($item)) {
                $model->where($field, $item, $operator);
            } else {
                $model->where($field, $item, $operator);
            }
        }


        $res = $model->page($page, $size)->all()->toRawArray();
        $count = $model->count();
        $data = [
            'list' => $res,
            'count' => $count,
            'page' => $page,
            'size' => $size
        ];
        return $data;
    }
}