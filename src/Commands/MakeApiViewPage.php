<?php

namespace Magein\Admin\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use magein\tools\common\Variable;

class MakeApiViewPage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'map {name} {--M|model=} {--F|fillable} {--T|table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '创建view page安全参数 -T、--table 查询的表 -M、--model 可以指定使用的模型 ，-F、--fillable 只显示模型的fillable值';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $name = $this->argument('name');
        $model = $this->option('model');
        $table = $this->option('table');
        $fillable = $this->option('fillable');

        if (empty($model)) {
            $model = $name;
        }

        $model = Variable::instance()->pascal($model);

        if (preg_match('/y$/', $name)) {
            $table = preg_replace('/y$/', 'ies', $name);
        } elseif (!preg_match('/s$/', $name)) {
            $table = $name . 's';
        } else {
            $table = $name;
        }

        var_dump(123);
        die();

        $page_path = config('view.page_path');
        $page_name = Variable::instance()->pascal($name) . 'Page.php';
        $filename = $page_path . '/' . $page_name;

        if (is_file($filename) && !$fillable) {
            $this->info('error:file exist');
            $this->info('maybe you want get fillable fields, please input -F');
            exit();
        }

        $fields = DB::select('show full columns from ' . $table);
        if (empty($fields)) {
            $this->info('error: table name not found ! please check you table name');
            exit();
        }

        $validate_rules = "[\n";
        $validate_message = "[\n";
        $fill = "protected \$fillable = [\n";
        foreach ($fields as $item) {

            $field = Variable::instance()->underline($item->Field);
            $types = $item->Type;

            $comment = $item->Comment;
            if (in_array($field, ['id', 'money', 'balance', 'score', 'integral', 'created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            $fill .= "          '$field',\n";

            $comment = explode(' ', $comment);
            $comment = $comment[0] ?? $field;

            $rules = ['bail', 'required'];

            preg_match('/([a-zA-Z_]+)\(([0-9]+)/', $types, $matches);
            if (count($matches) == 3) {
                $type = $matches[1];
                $max = $matches[2] ?? 0;
            } else {
                $type = $types;
                $max = 0;
            }

            if (in_array($field, ['province_id', 'city_id', 'area_id'])) {
                $rules[] = "string";
                $rules[] = "size:6";
            } elseif ($field === 'phone') {
                $rules[] = "string";
                $rules[] = "size:11";
            } elseif (in_array($type, ['int', 'tinyint', 'smallint', 'mediumint', 'integer', 'bigint'])) {
                $rules[] = 'integer';
            } elseif (in_array($type, ['char', 'varchar'])) {
                $rules[] = "string";
                $rules[] = "max:$max";
            } elseif ($type == 'decimal') {
                $rules[] = "numeric";
                $rules[] = "max:$max";
            }

            if ($rules) {
                foreach ($rules as $rule) {

                    if ($rule === 'bail') {
                        continue;
                    }
                    $message = '';
                    if ($rule === 'required') {
                        $message = "不能为空";
                    } elseif ($rule === 'integer') {
                        $message = "需要一个整数";
                    } elseif ($rule === 'string') {
                        $message = "需要一个字符串";
                    } elseif ($rule === 'numeric') {
                        $message = "需要一个数字";
                    } elseif (preg_match('/^max/', $rule)) {
                        $message = "最大长度为" . substr($rule, 4);
                        $rule = 'max';
                    } elseif (preg_match('/^size/', $rule)) {
                        $message = "限定长度为" . substr($rule, 5) . '个字符';
                        $rule = 'size';
                    }

                    if (empty($message)) {
                        continue;
                    }

                    $validate_message .= "          '$field.$rule' => '{$comment}{$message}',\n";
                }
            }
            $validate_rules .= "             '$field' => '" . implode('|', $rules) . "',\n";
        }

        $validate_rules .= "            ]";
        $validate_message .= "          ]";
        $fill .= "        ];";


        if ($fillable) {
            echo $fill;
            exit();
        }

        var_dump(__DIR__);
        die();

//        $template = file_get_contents(__DIR__ . '/../ViewPageTemplate.php');
//
//        echo $template;
//        die();


        $page = <<<EOF
<?php

namespace $page_path;

use Magein\Admin\View\Page;
use App\Models\\$model;

class {$page_name} extends Page
{

    public \$model = $model::class;
    /**
     * @return array
     */
    public \$rules = {$validate_rules};

    /**
     * @return array
     */
    public \$message = {$validate_message};
}


EOF;

        file_put_contents($filename, $page);

    }
}
