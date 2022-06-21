<?php

namespace Magein\Admin\View;

use Magein\Common\ApiResponse;
use Magein\Common\BaseModel;
use Magein\Common\MsgContainer;
use Magein\Common\Output;
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
        } catch (\Exception $exception) {
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
            return ApiResponse::error('尚未获得 ' . $this->name . '/' . $this->action . '的请求权限', ViewErrorCode::PAGE_FORBID);
        }

        try {
            $model = $page->model;
            self::$model = new $model();
        } catch (\Exception $exception) {
            return ApiResponse::error('数据模型不存在', ViewErrorCode::PAGE_MODEL_FAIL);
        }

        $action = $this->action;
        if (method_exists($this, $action)) {
            $output = $this->$action();
            return ApiResponse::auto($page->complete($output, $action));
        } else {
            return ApiResponse::error('不允许的行为', ViewErrorCode::PAGE_ACTION_FAIL);
        }
    }

    /**
     * 获取数据列表
     * @return Output
     */
    protected function list(): Output
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

        return Output::success([
            'current_page' => $paginator->currentPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'items' => $items,
        ]);
    }

    /**
     * @return \Magein\Common\Output
     */
    protected function tree(): Output
    {
        $no_page = request()->input('no_page', 0);

        $records = self::$page->tree();
        if ($no_page) {
            return Output::success($records);
        }

        return Output::success([
            'current_page' => 1,
            'per_page' => 0,
            'total' => 0,
            'items' => $records,
        ]);
    }

    protected function columns()
    {
        return self::$page->columns();
    }

    /**
     * @return \Magein\Common\Output
     */
    protected function get(): Output
    {
        $id = request()->input('id', 0);

        if (empty($id)) {
            return new Output('查询参数错误');
        }

        $record = self::$model->with(self::$page->with())->find($id);

        if ($record && self::$page->append()) {
            $record->append(self::$page->append());
        }

        return new Output($record);
    }

    /**
     * @return \Magein\Common\Output
     */
    protected function create(): Output
    {
        $output = self::$page->create();
        $data = $output->getData();
        if (empty($data) || !is_array($data)) {
            return $output;
        }

        foreach ($data as $key => $item) {
            if ($item === null) {
                $data[$key] = '';
            }
        }

        $model = self::$model;

        try {
            $model->fill($data);
            $model->save();
        } catch (\Exception $exception) {
            if (preg_match('/1062 Duplicate entry/', $exception->getMessage())) {
                $message = '请不要重复新增';
            } else {
                $message = '数据列新增失败';
            }

            (new ViewLog())->write([
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
            ]);

            return Output::error($message, 23000);
        }

        return Output::success(($model->id ?? 0) ?: false);
    }

    /**
     * @return \Magein\Common\Output
     */
    protected function edit(): Output
    {
        $id = request()->input('id', 0);

        if (empty($id)) {
            return new Output('更新参数错误');
        }

        $output = self::$page->edit();

        $data = $output->getData();
        if (empty($data) || !is_array($data)) {
            return $output;
        }

        foreach ($data as $key => $item) {
            if ($item === null) {
                $data[$key] = '';
            }
        }

        $model = self::$model->find($id);
        if (empty($model)) {
            return new Output('数据列不存在，请刷新重试');
        }

        try {
            $model->fill($data);
            $result = $model->save();
        } catch (\Exception $exception) {
            $index = (new ViewLog())->write([
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
            ]);
            $message = '数据列更新失败';
            if ($index) {
                $message = $message . ':' . $index;
            }
            return Output::error($message, 23000);
        }

        return Output::success($result ? $model->id : 0);
    }

    /**
     * @return \Magein\Common\Output
     */
    protected function update(): Output
    {
        $id = request()->input('id', 0);

        if (empty($id)) {
            return new Output('更新参数错误');
        }

        $key = request()->input('key', '');
        $value = request()->input('value', '');

        if (!in_array($key, self::$page->update())) {
            return new Output('不允许更新字段信息');
        }

        $model = self::$model->find($id);
        if (empty($model)) {
            return new Output('数据列不存在，请刷新重试');
        }

        try {
            $model->$key = $value;
            $result = $model->save();
        } catch (\Exception $exception) {
            $index = (new ViewLog())->write([
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'message' => $exception->getMessage(),
            ]);
            $message = '数据列更新失败';
            if ($index) {
                $message = $message . ':' . $index;
            }
            return Output::error($message, 23000);
        }

        return Output::success($result ? $model->id : 0);
    }

    /**
     * 移动到回收站
     * @return Output
     */
    protected function delete(): Output
    {
        $ids = request()->input('ids');

        if (empty($ids)) {
            return new Output('添加到回收站失败');
        }

        return Output::success(self::$model->destroy($ids));
    }

    /**
     * 彻底删除
     * @return Output
     */
    protected function clean(): Output
    {
        $ids = request()->input('ids');

        if (empty($ids)) {
            return new Output('数据删除失败');
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

        return Output::success($number);
    }

    /**
     * 恢复数据
     * @return Output
     */
    protected function restore(): Output
    {
        $ids = request()->input('ids');

        if (empty($ids)) {
            return new Output('数据列不存在，请刷新重试');
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

        return Output::success($number);
    }

    /**
     * @param array $search
     * @return \Magein\Common\BaseModel
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
            $field = ( string )($item[0] ?? '');
            $express = ( string )($item[1] ?? '');
            $value = $item[2] ?? '';
            if ($field === 'region') {
                $express = 'region';
            }
            $value = !$is_empty($value) ? $value : ($params[$field] ?? '');
            if (is_array($value)) {
                $express = $express === 'region' ? 'region' : 'in';
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
                    // 匹配日期范围 [ 2021-07-17, 2021-07-18 ]
                    if (is_array($item)) {
                        $start_time = UnixTime::instance()->begin($item[0]);
                        $end_time = UnixTime::instance()->end($item[1]);
                        $model = $model->whereBetween($field, [$start_time, $end_time]);
                    } else {
                        $model = $model->where($field, UnixTime::instance()->unix($value));
                    }
                    break;
                // 2021-07-17 22:00:00 匹配成等于
                // 2021-07-17 22:00:00, 2021-07-17 22:00:00 匹配成范围
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
