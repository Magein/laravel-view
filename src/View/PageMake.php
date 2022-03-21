<?php

namespace Magein\Admin\View;

use Illuminate\Support\Facades\DB;
use magein\tools\common\Variable;

/**
 * 构建vue页面，这里仅仅是一个生成前段的脚本代码
 */
class PageMake
{
    public function vue()
    {
        $table = request()->input('table');
        $name = request()->input('name', '');
        if (empty($table)) {
            dd('请输入表名称');
        }

        if (empty($name)) {
            $name = Variable::instance()->camelCase($table);
            if (preg_match('/s$/', $name)) {
                $name = substr($name, 0, -1);
            }
        }
        $fields = DB::select('show full columns from ' . Variable::instance()->underline($table));

        $dictionary = [];
        $header = '';
        $form = '';
        $search = '';
        $const = [];
        $region = 'false';
        foreach ($fields as $item) {

            $field = $item->Field;
            $type = $item->Type;
            $comment = $item->Comment;

            if (in_array($field, ['password', 'updated_at', 'deleted_at', 'city_id', 'area_id'])) {
                continue;
            }

            if ($comment) {
                $comment = explode(' ', $comment);
            } else {
                $comment = [];
            }

            if ($comment) {
                $dictionary[$field] = $comment[0];
            }

            if ($field === 'status') {
                $header .= "'$field|switch',";
            } elseif (in_array($field, ['icon', 'image', 'images', 'video', 'photo', 'img', 'head', 'avatar'])) {
                $header .= "'$field|image',";
            } elseif ($field === 'province_id') {
                $header .= "'region_text',";
                $region = 'true';
            } else {
                $header .= '\'' . $field . "',";
            }

            if (!in_array($field, ['id', 'create_time', 'created_at'])) {
                if ($field === 'status') {
                    $form .= "'$field|radio',";
                } else if ($field === 'scene') {
                    $form .= "'$field|checkbox',";
                } else if (in_array($field, ['icon', 'image', 'images', 'video', 'photo', 'img', 'head', 'avatar'])) {
                    $form .= "'$field|upload',";
                } elseif ($field === 'province_id') {
                    $form .= "'region|region',";
                } else {
                    $form .= '\'' . $field . "',";
                }
            }

            if (in_array($field, ['id', 'phone', 'order_no', 'good_no', 'province_id'])) {
                if ($field === 'province_id') {
                    $search .= "'region|region',";
                } else {
                    $search .= "'" . $field . "',";
                }
            }

            if (preg_match('/^tinyint/', $type) || in_array($field, ['scene'])) {
                $const[] = [
                    'field' => $field,
                    'comment' => $item->Comment,
                ];
            }
        }

        $const_string = '';
        if ($const) {
            foreach ($const as $key => $item) {
                $comment = explode(' ', $item['comment']);

                // 过滤多一个空格导致的切割不正确，同时注意0值
                $comment = array_filter($comment, function ($item) {
                    if ($item === '') {
                        return false;
                    }
                    return true;
                });

                // 剔除掉第一个的描述信息
                $comment = array_slice($comment, 1);
                // 没三个切割为一个数组
                $comment = array_chunk($comment, 3);
                if (empty($comment)) {
                    continue;
                }
                if ($key > 0) {
                    $const_string .= '          ';
                }
                $const_string .= 'const ' . $item['field'] . ' = ';
                $const_string .= '{';
                foreach ($comment as $com) {
                    if (count($com) == 3) {
                        $const_string .= "{$com[0]}:'{$com[1]}',";
                    }
                };
                $const_string = trim($const_string, ',');
                $const_string .= "};\n";
            }
        }

        $header .= "'operation|action|w|180'";
        $header = trim($header, ',');
        $form = trim($form, ',');
        $search = trim($search, ',');

        $string = "\n";
        if ($dictionary) {
            foreach ($dictionary as $key => $item) {
                $string .= '                "' . $key . '":' . json_encode($item, JSON_UNESCAPED_UNICODE) . ",\n";
            }
        }
        $string = '{' . $string . '              }';

        $dictionary = $string;

        return <<<EOF
<template>
    <div>
        <ViewPage :page="page" @report="report"></ViewPage>
    </div>
</template>

<script lang="ts">
import { ViewPage, Page } from '/@/vpage';
export default {
    components: {
      ViewPage
    },
    setup() {
        const dictionaries=$dictionary;
        $const_string
        let page = new Page();
        page.name="$name";
        page.dictionay = dictionaries;
        page.search= [$search];
        page.form = [$form];

        page.table= [$header];
        page.table.action= ['edit', 'remove'];
        page.table.region=$region;

        const report = params => {
          let callback = params?.callback;
          if(typeof callback==="function"){
            callback();
          }
        };
        return {
            page,
            report
        };
    },
};
</script>

<style scoped lang="scss">

</style>
EOF;
    }
}
