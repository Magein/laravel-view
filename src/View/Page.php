<?php

namespace Magein\Admin\View;

use Magein\Common\MsgContainer;
use Illuminate\Support\Facades\Validator;

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
     * @return array
     */

    public function search(array $params = []): array
    {
        return array_merge($this->search, $params);
    }

    /**
     * restful处理完成后的回调
     * @param $result
     * @param $action
     * @return mixed
     */

    public function complete($result, $action)
    {
        return $result;
    }

    protected function validate($data = [], array $rules = [], array $message = [])
    {
        $data = $data ?: request()->only($this->fillable());
        $rules = $rules ?: $this->rules();
        $message = $message ?: $this->message();

        if (empty($data)) {
            return MsgContainer::msg('发生错误', ViewErrorCode::REQUEST_DATA_IS_NULL);
        }
        if (empty($rules)) {
            return MsgContainer::msg('数据验证规则尚未设置', ViewErrorCode::REQUEST_DATA_VALIDATE_RULES_IS_NULL);
        }

        $validator = Validator::make($data, $rules, $message);

        if ($validator->fails()) {
            return MsgContainer::msg($validator->errors()->first(), ViewErrorCode::REQUEST_DATA_VALIDATE_FAIL);
        }

        return $data;
    }

    /**
     * @param array $data
     * @param array $rule
     * @param array $message
     * @return array|MsgContainer
     */

    public function create(array $data = [], array $rule = [], array $message = [])
    {
        return $this->validate($data, $rule, $message);
    }

    /**
     * @param array $data
     * @param array $rule
     * @param array $message
     * @return array|MsgContainer
     */

    public function edit(array $data = [], array $rule = [], array $message = [])
    {
        return $this->validate($data, $rule, $message);
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
