<?php

namespace Magein\Admin\View;

class ViewErrorCode
{
    /**
     * 401~~ 页面相关错误代码
     * 402~~ curd相关错误代码
     */

    // 页面匹配模型错误
    const PAGE_MAPPING_FAIL = 40101;
    // 尚未获取权限
    const PAGE_FORBID = 40103;
    // 数据模型实例化失败
    const PAGE_MODEL_FAIL = 40105;
    // 页面行为错误
    const PAGE_ACTION_FAIL = 40107;

    // 页面请求参数为空
    const REQUEST_DATA_IS_NULL = 40201;
    // 请求参数验证规则为空
    const REQUEST_DATA_VALIDATE_RULES_IS_NULL = 40203;
    // 数据验证不通过
    const REQUEST_DATA_VALIDATE_FAIL = 40205;

}
