<?php

namespace Magein\Admin\View;

use Magein\Common\ApiResponse;
use Magein\Common\BaseModel;
use Magein\Common\MsgContainer;
use magein\tools\common\UnixTime;

class ViewData
{
    /**
     * @var string
     */
    protected $name = '';

    /**
     * @var string
     */
    protected $action = '';

    /**
     * @var BaseModel
     */
    protected static $model = null;

    /**
     * @var Page
     */
    protected static $page = null;

    /**
     * @param $name
     * @param $action
     */
    public function __construct($name, $action)
    {
        $this->name = $name;
        $this->action = $action;
    }

    public function response()
    {
        $mapping = config('view.page_mapping');
        try {
            $mapping = new $mapping();
        } catch (Exception $exception) {
            $mapping = new PageMapping();
        }

        if (!$mapping instanceof PageMapping) {
            $mapping = new PageMapping();
        }

        self::$page = $page = $mapping->page($this->name);

        if ($page === null) {
            return ApiResponse::error('尚未配置页面信息', ViewErrorCode::PAGE_MAPPING_FAIL);
        }

        // 验证权限
        if (!$page->auth()->verify($this->name, $this->action)) {
            return ApiResponse::error('尚未获得请求权限', ViewErrorCode::PAGE_FORBID);
        }

        try {
            $model = $page->model;
            self::$model = new $model();
        } catch (\Exception $exception) {
            return ApiResponse::error('数据模型不存在', ViewErrorCode::PAGE_MODEL_FAIL);
        }

        $action = $this->action;
        if (method_exists($this, $action)) {
            $result = $this->$action();
            return ApiResponse::auto($page->complete($result, $action));
        } else {
            return ApiResponse::error('不允许的行为', ViewErrorCode::PAGE_ACTION_FAIL);
        }
    }

    /**
     * 获取数据列表
     * @return array
     */
    protected function list(): array
    {
        $page_size = request()->input('page_size', 15);
        $trash = request()->input('_trash', 0);

        $model = $this->express(self::$page->search());

        if ($trash) {
            $model = $model->onlyTrashed();
        }

        $paginator = $model->with(self::$page->with())->orderBy('id', 'desc')->paginate($page_size);

        $items = $paginator->items();

        if ($items && self::$page->append()) {
            foreach ($items as $key => $item) {
                $item->append(self::$page->append());
                $items[$key] = $item;
            }
        }

        return [
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'items' => $items,
        ];
    }

    protected function get()
    {
        $id = request()->get('id');

        if (empty($id)) {
            return new MsgContainer('查询参数错误');
        }

        $record = self::$model->with(self::$page->with())->find($id);

        if ($record && self::$page->append()) {
            $record->append(self::$page->append());
        }

        return $record;
    }

    protected function columns()
    {
        $fields = self::$page->columns;
        if ($fields) {
            $fields = explode(',', $fields);
        } else {
            return [];
        }

        return self::$model->limit(200)->pluck($fields[0] ?? 'name', $fields[1] ?? 'id')->toArray();
    }

    /**
     * @return int|bool|MsgContainer
     */
    protected function post()
    {
        $data = self::$page->post();
        if (empty($data) || !is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $item) {
            if ($item === null) {
                unset($data[$key]);
            }
        }

        $model = self::$model;

        try {
            $model->fill($data);
            $result = $model->save();
        } catch (\Exception $exception) {
            if (preg_match('/1062 Duplicate entry/', $exception->getMessage())) {
                return new MsgContainer('请不要重复新增', 23000);
            } else {
                return new MsgContainer('数据列新增失败', 23000, [
                    'line' => $exception->getLine(),
                    'file' => $exception->getFile(),
                    'error' => $exception->getMessage()
                ]);
            }
        }

        return $result ? $model->id : false;
    }

    /**
     * @return int|bool|MsgContainer
     */
    protected function put()
    {
        $id = request()->input('id', 0);

        if (empty($id)) {
            return new MsgContainer('数据更新异常:not found key');
        }

        $data = self::$page->put();
        if (empty($data) || !is_array($data)) {
            return $data;
        }

        foreach ($data as $key => $item) {
            if ($item === null) {
                $data[$key] = '';
            }
        }

        $model = self::$model->find($id);
        if (empty($model)) {
            return new MsgContainer('数据列不存在，请刷新重试');
        }

        try {
            $model->fill($data);
            $result = $model->save();
        } catch (\Exception $exception) {
            return new MsgContainer('数据列更新失败', 23000, [
                'line' => $exception->getLine(),
                'file' => $exception->getFile(),
                'error' => $exception->getMessage()
            ]);
        }

        return $result ? $model->id : 0;
    }

    /**
     * @return int|bool|MsgContainer
     */
    protected function patch()
    {
        $id = request()->input('id', 0);

        if (empty($id)) {
            return new MsgContainer('数据更新异常:not found key');
        }

        $key = request()->input('key', '');
        $value = request()->input('value', '');

        if (!in_array($key, self::$page->patch())) {
            return new MsgContainer('不允许更新字段信息');
        }

        $model = self::$model->find($id);
        if (empty($model)) {
            return new MsgContainer('数据列不存在，请刷新重试');
        }

        try {
            $model->$key = $value;
            $result = $model->save();
        } catch (\Exception $exception) {
            return new MsgContainer('数据列更新失败', 23000, $exception->getMessage());
        }

        return $result ? $model->id : 0;
    }

    /**
     * 移动到回收站
     * @return int|MsgContainer
     */
    protected function trash()
    {
        $ids = request()->input('ids');

        if (empty($ids)) {
            return new MsgContainer('添加到回收站失败');
        }

        return self::$model->destroy($ids);
    }

    /**
     * 彻底删除
     * @return int|MsgContainer
     */
    protected function clean(): int
    {
        $ids = request()->input('ids');

        if (empty($ids)) {
            return new MsgContainer('数据删除失败');
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $number = 0;
        foreach ($ids as $id) {
            $model = clone self::$model;
            $record = $model->withTrashed()->find($id);
            if ($record && $record->trashed()) {
                $record->forceDelete();
                $number++;
            }
        }

        return $number;
    }

    /**
     * 恢复数据
     * 这里使用了patch 用户更新一个字段，即delete_time从有值变成0
     * @return int|MsgContainer
     */
    protected function recovery()
    {
        $ids = request()->input('ids');

        if (empty($ids)) {
            return new MsgContainer('数据列不存在，请刷新重试');
        }

        if (!is_array($ids)) {
            $ids = [$ids];
        }

        $number = 0;
        foreach ($ids as $id) {
            $model = clone self::$model;
            $record = $model->withTrashed()->find($id);
            if ($record && $record->trashed()) {
                $record->restore();
                $number++;
            }
        }

        return $number;
    }

    /**
     * @param array $search
     * @return mixed
     */
    public function express(array $search)
    {
        $params = request()->input();

        $is_empty = function ($value) {
            if ($value === null || $value === '') {
                return true;
            }
            return false;
        };

        $model = self::$model;

        foreach ($search as $item) {
            if (is_string($item)) {
                $item = explode('|', $item);
            }

            $field = (string)($item[0] ?? '');
            $express = (string)($item[1] ?? '');
            $value = $item[2] ?? '';
            if ($field === 'region') {
                $express = 'region';
            }
            $value = !$is_empty($value) ? $value : ($params[$field] ?? '');

            if (is_array($value)) {
                $express = 'in';
            } else {
                $value = trim($value);
                if ($is_empty($value)) {
                    continue;
                }
            }

            switch ($express) {
                case 'like':
                    $model = $model->where($field, 'like', '%' . $value . '%');
                    break;
                case 'between':
                    if (is_array($value)) {
                        $model = $model->whereBetween($field, $value);
                    }
                    break;
                case 'in':
                    if (is_array($value)) {
                        $model = $model->whereIn($field, $value);
                    }
                    break;
                case 'date':
                    // 匹配日期范围 [2021-07-17,2021-07-18]
                    if (is_array($item)) {
                        $start_time = UnixTime::instance()->begin($item[0]);
                        $end_time = UnixTime::instance()->end($item[1]);
                        $model = $model->whereBetween($field, [$start_time, $end_time]);
                    } else {
                        $model = $model->where($field, UnixTime::instance()->unix($value));
                    }
                    break;
                // 2021-07-17 22:00:00 匹配成等于
                // 2021-07-17 22:00:00,2021-07-17 22:00:00 匹配成范围
                case 'datetime':
                    if (is_array($item)) {
                        $start_time = UnixTime::instance()->unix($item[0]);
                        $end_time = UnixTime::instance()->unix($item[1]);
                        $model = $model->whereBetween($field, [$start_time, $end_time]);
                    } else {
                        $model = $model->where($field, UnixTime::instance()->unix($value));
                    }
                    break;
                case 'region':
                    $len = count($value);
                    if ($len == 1) {
                        $key = 'province_id';
                        $val = $value[0];
                    } elseif ($len == 2) {
                        $key = 'city_id';
                        $val = $value[1];
                    } elseif ($len == 3) {
                        $key = 'area_id';
                        $val = $value[2];
                    }
                    if (isset($key) && isset($val)) {
                        $model = $model->where($key, $val);
                    }
                    break;
                case 'eq':
                case '=':
                default:
                    $model = $model->where($field, $value);
            }
        }

        return $model;
    }
}
