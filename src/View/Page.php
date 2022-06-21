<?php

namespace Magein\Admin\View;

use Illuminate\Support\Facades\Validator;
use Magein\Common\Output;

class Page
{
    /**
     * 使用的模型
     * @var string|null
     */
    public $model = null;

    /**
     * 权限标题
     * @var string
     */
    public $auth = '';

    /**
     * 获取标题，姓名，等单行数据
     * @var string
     */
    public $columns = '';

    /**
     * 保存数据使用的规则
     * @var array
     */
    public $rules = [];

    /**
     * 规则信息
     * @var array
     */
    public $message = [];

    /**
     * 筛选字段
     * @var array
     */
    public $search = [];

    /**
     * 预加载
     * @var array
     */
    public $with = [];

    /**
     * 查询追加字段
     * @var array
     */
    public $append = [];

    /**
     * 允许批量赋值的字段
     * @var array
     */
    public $fillable = [];

    /**
     * 对外暴露的字段 默认会过滤掉
     * @var array
     */
    public $fields = [];

    /**
     * 数据结构的字段
     * @var array
     */
    public $tree = [];

    /**
     * @param string $model
     */

    public function __construct(string $model = '')
    {
        if ($model) {
            $this->model = $model;
        }
    }

    /**
     * @return PageAuth
     */

    public function auth(): PageAuth
    {
        if ($this->auth === null) {
            return new PageAuth('', '', null);
        }
        return new PageAuth(preg_replace('/Page$/', '', class_basename(static::class)), $this->auth);
    }

    /**
     * 用方法控制可以根据逻辑返回，具备一定的扩展性
     * @return array
     */

    public function with(): array
    {
        return $this->with;
    }

    /**
     * @return array
     */

    public function append(): array
    {
        return $this->append;
    }

    /**
     * @return array
     */

    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * 允许修改的字段信息
     * @return array
     */
    protected function fillable(): array
    {
        $model = $this->model;
        if (empty($model) || !is_string($model)) {
            return [];
        }
        try {
            $fields = (new $model())->getFillable();
        } catch (\Exception $exception) {
            $fields = [];
        }
        return $fields;
    }

    /**
     * @return array
     */

    public function rules(): array
    {
        return $this->rules;
    }

    /**
     * @return array
     */
    public function message(): array
    {
        return $this->message;
    }

    public function columns()
    {
        $model = $this->model;
        if (!$this->columns) {
            return [];
        }
        return (new $model())->limit(200)->pluck($this->columns, 'id')->toArray();
    }

    protected function getTree($page_size = 15, $parent_id = 0, &$result = [])
    {
        $model = $this->model;
        $model = new $model();
        if ($parent_id == 0) {
            $records = $model->where('parent_id', $parent_id)->paginate($page_size);
        } else {
            $records = $model->where('parent_id', $parent_id)->get();
        }

        if ($records->isNotEmpty()) {
            foreach ($records as $item) {
                $result[$item['id']] = $item->toArray();
                $this->getTree($page_size, $item['id'], $result);
            }
        }
        return $result;
    }

    public function tree($page_size = 15)
    {
        $data = $this->getTree($page_size);
        $result = [];
        if ($data) {
            foreach ($data as $key => $item) {
                $id = $item['id'];
                $parent_id = $item['parent_id'];
                if (isset($data[$parent_id])) {
                    $data[$parent_id]['children'][] = &$data[$key];
                } else {
                    $result[] = &$data[$key];
                }
            }
        }
        return $result;
    }

    /**
     *
     * 例如
     *$example = [
     * '字段',
     * [ '字段', '表达式,为空表示等于', '值' ],
     * [ 'status', '', '1' ],
     * [ 'status', 'gt', '1' ],
     * [ 'name', 'like' ],
     * [ 'name', 'like', 'jak' ],
     * 'name|like',
     * 'name|like|jak',
     * ];
     * @param array $params
     * @return array
     */
    public function search(array $params = []): array
    {
        return array_merge($this->search, $params);
    }

    /**
     * 处理完成后的回调
     * @param Output $output
     * @param string $action
     * @return Output
     */
    public function complete(Output $output, string $action): Output
    {
        return $output;
    }

    /**
     * @param array $data
     * @param array $rules
     * @param array $message
     * @return \Magein\Common\Output
     */
    protected function validate(array $data = [], array $rules = [], array $message = []): Output
    {
        $rules = $rules ?: $this->rules();
        $message = $message ?: $this->message();

        if (empty($data)) {
            return Output::error('发生错误', ViewErrorCode::REQUEST_DATA_IS_NULL);
        }
        if (empty($rules)) {
            return Output::error('数据验证规则尚未设置', ViewErrorCode::REQUEST_DATA_VALIDATE_RULES_IS_NULL);
        }

        $validator = Validator::make($data, $rules, $message);
        if ($validator->fails()) {
            return Output::error($validator->errors()->first(), ViewErrorCode::REQUEST_DATA_VALIDATE_FAIL);
        }

        return Output::success($data);
    }

    /**
     * @return array
     */
    protected function transPostData(): array
    {
        return request()->only($this->fillable());
    }

    /**
     * @param array $data
     * @param array $rule
     * @param array $message
     * @return \Magein\Common\Output
     */
    public function create(array $data = [], array $rule = [], array $message = []): Output
    {
        return $this->validate($data ?: $this->transPostData(), $rule, $message);
    }

    /**
     * @param array $data
     * @param array $rule
     * @param array $message
     * @return \Magein\Common\Output
     */
    public function edit(array $data = [], array $rule = [], array $message = []): Output
    {
        return $this->validate($data ?: $this->transPostData(), $rule, $message);
    }

    /**
     * @return string[]
     */
    public function update(): array
    {
        return [
            'status',
            'sort'
        ];
    }
}
