# Yangzie 框架 PHPdoc 文档

> 本文档根据 `yangzie/` 目录下的源代码及代码注释自动整理生成。
>
> 命名空间：`yangzie` ｜ 框架版本：`YZE_Object::VERSION = 3.0.1`

---

## 目录

- [1. 核心基类](#1-核心基类)
  - [YZE_Object](#yze_object)
  - [YZE_Hook](#yze_hook)
  - [YZE_I18N](#yze_i18n)
  - [YZE_ACL](#yze_acl)
  - [YZE_Session_Context](#yze_session_context)
  - [YZE_Router](#yze_router)
- [2. 请求处理](#2-请求处理)
  - [YZE_Request](#yze_request)
  - [YZE_Resource_Controller](#yze_resource_controller)
  - [Yze_Default_Controller](#yze_default_controller)
  - [YZE_Exception_Controller](#yze_exception_controller)
  - [YZE_Base_Module](#yze_base_module)
- [3. 视图与响应](#3-视图与响应)
  - [YZE_IResponse](#yze_iresponse)
  - [YZE_View_Adapter](#yze_view_adapter)
  - [YZE_Simple_View](#yze_simple_view)
  - [YZE_View_Component](#yze_view_component)
  - [YZE_Notpl_View](#yze_notpl_view)
  - [YZE_JSON_View](#yze_json_view)
  - [YZE_XML_View](#yze_xml_view)
  - [YZE_Layout](#yze_layout)
  - [YZE_Response_304_NotModified](#yze_response_304_notmodified)
  - [YZE_Redirect](#yze_redirect)
- [4. 模型与数据库](#4-模型与数据库)
  - [YZE_Model](#yze_model)
  - [YZE_SQL](#yze_sql)
  - [YZE_DBAImpl](#yze_dbaimpl)
  - [YZE_PDOStatementWrapper](#yze_pdostatementwrapper)
- [5. 异常体系](#5-异常体系)
- [6. GraphQL](#6-graphql)
  - [Graphql_Controller](#graphql_controller)
  - [GraphqlDatable](#graphqldatable)
  - [GraphqlType](#graphqltype)
  - [GraphqlField](#graphqlfield)
  - [GraphqlInputValue](#graphqlinputvalue)
  - [GraphqlEnumValue](#graphqlenumvalue)
  - [GraphqlDirective](#graphqldirective)
  - [GraphqlQueryWhere / GraphqlQueryClause](#graphqlquerywhere--graphqlqueryclause)
  - [GraphqlSearchArg / GraphqlSearchNode](#graphqlsearcharg--graphqlsearchnode)
  - [GraphqlIntrospection / GraphqlIntrospectionValues](#graphqlintrospection--graphqlintrospectionvalues)
  - [GraphqlResult](#graphqlresult)
  - [GraphqlCustomType](#graphqlcustomtype)
  - [Trait：Graphql_Query](#traitgraphql_query)
  - [Trait：Graphql_Mutation](#traitgraphql_mutation)
  - [Trait：Graphql__Schema](#traitgraphql__schema)
  - [Trait：Graphql__Type](#traitgraphql__type)
  - [Trait：Graphql__Typename](#traitgraphql__typename)
- [7. 全局函数](#7-全局函数)
  - [i18n 相关函数](#i18n-相关函数)
  - [文件操作函数](#文件操作函数)
  - [HTML 辅助函数](#html-辅助函数)
  - [启动与路由函数](#启动与路由函数)
- [8. 全局常量](#8-全局常量)

---

## 1. 核心基类

### YZE_Object

> 文件：`yangzie.php`
>
> 框架最基础的类，提供框架级静态工具方法：
> - 已加载模块信息的注册与查询（`set_loaded_modules` / `loaded_module`）
> - 变量的默认值处理（`the_val`）
> - 命名格式转换（`format_class_name`）
> - 输入数据的 HTML 转义与反转义（`filter_*` / `defilter_var`）

| 成员 | 类型/说明 |
| --- | --- |
| `VERSION` | `const string` 框架版本号 `'3.0.1'` |
| `$loaded_modules` | `private static array` 已加载模块注册表，key 为小写模块名 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `set_loaded_modules($module_name, $module_info)` | `static` 注册一个已加载的模块信息 |
| `loaded_module($module_name)` | `static` 根据模块名获取已加载的模块信息，未加载返回 `null` |
| `output()` | 输出方法，子类可重写定义自身输出行为 |
| `the_val($val, $default)` | `static` 变量为假值时返回默认值 |
| `format_class_name($class_name, $suffix)` | `static` 将 `aa_bb_cc` 格式化成 `Aa_Bb_Cc_suffix` |
| `filter_special_chars($array, $type)` | `static` 按指定输入来源转义 html 符号 |
| `filter_vars(array $array)` | `static` 转义数组中的 html 符号 |
| `filter_var($var)` | `static` 转义单个数据中的 html 符号 |
| `defilter_var($var)` | `static` 解码单个数据中的 html 符号（filter_var 逆操作） |

---

### YZE_Hook

> 文件：`hooks.php`
>
> 提供 hook 机制，hook 主要用于：数据输入/输出处理、事件通知、模块之间功能调用。
>
> 处理方式：系统启动前加载所有 hook 文件（`include_hooks`）；通过 `do_hook($hook_name, $args)` 调用，注册到同一 hook 的多个函数依次执行，前一个函数返回的 `$args` 会进入下一个 hook 函数。

| 成员 | 类型/说明 |
| --- | --- |
| `$listeners` | `private static array` 监听器注册表，结构：`listeners[事件名][模块名][] = ["function"=>回调, "object"=>对象]` |
| `$currModule` | `private static string` 当前正在加载 hook 文件的模块名 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `add_hook($event, $funcName, $object = null)` | `static` 增加 hook 回调；多个回调时返回最后一个回调的返回结果 |
| `do_hook($filterName, &$data=null, $module=null)` | `static` 触发指定 hook 事件，依次调用所有回调；`$module` 指定则只调用该模块的 hook（逗号分隔多个） |
| `get_hook($filterName, $module=null)` | `static` 返回注册在 filterName 下的回调函数列表 |
| `include_hooks($module, $dir)` | `static` 递归包含模块下目录中的所有 hook 文件 |

---

### YZE_I18N

> 文件：`i18n.php`
>
> 国际化（i18n）管理类。负责加载和缓存各 domain 的翻译（MO 格式翻译文件），并提供 `translate()` / `__()` / `_e()` 等全局函数。

| 成员 | 类型/说明 |
| --- | --- |
| `$i18n` | `private array` 已加载的翻译对象集合，key 为 domain 名 |
| `$me` | `private static YZE_I18N|null` 单例实例 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_instance()` | `static` 获取 i18n 管理单例 |
| `clear()` | 清空所有已加载的翻译 |
| `getLoadedI18N()` | 获取所有已加载的翻译对象集合 |
| `setLoadedI18N($domain, &$mo)` | 设置指定 domain 的翻译对象（引用保存） |

---

### YZE_ACL

> 文件：`acl.php`
>
> 访问控制列表（ACL）管理类。基于 ACO（被访问对象）与 ARO（请求访问角色/用户）两级权限模型，支持"拒绝优先、逐级向上匹配"的权限判断规则。

| 成员 | 类型/说明 |
| --- | --- |
| `$acos_aros` | `private array` ACO 与 ARO 权限映射表，结构：`[aco=>["deny"=>[...], "allow"=>[...]]]` |
| `$permission_cache` | `private array` 权限检查结果缓存，结构：`[aroname][aconame]=>bool` |
| `$instance` | `private static YZE_ACL|null` 单例实例 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_instance()` | `static` 获取 ACL 单例 |
| `check_byname($aroname, $aconame)` | 检查 $aroname（单个角色名或角色名数组）是否有对 $aconame 的访问权限 |
| `begin_check_permission($aroname, $aconame)` | 开始权限检查：开启输出缓冲并缓存检查结果 |
| `end_check_permission($aroname, $aconame)` | 结束权限检查：有权限则输出缓冲内容，否则丢弃 |
| `check_user_permission($aconame)` | `private` 检查当前登录用户权限（返回 true / false / -1） |
| `check_role_permission($aroname, $aconame)` | `private` 检查指定角色权限，未匹配时逐级向父级递归匹配 |
| `need_controll($aconame)` | `private` 判断 ACO 是否在需要权限控制的范围内 |
| `in_array($check, array $arrays)` | `private` 判断 $check 是否匹配 $arrays 中任意一项（支持通配符 `*`） |

---

### YZE_Session_Context

> 文件：`session.php`
>
> 会话封装类（单例）。基于 `$_SESSION['yze']` 命名空间存取数据。

| 成员 | 类型/说明 |
| --- | --- |
| `$instance` | `private static YZE_Session_Context|null` 单例实例 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_instance()` | `static` 获取会话上下文单例 |
| `get($key)` | 从会话中获取指定 key 的数据，不存在返回 `null` |
| `set($key, $value)` | 在会话中设置指定 key 的数据 |
| `has($key)` | 判断会话中是否存在指定的 key |
| `destory($key = null)` | 删除会话中指定的 key；为 `null` 时清空全部，支持链式调用 |

---

### YZE_Router

> 文件：`router.php`
>
> 路由管理类。定义系统的所有资源及这些资源对应的控制器映射，负责收集并加载各模块配置中的路由表（routers）。

| 成员 | 类型/说明 |
| --- | --- |
| `$instance` | `private static YZE_Router|null` 路由表单例实例 |
| `$mappings` | `private array` 各模块的路由映射表，结构：`['模块名'=>['uri地址'=>["controller"=>, 'action'=>, "args"=>]]]` |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_Instance()` | `static` 获取路由管理单例 |
| `set_Routers($module, $vars)` | 设置指定模块的路由映射表 |
| `get_Routers($module = null)` | 获取路由映射表，`$module` 为 `null` 时返回全部 |
| `load_routers()` | `static` 加载所有模块的路由配置（遍历 `YZE_APP_MODULES_INC` 下模块的 `__config__.php`） |

---

## 2. 请求处理

### YZE_Request

> 文件：`request.php`
>
> 一次请求处理的上下文，单例模式。负责解析 URI、匹配路由、实例化控制器/模块、认证、分发调度。

| 成员 | 类型/说明 |
| --- | --- |
| `$method` | `private string` 映射到的控制器方法名，如 `post_index`、`index` |
| `$request_method` | `private string` 当前请求的 http 方法，如 get、post、delete、put |
| `$vars` | `private array` 请求变量集合（含路由命名参数与请求参数） |
| `$post` / `$get` / `$cookie` / `$server` / `$env` | `private array` 各来源请求数据 |
| `$controller_name` / `$controller_class` / `$controller` | `private` 控制器短名 / 类名 / 实例 |
| `$module_class` / `$module_obj` / `$module` | `private` 模块类名 / 实例 / 当前模块名 |
| `$view_path` | `private string` 模块 views 目录路径 |
| `$uri` / `$full_uri` / `$queryString` | `private string` URI（已 urldecode）/ 完整 URI / query string |
| `$uuid` | `private string` 本次请求唯一标识 |
| `$exception` | `private \Exception|null` 请求处理过程中抛出的异常 |
| `$me` | `private static YZE_Request|null` 单例实例 |
| `$context` | `private array` 请求上下文数据（`set()` / `get()` 存取） |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_instance()` | `static` 返回 YZE_Request 实例 |
| `init($newUri, $action, $format, $request_method)` | 初始化请求：解析 uri、匹配路由、实例化控制器 |
| `set($name, $value)` / `get($name)` | 在请求上下文中设置 / 取值 |
| `the_post_datas()` / `the_get_datas()` | 返回 post / get 数据 |
| `set_post($name, $value)` | 往 post 数据中设置值（后端设置） |
| `get_from_post($name, $default)` | 从 post 数据中取值 |
| `get_from_server($name, $default)` | 从 `$_SERVER` 中取值 |
| `get_from_cookie($name, $default)` | 从 cookie 中取值 |
| `get_from_get($name, $default)` | 从 query string 中取值 |
| `get_from_request($name, $default)` | 按 `post > cookie > get > server` 顺序取值 |
| `get_request_method()` | 当前请求的方法 |
| `the_uri()` | 请求的 URI 路径部分（已 urldecode） |
| `the_full_uri()` | 请求的路径及 query string（未 urldecode） |
| `the_query()` | 请求字符串（`?` 后面的部分） |
| `get_Scheme()` | 请求协议，`http` 或 `https` |
| `uuid()` | 每次请求的唯一 uuid |
| `the_method()` | 映射的控制器方法 |
| `is_post()` / `is_get()` | 是否 post / get 请求 |
| `the_referer_uri($just_path)` | 获取请求来源地址（HTTP_REFERER） |
| `auth()` | 对当前请求做身份认证处理（options 请求不做处理） |
| `get_output_format()` | 取得请求指定的输出格式，默认 `tpl`，移动端 `mob` |
| `is_mobile_client()` | 是否移动端（UA 含 android/iphone/ipad） |
| `is_In_IOS()` / `is_In_Android()` | 是否 iOS / Android 环境 |
| `format_gmdate($date_str)` | `static` 把日期字符串格式化为 GMT 格式 |
| `set_method($method)` | `private` 设置映射的控制器方法名 |
| `set_vars($vars)` | `private` 设置请求变量集合 |
| `set_var($name, $val)` | 设置单个请求变量，支持链式调用 |
| `get_var($key, $default)` | 取得 router 中 url 正则表示的命名参数 |
| `need_auth()` | `private` 判断当前请求方法是否需要认证 |
| `get_auth_methods($controller_name, $type)` | `private` 从模块配置获取 need / noneed 认证方法列表 |
| `parse_url($routers, $uri)` | `static` 根据路由表解析当前 url，未匹配则按 `/module/controller/vars.format` 默认格式解析 |
| `dispatch()` | 处理请求，把控制交给具体控制器的具体方法 |
| `set_controller_name($controller)` | `private` 设置并实例化控制器 |
| `controller_name($is_sort)` | 控制器名字，如 `\app\module\controller`；`$is_sort=true` 返回短格式 |
| `controller_class($is_sort)` | 控制器类名，如 `\app\module\controller_Controller` |
| `controller_instance()` | 控制器对象 |
| `set_module($module)` | 设置并实例化模块 |
| `module()` / `module_class()` / `module_instance()` | 模块名 / 模块类名 / 模块配置对象 |
| `view_path()` | 当前请求模块的 views 目录（结尾无 `/`） |
| `get_exception()` / `set_Exception(\Exception $e)` | 获取 / 设置请求处理过程中保存的异常 |
| `get_Accept_Language()` | 返回前端支持的语言 |

---

### YZE_Resource_Controller

> 文件：`controller.php`
>
> 资源控制器抽象基类，提供控制器的处理机制，子类控制器的 action 映射到具体的 uri。
>
> 同一个 url 的不同 request method 映射到不同 action：GET `/user` → `User_Controller:index`；POST `/user` → `post_index`；DELETE `/user` → `delete_index`。即非 get 请求在 action 前加 `REQUEST_METHOD_` 前缀。

| 成员 | 类型/说明 |
| --- | --- |
| `$view_data` | `protected array` 视图数据集合，key 为变量名 |
| `$layout` | `protected string` 布局名，默认 `tpl` |
| `$view` | `protected string` 视图模板名，非空时优先使用 |
| `$request` | `protected YZE_Request` 当前请求实例 |
| `$module` | `protected YZE_Base_Module` 当前请求所在模块 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($request = null)` | 初始化请求与模块实例，并根据输出格式设置布局 |
| `get_Request()` | 获取当前请求实例 |
| `get_Layout()` | 获取布局名 |
| `set_View_Data($name, $value)` | 设置视图数据，支持链式调用 |
| `get_View_Data($name)` | 获取视图数据，不存在返回 `null` |
| `response_headers()` | 子类可重载设置响应头（如跨域头） |
| `handle_request()` | `final` 调用映射的 action 方法并返回响应 |
| `do_exception(\Exception $e)` | `final` 处理 action 过程中的异常，json 格式返回错误视图 |
| `exception(\Exception $e)` | 子类重载该方法处理异常 |
| `get_Annotation($action, $annotation)` | 获取 action 上指定注解的值 |
| `has_Annotation($action, $annotation)` | 判断 action 上是否有指定注解 |
| `get_Response($view_tpl, $format)` | `private` 返回当前请求对应的响应对象 |

---

### Yze_Default_Controller

> 文件：`controller.php`
>
> 默认控制器，当请求无法匹配到任何模块与控制器时使用。

**方法**

| 方法 | 说明 |
| --- | --- |
| `index()` | 显示框架欢迎页 |

---

### YZE_Exception_Controller

> 文件：`controller.php`
>
> 异常控制器，用于统一处理请求过程中的异常并输出对应的错误页面。

| 成员 | 类型/说明 |
| --- | --- |
| `$exception` | `private \Exception|null` 需要展示的异常对象 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `index()` | 根据异常错误码输出对应的错误页面 |
| `exception(\Exception $e)` | 保存异常并输出错误页面 |
| `output_status_code($error_number)` | `private` 根据错误码输出 http 状态码响应头（404 / 500） |

---

### YZE_Base_Module

> 文件：`module.php`
>
> 模块配置基类。整个 yangzie app 也是一个 module，每个 module 可以通过 `check` 方法检查运行必须满足的条件，通过 `config` 返回模块配置（如 routers）。

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 模块的名字 |
| `$auths` | `public array` 需要认证访问的资源，格式：`['控制器名'=>"action，支持正则"]` |
| `$no_auths` | `public array` 不需要认证的 action，优先级比 `$auths` 高 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_module_config($name = null)` | 获取指定的模块配置（对象属性 + config 返回内容） |
| `get_uris_of_controller($controller, $action='index')` | 返回指定控制器上映射的 url 列表 |
| `check()` | 加载模块之前做检查，出错则抛异常 |
| `config()` | `protected abstract` 初始化配置项的值，返回配置数组（含 `routers`） |
| `js_bundle($bundle)` | `abstract` js 资源分组，返回资源路径列表 |
| `css_bundle($bundle)` | `abstract` css 资源分组，返回资源路径列表 |

---

## 3. 视图与响应

### YZE_IResponse

> 文件：`view.php`
>
> HTTP 请求响应结果接口。实现类可以是可查看的内容（html、xml、json、yaml 等），也可以只是 http 响应头（301 redirect、304 not modified 等）。

**方法**

| 方法 | 说明 |
| --- | --- |
| `output($return = false)` | 输出响应；`$return` 为 true 表示返回不输出 |
| `get_data($key)` | 取得控制器设置在响应中的值 |

---

### YZE_View_Adapter

> 文件：`view.php`
>
> 视图响应抽象基类。表示响应 HTTP 中有 message-body，内容可能是 html、xml、json、yaml 等，视图响应是可缓存的。

| 成员 | 类型/说明 |
| --- | --- |
| `$data` | `protected array` 响应视图上要显示的数据 |
| `$layout` | `public string` 指定 view 输出的 layout，优先级高于 controller 设置的 layout |
| `$format` | `public string` 响应格式 |
| `$master_view` | `public string \| YZE_View_Component` 指定视图的容器视图 |
| `$master_view_path` | `protected string` 调用 `check_master` 后找到的 master view 绝对路径 |
| `$controller` | `protected YZE_Resource_Controller` 视图所属控制器 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($data, YZE_Resource_Controller $controller)` | 构造视图 |
| `get_controller()` | 获取视图所属的控制器 |
| `content_of_section($section)` | 获取指定 section 的内容 |
| `content_of_view()` | 获取当前视图的内容 |
| `output($return = false)` | `final` 输出视图响应（含 master view 渲染） |
| `view_sections()` | 获取当前视图收集的所有 section 内容 |
| `begin_section()` | 开始收集一个 section |
| `end_section($section)` | 结束 section 收集并保存 |
| `get_output()` | 取得视图的输出内容 |
| `get_data($key)` / `get_datas()` | 获取视图单个 / 全部数据 |
| `set_data($key, $data)` / `set_datas(array $datas)` | 设置视图单个 / 批量数据 |
| `check_view()` | 检查模板文件是否存在 |
| `build_view($controller, $format, $data)` | `static` 根据响应格式构建对应视图（json / xml / 其他） |
| `check_master()` | `protected` 检查 master view 是否存在 |
| `output_master($data, $return)` | `protected` 输出 master view |
| `display_self()` | `protected abstract` 视图响应显示自己 |

---

### YZE_Simple_View

> 文件：`view.php`
>
> 视图响应实现，负责加载视图响应模板（`views/controller name/action name.tpl.php`）。在对象中 include 模板，模板中可通过 `$this->get_data()` 等 API 取到控制器设置的数据。

| 成员 | 类型/说明 |
| --- | --- |
| `$tpl` | `private string` 视图模板名（不含格式后缀），按 `{$tpl}.{$format}.php` 查找 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($tpl_name, $data, YZE_Resource_Controller $controller, $format = null)` | 通过模板、数据构建视图输出 |
| `check_master()` | `protected` 按模块 views / app views / 绝对路径查找 master view |
| `check_view()` | 检查模板文件，不存在时回退 tpl 或抛异常 |
| `display_self()` | `protected` 加载并 require 模板文件 |

---

### YZE_View_Component

> 文件：`view.php`
>
> 以 class 的方式实现 view，如需使用 master view 则重载 `check_master` 并返回 master 的 `YZE_View_Component` 对象。

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($data, $controller, $format = null)` | 构造函数 |
| `output_component()` | `protected abstract` 输出组件内容 |
| `display_self()` | `protected` 调用 `output_component()` |

---

### YZE_Notpl_View

> 文件：`view.php`
>
> 没有模板文件的 response，只输出一些字符串，用于没有 html 模板只返回简单数据的地方。

| 成员 | 类型/说明 |
| --- | --- |
| `$html` | `private string` 需要直接输出的字符串内容 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($html, YZE_Resource_Controller $controller)` | 构造函数 |
| `display_self()` | `protected` 直接输出字符串 |
| `return_html()` | 返回要输出的字符串内容 |

---

### YZE_JSON_View

> 文件：`view.php`
>
> 返回 json，输出格式 `{errorcode, success, msg, data}`。

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct(YZE_Resource_Controller $controller, $data)` | 构造函数 |
| `display_self()` | `protected` 输出 `Content-Type: application/json` 及 json 编码数据 |
| `error($controller, $message, $code, $data)` | `static` 构建失败响应 `{success:false, data, code, msg}` |
| `success($controller, $data)` | `static` 构建成功响应 `{success:true, data, msg:null}` |

---

### YZE_XML_View

> 文件：`view.php`
>
> 把数据转换成 xml 输出。

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct(YZE_Resource_Controller $controller, $data)` | 构造函数 |
| `display_self()` | `protected` 输出 xml |
| `array_to_xml($data, &$xml)` | `private` 将数组数据递归转换为 xml 节点 |
| `error($controller, $message, $code)` | `static` 构建失败响应 `<success>0</success>` |
| `success($controller, $data)` | `static` 构建成功响应 `<success>1</success>` |

---

### YZE_Layout

> 文件：`view.php`
>
> Layout 定义视图响应的数据定义格式（html / xml / json 等）。layout 也是视图响应，也包含模板，它在定义的响应数据格式中加上请求视图的内容。
>
> layout 模板中的 `content_for_layout` 表示请求的视图输出内容。

| 成员 | 类型/说明 |
| --- | --- |
| `$view` | `private YZE_View_Adapter` 需要被布局包裹的视图对象 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($layout, YZE_View_Adapter $view, YZE_Resource_Controller $controller)` | 构造函数 |
| `display_self()` | `protected` 输出布局（支持移动端 moblayout、pjax 请求不返回 layout） |

---

### YZE_Response_304_NotModified

> 文件：`view.php`
>
> 只输出 http 头、无 message-body 的响应，表示请求内容没有修改，客户端应使用缓存内容。

| 成员 | 类型/说明 |
| --- | --- |
| `$headers` | `private array` 需要输出的 http 响应头集合 |
| `$controller` | `private YZE_Resource_Controller` 发起响应的控制器 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($headers, YZE_Resource_Controller $controller)` | 构造函数 |
| `output($return = false)` | 输出 304 状态码及配置的响应头 |
| `add_header($header_name, $header_value)` | 增加一个响应头 |
| `get_data($key)` | 获取指定响应头的值 |

---

### YZE_Redirect

> 文件：`view.php`
>
> HTTP Location 重定向响应，表示请求的处理输出是重定向到一个新地址。

| 成员 | 类型/说明 |
| --- | --- |
| `$destinationURI` | `private string` 重定向的目标 url |
| `$sourceController` | `private YZE_Resource_Controller` 发起重定向的控制器 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($destination_uri, YZE_Resource_Controller $source_controller)` | 构造函数 |
| `output($return = false)` | 输出重定向响应（`$return=true` 时返回目标 url 不输出 header） |
| `destinationURI()` | 获取重定向的目标 url |
| `get_data($key)` | 始终返回空字符串 |

---

## 4. 模型与数据库

### YZE_Model

> 文件：`model.php`
>
> model 基类，封装基本的表与 model 的映射、操作。约定表必须包含自增主键，建议有版本字段与 uuid 字段（提供给前端使用），不支持复合主键。
>
> 使用 `Graphql_Query` trait，支持 Model Query 链式调用。

| 成员 | 类型/说明 |
| --- | --- |
| `$sql` | `private YZE_SQL` Model Query 链式调用使用的查询对象 |
| `$suffix` | `private string|null` 分表查询时使用的表后缀 |
| `$db` | `private string|null` 当前 Model 使用的数据库名 |
| `$records` | `protected array` 记录数据集合，key 为字段名 |
| `$objects` | `protected array` 对象映射关系 |
| `$encrypt_columns` | `public array` 需要进行加密的字段名（读写自动加解密） |
| `$cache` | `private array` 缓存数据集合（预留） |
| `$unique_key` | `protected array` 唯一键配置 |
| `$relation_column` | `protected array` 与其他 model 的关联关系 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_unique_key()` | 返回该 model 的唯一键字段集合 |
| `get_relation_columns()` | 返回关联关系 |
| `get_table()` / `get_key_name()` / `get_uuid_name()` | 返回表名 / 主键字段名 / uuid 字段名 |
| `get_columns()` | 返回实体对应的字段配置 `array('column'=>array(type,nullable))` |
| `get_graphql_column()` | 获取前端可见的 column（可重载做字段级权限控制） |
| `get_graphql_fields()` | 把 model 字段封装成 `GraphqlField` 返回 |
| `get_Model_Field_Type($columnConfig, $columnName)` | 获取 Graphql 字段的类型 |
| `get_module_name()` | 返回当前 Model 所在模块名 |
| `has_set_value($column)` / `has_column($column)` | 判断字段是否已设置 / 是否存在 |
| `to_json()` / `from_Json($json)` / `from_Array(array $array)` | model 与 json / 数组互转 |
| `get_key()` / `get_uuid()` | 返回主键值 / uuid 字段值 |
| `get_date_val($name, $format)` | 时间字段去掉时间部分只留日期 |
| `get_records()` | 返回 model 的记录数组 |
| `get($name)` / `set($name, $value)` | 获取 / 设置字段值（set 按字段类型转型、字符串截断） |
| `find_by_id($id, $db, $suffix)` | `static` 根据主键查询 model |
| `find_by_ids($ids, $db, $suffix)` | `static` 查询指定主键集合，以 id 为键返回数组 |
| `remove_by_id($id, $db, $suffix)` | `static` 删除指定 id 的记录，触发 `YZE_HOOK_MODEL_DELETE` |
| `find_by_uuid($uuid, $db, $suffix)` | `static` 通过 uuid 查找 model |
| `remove_by_uuid($uuid, $db, $suffix)` | `static` 删除指定 uuid 的记录 |
| `is_Empty_Date($dateValue)` | 判断是否空日期 |
| `update_by_id($id, $attrs, $db, $suffix)` | `static` 用 attrs 更新指定主键的 model |
| `find_all($db, $suffix)` | `static` 查询所有数据 |
| `before_Save()` | save 前调用，可做验证等 |
| `save($type, $checkSql)` | 保存记录（有主键更新、无主键插入），返回主键；触发 `YZE_HOOK_MODEL_UPDATE` / `YZE_HOOK_MODEL_INSERT` |
| `insert_Or_Update($checkFields)` | 判断传入字段值，存在则更新，否则插入 |
| `remove()` | 从数据库删除对象数据并清空主键 |
| `refresh()` | 从数据库刷新 |
| `remove_all($db, $suffix)` | `static` 删除所有记录 |
| `delete_key()` / `delete_field($key)` | 清空主键 / 清空指定字段 |
| `save_from_data($posts, $prefix, $type, $checkSql)` | 用指定 data 保存记录 |
| `__get($name)` / `__set($name, $value)` | 魔术方法：加密字段自动解密 / 加密 |
| `from($myAlias, $suffix)` | `static` 开启 Model Query 链式调用 |
| `in_db($db)` / `suffix($suffix)` / `get_suffix()` | 设置 / 获取数据库名、分表后缀 |
| `where($where)` / `order_By(...)` / `group_By(...)` / `limit(...)` | 查询条件、排序、分组、分页 |
| `left_join(...)` / `right_join(...)` / `join(...)` | 左连接 / 右连接 / 内连接 |
| `select($params, $alias)` / `get_Single($params, $alias)` | 查询多条 / 单条 |
| `count($field, $params, $alias, $distinct)` / `sum` / `max` / `min` | 聚合查询 |
| `delete($params, $alias)` | 删除满足条件的记录 |
| `clean()` / `clean_where()` / `clean_select()` | 清空查询 / where / select |
| `truncate($db, $suffix)` | `static` 清空表中所有数据 |
| `get_Sql()` / `init_Sql()` | 获取 / 初始化查询对象 |
| `get_Field_Type($field_name)` / `get_Field_props($field_name)` | `private` 获取字段类型 / 完整配置 |
| `uuid()` | `static` 返回 uuid 值 |
| `get_column_mean($column)` | 返回字段面向用户可读的含义（子类实现） |

---

### YZE_SQL

> 文件：`sql.php`
>
> 构建查询语句类。以链式调用方式构建 select / insert / update / delete SQL。

**常量**

| 常量 | 值 |
| --- | --- |
| `EQ` / `NE` / `GT` / `LT` / `GEQ` / `LEQ` | `=` / `!=` / `>` / `<` / `>=` / `<=` |
| `ISNULL` / `ISNOTNULL` | `is null` / `is not null` |
| `NOTIN` / `IN` / `FIND_IN_SET` / `BETWEEN` | `not in` / `in` / `FIND_IN_SET` / `between` |
| `LIKE` / `BEFORE_LIKE` / `END_LIKE` | `like` / `like before` / `like end` |
| `DESC` / `ASC` | `desc` / `asc` |
| `INSERT_NORMAL` | `insert_normal` 普通插入 |
| `INSERT_NOT_EXIST` | `insert_not_exist` 条件不存在时插入 |
| `INSERT_NOT_EXIST_OR_UPDATE` | `insert_not_exist_or_update` 不存在插入、存在更新 |
| `INSERT_EXIST` | `insert_exist` 条件存在时插入 |
| `INSERT_ON_DUPLICATE_KEY_UPDATE` | `insert_on_duplicate_key_update` 唯一键冲突更新 |
| `INSERT_ON_DUPLICATE_KEY_REPLACE` | `insert_on_duplicate_key_replace` 唯一键冲突先删后插 |
| `INSERT_ON_DUPLICATE_KEY_IGNORE` | `insert_on_duplicate_key_ignore` 唯一键冲突忽略 |

**主要成员**

| 成员 | 类型/说明 |
| --- | --- |
| `$where` / `$select` / `$distinct` / `$count` / `$sum` / `$min` / `$max` | `private array` 各 SQL 片段 |
| `$update` / `$insert` / `$from` | `private array` 更新 / 插入 / from 数据 |
| `$limit_start` / `$limit_end` | `private int|null` limit 起点 / 结束 |
| `$has_join` / `$has_from` | `private bool` 是否包含 join / from |
| `$suffix` | `private array` 表别名及对应表后缀 |
| `$order_by` / `$group_by` | `private array` 排序 / 分组 |
| `$action` | `private string` 当前构建的 sql 类型（select/insert/update/delete） |
| `$classes` | `private array` 构建查询中涉及的类 |
| `$unique_key` / `$check_sql` / `$insert_type` | `private` 唯一键 / 检查 sql / 插入类型 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `where($where)` | 构建原生 where 条件 |
| `select($alias, array $select)` | 查询字段 |
| `distinct($alias, $field)` | distinct 查询 |
| `count($table_alias, $field, $count_alias, $distinct)` | count 聚合 |
| `sum` / `max` / `min` | sum / max / min 聚合 |
| `delete()` / `update($alias, array $datas)` / `insert($alias, array $datas, $insert_type, $extra_info)` | 构建删除 / 更新 / 插入 sql |
| `from($class_name, $alias, $suffix)` | 构建查询的 from 段 |
| `left_join` / `right_join` / `join` | 构建 join 段 |
| `get_alias($table_name)` | 返回表在查询中的别名 |
| `limit($start, $end)` | limit 限制 |
| `order_by($table_alias, $order_by, $sort, $use_alias)` | 构建 order by |
| `group_by($table_alias, $group_by, $use_alias)` / `group_by_function($group_by)` | 构建 group by |
| `new_SQL()` | `static` 返回新 YZE_SQL 对象 |
| `clean_where($alias, $column)` | 清空 where 条件（支持按表 / 字段） |
| `clean()` / `clean_groupby()` / `clean_limit()` / `clean_select()` | 清除已构造的查询 |
| `get_select_classes($just_select)` | 返回要查询的对象类名 |
| `__toString()` | 构建并返回完整的 sql 语句 |
| `has_join()` / `has_from()` | 是否包含 join / from |
| `get_select_table()` | 返回 sql 中的所有表名 |
| `isinsert()` / `isdelete()` | 当前 sql 是否是插入 / 删除语句 |
| `_quoteValue($value)` | `private` 构建格式正确的 sql 值（转义） |
| `_buildWhere($wheres)` | `private` 构建单条 where 条件 |
| `_where()` / `_from()` / `_select()` / `_delete()` / `_insert()` / `_update()` / `_group_by()` / `_order_by()` / `_limit()` | `private` 各子句构建 |

---

### YZE_DBAImpl

> 文件：`dba.php`
>
> 与数据库进行交互的类，负责对数据库的 CRUD 操作并返回 model。

| 成员 | 类型/说明 |
| --- | --- |
| `$conn` | `private static array[PDO]` 各数据库连接对象集合，key 为数据库名 |
| `$db_name` | `private string` 当前实例使用的数据库名 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($db_name)` | 建立指定数据库的连接 |
| `get_instance($db_name = null)` | `static` 返回 DBA 实例（不存在则创建并开启事务） |
| `get_db_name()` | 返回当前实例使用的数据库名 |
| `get_Conn($db_name)` | 返回 PDO 对象 |
| `get_all_Conn()` | 返回所有已建立的数据库连接对象 |
| `reset()` | 重置数据库连接 |
| `get_crypt_key()` | `private` 获取加密秘钥 |
| `connect($db_name, $force)` | `private` 建立（或复用）PDO 连接并开启事务 |
| `check_connect($errorCode, $errorInfo, $method, ...$args)` | `private` MySQL server has gone away 时重连重试 |
| `get_entity_record(YZE_Model $entity)` | `private` 获取 model 记录（加密字段加密处理） |
| `save_update(YZE_Model $entity)` | `private` 根据主键更新记录，触发 `YZE_HOOK_MODEL_UPDATE` |
| `valid_entity(YZE_Model $entity)` | `private` 校验字段：非空、长度、枚举、日期 |
| `build_entity(YZE_Model $entity, $raw_datas, $table_alias)` | `private` 根据原始行数据构建 model 字段值 |
| `decrypt($hexString)` / `encrypt($value)` | 通过秘钥解密 / 加密（hex 格式） |
| `quote($value)` | 对值进行转义 |
| `find_by(array $ids, $class, $suffix)` | 批量查找指定 id 的对象 |
| `find($key, $class, $suffix)` | 根据主键查询记录返回实体 |
| `find_All($class, $suffix)` | 查询所有记录，键为主键值 |
| `native_Query($sql, $params)` | 原生查询，返回 `YZE_PDOStatementWrapper` |
| `get_Single(YZE_SQL $sql, $params)` | 同 select，只返回一条数据 |
| `select(YZE_SQL $sql, $params, $index_field)` | 根据条件查询所有记录，触发 `YZE_HOOK_MODEL_SELECT` |
| `delete(YZE_Model $entity)` | 删除传入 model，清空主键，触发 `YZE_HOOK_MODEL_DELETE` |
| `in_transaction()` | 当前是否处于事务内 |
| `execute(YZE_SQL $sql, $params)` / `exec($sql, $params)` | 执行 sql，返回影响记录数 |
| `save(YZE_Model $entity, $type, $checkSql)` | 保存记录，触发 `YZE_HOOK_MODEL_INSERT` |
| `auto_Commit($boolean)` | 设置是否开启自动提交 |
| `begin_Transaction($commit)` | 开启事务（建连时自动开启） |
| `commit_all()` / `commit()` | 提交所有 / 单个事务 |
| `rollBack_all()` / `rollBack()` | 回滚所有 / 单个事务 |
| `lookup($field, $table, $where, $values)` | 查单个字段值 |
| `lookup_record($fields, $table, $where, $values)` | 查询多个字段值（单条） |
| `lookup_records($fields, $table, $where, $values)` | 查询多条数据 |
| `update($table, $fields, $where, $values)` | 更新记录 |
| `deletefrom($table, $where, $values)` | 删除记录 |
| `check_Insert($table, $info, $checkSql, $checkInfo, $exist, $update, $key)` | 根据 checkSql 判断如何插入 |
| `insert($table, $info, $duplicate_key, $keyname)` | 插入记录（支持唯一键冲突更新） |
| `insert_Or_Ignore($table, $info)` | 插入记录，唯一键冲突忽略 |
| `replace($table, $info)` | 插入记录，唯一键冲突替换 |
| `table_fields($table)` | 返回指定表的字段列表 |

---

### YZE_PDOStatementWrapper

> 文件：`dba.php`
>
> PDOStatement 结果集包装类。构造时一次性取回所有结果行，通过游标（index）逐行访问，字段值统一进行 html 符号过滤。

| 成员 | 类型/说明 |
| --- | --- |
| `$db` | `private PDOStatement` 原始 PDOStatement 对象 |
| `$result` | `private array` 取回的所有结果行 `[行号=>[字段名=>值]]` |
| `$index` | `private int` 当前游标位置（默认 -1） |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct(PDOStatement $db_mysql)` | 取回语句的所有结果 |
| `reset()` | 将游标重置到起始位置 |
| `next()` | 游标下移一行并返回该行数据 |
| `get_results()` | 返回全部结果行 |
| `f($name, $table_alias = null)` | 返回当前行指定字段值（支持表别名前缀） |
| `getEntity(YZE_Model $entity, $alias)` | 用当前行数据填充 model 并返回 |

---

## 5. 异常体系

> 文件：`error.php`
>
> 所有异常均继承自 `\Exception`，构造函数签名为 `__construct($message = null, $code = 默认值)`。

| 异常类 | 继承自 | 默认错误码 | 说明 |
| --- | --- | --- | --- |
| `YZE_RuntimeException` | `\Exception` | 500 | 运行时异常 |
| `YZE_FatalException` | `YZE_RuntimeException` | 500 | 严重异常 |
| `YZE_Suspend_Exception` | `YZE_FatalException` | 500 | 中止类型异常，不进入 controller 的 do_exception 处理 |
| `YZE_Need_Signin_Exception` | `YZE_Suspend_Exception` | 500 | 需要登录的异常，直接中止请求流程 |
| `YZE_Permission_Deny_Exception` | `YZE_Suspend_Exception` | 500 | 权限不足的异常，直接中止请求流程 |
| `YZE_Resource_Not_Found_Exception` | `YZE_RuntimeException` | 404 | 请求对象不存在，使用 Error_Controller 处理 |
| `YZE_DBAException` | `YZE_RuntimeException` | 404 | 数据库访问异常 |
| `YZE_Not_Modified_Exception` | `YZE_RuntimeException` | 302 | HTTP 302 响应 |
| `YZE_Model_Update_Conflict_Exception` | `YZE_RuntimeException` | 500 | Model 更新冲突（乐观锁冲突） |

---

## 6. GraphQL

### Graphql_Controller

> 文件：`graphql.php`
>
> GraphQL 处理控制器。负责解析请求中的 graphql 查询语句、验证字段并执行数据查询，支持 query 与 mutation 两类操作，同时实现内省（introspection）查询。
>
> 使用 trait：`Graphql__Schema`、`Graphql__Type`、`Graphql__Typename`、`Graphql_Query`、`Graphql_Mutation`。

| 成员 | 类型/说明 |
| --- | --- |
| `$operationType` | `private string` 当前请求的操作类型：query 或 mutation（默认 query） |
| `$operationName` | `private string|null` 当前请求的操作名称（operationName） |
| `$vars` | `private array` 请求传入的变量集合 |
| `$varDefault` | `private array` 变量的默认值 |
| `$fetchActRegx` | `private string` 用于拆分 graphql 查询语句的正则 |
| `$allModelTypes` | `private array` 所有 model 的类型缓存，key 为表名 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `response_headers()` | 返回允许跨域访问的响应头 |
| `post_index()` | post 请求处理入口，直接调用 index |
| `index()` | graphql 请求处理入口：解析数据 → 解析语法结构 → 执行查询/变更 → 返回结果 |
| `fetch_Request()` | `private` 根据请求方法（post/get）及传参方式获取请求数据 |
| `parse($query)` | `private` 解析请求并对 field 做验证，返回 `GraphqlSearchNode` 结构 |
| `parse_var_default($args)` | `private` 解析变量默认值（预留实现） |
| `fetch_Fragment($acts, $fragmentName)` | `private` 提取指定 fragment 的节点结构 |
| `fetch_Node($acts, &$fetchedLength)` | `private` 遍历 token 解析查询节点结构 |
| `parse_args($argString)` | `private` 逐个字符遍历提取参数名、参数值及元字符 `[]{}` |
| `array_key_last($array)` | `private` 返回数组最后一个键名 |
| `fetch_Args_array($end, $acts, &$fetchedLength)` | `private` 解析嵌套 `{}`/`[]` 结构，返回关联数组 |
| `fetch_Args($acts)` | `private` 提取查询字符串中的参数部分 |
| `query($table, GraphqlSearchNode $node, &$total)` | `private` 解析并返回查询结果 |
| `basic_types()` | `private` 返回 graphql 基础标量类型定义 |
| `get_all_model_types()` | `private` 返回 `[tableName=>[__Field]]` |

---

### GraphqlDatable

> 文件：`graphql_model.php`
>
> GraphQL 数据结构接口。

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_data()` | 返回可序列化的数据数组 |

---

### GraphqlType

> 文件：`graphql_model.php`
>
> GraphQL 类型描述类，实现 `GraphqlDatable`。

**常量**

| 常量 | 值 |
| --- | --- |
| `KIND_SCALAR` / `KIND_OBJECT` / `KIND_INTERFACE` / `KIND_UNION` / `KIND_ENUM` / `KIND_INPUT_OBJECT` / `KIND_LIST` / `KIND_NON_NULL` | `SCALAR` / `OBJECT` / `INTERFACE` / `UNION` / `ENUM` / `INPUT_OBJECT` / `LIST` / `NON_NULL` |

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 字段名 |
| `$kind` | `public string` 字段类型（见 `GraphqlType::KIND_*`） |
| `$description` | `public string` 字段描述 |
| `$fields` / `$interfaces` / `$possibleTypes` / `$enumValues` / `$inputFields` | `public array` 各类型集合 |
| `$specifiedByURL` | `public string` 规范 URL |
| `$ofType` | `public GraphqlType|null` 包装类型 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct(...)` | 构造函数（支持名称、描述、kind、ofType、各集合参数） |
| `get_data()` | 返回类型数据数组 |

---

### GraphqlField

> 文件：`graphql_model.php`
>
> GraphQL 字段描述类，实现 `GraphqlDatable`。

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 字段名 |
| `$description` | `public string` 字段描述 |
| `$args` | `public array<GraphqlInputValue>` 参数列表 |
| `$type` | `public GraphqlType` 字段类型 |
| `$isDeprecated` | `public bool` 是否弃用 |
| `$deprecationReason` | `public null` 弃用原因 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($name, GraphqlType $type, $description, $args, $isDeprecated, $deprecationReason)` | 构造函数 |
| `get_data()` | 返回字段数据数组 |

---

### GraphqlInputValue

> 文件：`graphql_model.php`
>
> GraphQL 输入值（参数/输入字段）描述类，实现 `GraphqlDatable`。

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 名称 |
| `$description` | `public string` 描述 |
| `$type` | `public GraphqlType` 类型 |
| `$defaultValue` | `public mixed` 默认值 |
| `$isDeprecated` | `public bool` 是否弃用 |
| `$deprecationReason` | `public null` 弃用原因 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($name, GraphqlType $type, $description, $defaultValue, $isDeprecated, $deprecationReason)` | 构造函数 |
| `get_data()` | 返回数据数组 |

---

### GraphqlEnumValue

> 文件：`graphql_model.php`
>
> GraphQL 枚举值描述类，实现 `GraphqlDatable`。

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 枚举名 |
| `$description` | `public string` 描述 |
| `$isDeprecated` | `public bool` 是否弃用 |
| `$deprecationReason` | `public null` 弃用原因 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($name, $description, $isDeprecated, $deprecationReason)` | 构造函数 |
| `get_data()` | 返回数据数组 |

---

### GraphqlDirective

> 文件：`graphql_model.php`
>
> GraphQL 指令描述类，实现 `GraphqlDatable`。

**常量**

`LOCATION_QUERY`、`LOCATION_MUTATION`、`LOCATION_SUBSCRIPTION`、`LOCATION_FIELD`、`LOCATION_FRAGMENT_DEFINITION`、`LOCATION_FRAGMENT_SPREAD`、`LOCATION_INLINE_FRAGMENT`、`LOCATION_VARIABLE_DEFINITION`、`LOCATION_SCHEMA`、`LOCATION_SCALAR`、`LOCATION_OBJECT`、`LOCATION_FIELD_DEFINITION`、`LOCATION_ARGUMENT_DEFINITION`、`LOCATION_INTERFACE`、`LOCATION_UNION`、`LOCATION_ENUM`、`LOCATION_ENUM_VALUE`、`LOCATION_INPUT_OBJECT`、`LOCATION_INPUT_FIELD_DEFINITION`

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 指令名 |
| `$description` | `public string` 描述 |
| `$args` | `public array<GraphqlInputValue>` 参数 |
| `$locations` | `public array` LOCATION_XX 常量集合 |
| `$isRepeatable` | `public bool` 是否可重复 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct($name, $description, $args, $locations, $isRepeatable)` | 构造函数 |
| `get_data()` | 返回数据数组 |

---

### GraphqlQueryWhere / GraphqlQueryClause

> 文件：`graphql_model.php`

**GraphqlQueryWhere** — GraphQL 查询条件类

| 成员 | 类型/说明 |
| --- | --- |
| `$column` | `public string` 字段名 |
| `$op` | `public string` 比较操作符 |
| `$value` | `public string\|array` 查询值 |
| `$andor` | `public string` 与下一个 where 的拼接方式（`and` / `or`） |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct(string $column, string $op, $value, string $andor = "and")` | 构造函数 |
| `build($wheres)` | `static` 从数组批量构建 `GraphqlQueryWhere[]` |

**GraphqlQueryClause** — GraphQL 分页/排序/分组条件类

| 成员 | 类型/说明 |
| --- | --- |
| `$orderby` | `public string` 排序字段 |
| `$groupby` | `public string` 分组字段 |
| `$sort` | `public string` ASC / DESC（默认 DESC） |
| `$page` | `public int` 当前页（默认 1） |
| `$limit` | `public int` 每页条数（默认 10） |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct(string $orderby, string $groupby, string $sort, int $page, int $limit)` | 构造函数 |
| `build(array $clause)` | `static` 从数组构建条件 |

---

### GraphqlSearchArg / GraphqlSearchNode

> 文件：`graphql_model.php`

**GraphqlSearchArg** — GraphQL 查询参数

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 参数名 |
| `$value` | `public mixed` 参数值 |

**GraphqlSearchNode** — GraphQL 查询节点

| 成员 | 类型/说明 |
| --- | --- |
| `$name` | `public string` 查询内容 |
| `$args` | `public array<GraphqlSearchArg>` 参数 |
| `$alias` | `public string` 别名 |
| `$sub` | `public array<GraphqlSearchNode>` 子节点 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `has_value()` | 是否有名称（判断节点是否有效） |

---

### GraphqlIntrospection / GraphqlIntrospectionValues

> 文件：`graphql_model.php`

**GraphqlIntrospection** — GraphQL 内省查询处理类

| 成员 | 类型/说明 |
| --- | --- |
| `$_searchNode` | `protected GraphqlSearchNode` 查询结构体 |
| `$_valueInfo` | `protected array` 值信息，格式 `[NAME=>INFO]` |

**方法**

| 方法 | 说明 |
| --- | --- |
| `find_search_node_by_name($searchNodes, string $name)` | `static` 根据名称在 searchNodes 中查找对应 searchNode |
| `__construct(GraphqlSearchNode $searchNode, array $valueInfo)` | 构造函数 |
| `search(): array` | 根据 searchNode 查询 valueInfo 并返回满足条件的内容 |
| `pick(GraphqlSearchNode $searchNode, array $valueInfo): array` | 根据传入的名字返回对应的内容 |

**GraphqlIntrospectionValues** — 值数组构成的内省查询（继承 `GraphqlIntrospection`）

| 方法 | 说明 |
| --- | --- |
| `search(): array` | 对值数组逐项执行内省查询 |

---

### GraphqlResult

> 文件：`graphql_model.php`
>
> GraphQL 响应视图，继承 `YZE_JSON_View`。

**方法**

| 方法 | 说明 |
| --- | --- |
| `error($controller, $message, $code, $data)` | `static` 构建失败响应 `{errors:[message], data}` |
| `success($controller, $data)` | `static` 构建成功响应 `{data}` |

---

### GraphqlCustomType

> 文件：`graphql_model.php`
>
> 在 `YZE_GRAPHQL_CUSTOM_QUERY_TYPE` Hook 中定义的自定义类型，是 `GraphqlType` 和 `GraphqlField` 的集合体。

| 成员 | 类型/说明 |
| --- | --- |
| `$args` | `public array<GraphqlInputValue>` 查询参数数组 |
| `$isDeprecated` | `public bool` 是否弃用 |
| `$deprecationReason` | `public null` 弃用原因 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `__construct(string $name, string $description, array $args, string $kind, bool $isDeprecated, $deprecationReason)` | 构造函数 |
| `get_data()` | 返回父类数据 |

---

### Trait：Graphql_Query

> 文件：`graphql_query.trait.php`
>
> GraphQL 查询处理 trait，被 `Graphql_Controller` 与 `YZE_Model` 使用。

| 成员 | 类型/说明 |
| --- | --- |
| `$models` | `private array` 已发现的 model 集合缓存 |

**方法**

| 方法 | 说明 |
| --- | --- |
| `get_andor($op)` | `private static` 校验并返回 `and` / `or` |
| `filter_array_value($dba, $values)` | `private static` 将值数组转义并 join 成 `(...)` |
| `get_op($op)` | `private static` 校验并规范化比较操作符 |
| `get_models()` | `private` 获取（并缓存）系统所有 model |
| `find_All_Models(): array` | `private` 查找系统中所有启用 graphql 的 Model，返回 `['table name'=>'Model Class Full Name']` |
| `model_query($class, GraphqlSearchNode $node, &$total, $wheres, $clause, $id)` | `static` 针对 model 的查询，返回数组结果 |
| `model_get(GraphqlSearchNode $node)` | 单条 model 的 graphql 查询 |

---

### Trait：Graphql_Mutation

> 文件：`graphql_mutation.trait.php`
>
> GraphQL mutation（变更）处理 trait。

**方法**

| 方法 | 说明 |
| --- | --- |
| `getModelMutations(array $models)` | 根据 models 生成 mutation 定义，key 为 mutation 名称，value 为 `GraphqlField` |
| `mutation(array $nodes)` | 执行变更：对每个 node 生成 / 更新或删除对应 model，返回结果 |

---

### Trait：Graphql__Schema

> 文件：`graphql__schema.trait.php`
>
> GraphQL schema 内省处理 trait。

**方法**

| 方法 | 说明 |
| --- | --- |
| `__schema(GraphqlSearchNode $node)` | 内省 schema 主入口，支持 `__typename` / `queryType` / `mutationType` / `subscriptionType` / `types` / `directives` / `description` |
| `schema_Query_type($models, $node)` | `private` 构建查询类型（每个 model 一个 field，含 count） |
| `schema_Subscription_Type($node)` | `private` 返回系统有哪些订阅操作（当前返回 null） |
| `schema_Mutation_Type($models, $node)` | `private` 构建变更类型 |
| `get_model_schema($models, $node)` | `private` 生成各 model 的类型 schema |
| `all_schema_Types($models, $node)` | `private` 返回系统所有查询类型（含基础类型、内省类型、enum、Where、Clause） |
| `get_non_list_type($name, $kind)` | `private` 构建 `NON_NULL > LIST > NON_NULL > type` 包装类型 |
| `get__typekind()` | `private` 返回 `__TypeKind` 枚举定义 |
| `get__EnumValue()` | `private` 返回 `__EnumValue` 类型定义 |
| `get__InputValue()` | `private` 返回 `__InputValue` 类型定义 |
| `get__DirectiveLocation()` | `private` 返回 `__DirectiveLocation` 枚举定义 |
| `get__Directive()` | `private` 返回 `__Directive` 类型定义 |
| `get__Type()` | `private` 返回 `__Type` 类型定义 |
| `get__schema()` | `private` 返回 `__Schema` 类型定义 |
| `get__fields()` | `private` 返回 `__Field` 类型定义 |
| `_schema_Basic_type($node)` | `private` 基础标量类型定义 |
| `get_Model_Enum($model, $node, $columnName)` | `private` 获取 model enum 字段的枚举值 |
| `get_Model_Fields($model)` | `private` 根据 schema 查询返回 model 字段信息（含自定义 field、关联表 field） |
| `get_Model_Where_Fields()` | `private` Where 输入类型字段 |
| `get_Model_Clause_Fields()` | `private` Clause 输入类型字段 |
| `get_Model_Args($node)` | `private` 获取 model 查询参数（id / wheres / clause） |
| `schema_Directives($node)` | `private` 返回系统指令类型（include、skip、defer、stream、deprecated、specifiedBy） |
| `custom_query_types($node, &$results)` | `private` 触发 `YZE_GRAPHQL_CUSTOM_QUERY_TYPE` hook 收集自定义类型 |
| `custom_fields(&$results, &$names)` | `private` 触发 hook 收集自定义 field |

---

### Trait：Graphql__Type

> 文件：`graphql__type.trait.php`
>
> GraphQL type 内省处理 trait。

**方法**

| 方法 | 说明 |
| --- | --- |
| `__type($node)` | 通过内省查询支持的类型（支持 name 参数过滤） |

---

### Trait：Graphql__Typename

> 文件：`graphql__typename.trait.php`
>
> GraphQL typename 内省处理 trait。

**方法**

| 方法 | 说明 |
| --- | --- |
| `__typename($node)` | 返回当前查询类型名 `YangzieQuery` |

---

## 7. 全局函数

### i18n 相关函数

> 文件：`i18n.php`

| 函数 | 说明 |
| --- | --- |
| `translate($text, $domain = 'default')` | 翻译指定的文本，未找到翻译时返回原文本 |
| `__($text, $domain = 'default')` | translate 的别名（短名形式） |
| `_e($text, $domain = 'default')` | 翻译并输出文本 |
| `load_textdomain($domain, $mofile)` | 从 MO 文件加载指定 domain 的翻译 |
| `get_accept_language()` | 获取当前语言（locale），优先 hook，其次 Accept-Language |
| `load_default_textdomain()` | 加载默认 domain 的翻译文件（`i18n/{locale}.mo`） |

---

### 文件操作函数

> 文件：`file.php`

| 函数 | 说明 |
| --- | --- |
| `yze_isimage($file)` | 通过后缀名判断文件是否是图片 |
| `yze_get_abs_path($path, $in = '')` | 将路径格式化为绝对路径，去除 `.` 和 `..` |
| `yze_remove_path($path, $need_remove)` | 从路径中删除指定子串 |
| `yze_move_file($src_file, $dist_dir)` | 移动文件到目标目录，返回目标文件路径或 false |
| `yze_copy_file($src_file, $dist_dir)` | 拷贝文件到目标目录（目录不存在自动创建），返回路径或 false |
| `yze_copy_dir($srcDir, $destDir)` | 递归拷贝目录 |
| `yze_make_dirs($dirs)` | 递归创建目录 |

---

### HTML 辅助函数

> 文件：`html.php`

| 函数 | 说明 |
| --- | --- |
| `yze_die(YZE_View_Adapter $view, YZE_Resource_Controller $controller)` | 显示视图（error 布局）并停止执行 |
| `yze_controller_error($begin_tag, $end_tag)` | 返回当前请求保存的异常消息（含标签包裹） |
| `yze_merge_query_string($url, $args, $format)` | 在 url 参数基础上合并 args 并返回 |
| `yze_js_bundle($bundle, $version)` | 输出 js 加载 script 代码 |
| `yze_css_bundle($bundle, $version)` | 输出 css 加载 link 代码 |
| `yze_module_js_bundle($bundle, $version)` | 输出 module 指定的 js bundle |
| `yze_module_css_bundle($bundle, $version)` | 输出 module 指定的 css bundle |
| `yze_module_asset_url($src, $version)` | 返回模块资源的完整访问 url |

---

### 启动与路由函数

> 文件：`startup.php`

| 函数 | 说明 |
| --- | --- |
| `yze_autoload($class)` | 自动加载类文件（按类名命名规则定位到模块下文件，支持 phar） |
| `yze_load_app()` | 加载应用及所有模块，初始化配置（app 配置、模块 include 文件、hooks、注册路由） |
| `yze_handle_request()` | yangzie 处理入口：请求初始化 → 控制器加载 → 认证 → 分发调度 → 提交事务；异常回滚并交由异常控制器输出 |

---

## 8. 全局常量

> 文件：`hooks.php`

| 常量 | 说明 |
| --- | --- |
| `YZE_HOOK_BEFORE_DISPATCH` | 在开始执行具体 action 前调用 |
| `YZE_HOOK_AFTER_DISPATCH` | 在执行具体 action 后调用 |
| `YZE_HOOK_MODEL_UPDATE` | 实际更新数据库之后调用，传入更新的 model |
| `YZE_HOOK_MODEL_INSERT` | 实际插入数据库之后调用，传入 model |
| `YZE_HOOK_MODEL_DELETE` | 实际删除数据库之后调用，传入 model |
| `YZE_HOOK_MODEL_SELECT` | 查询回调，传入查询出来的 model 数组 |
| `YZE_HOOK_BEFORE_DO_EXCEPTION` | 处理流程出现异常，在执行控制器 exception 前调用 |
| `YZE_HOOK_YZE_EXCEPTION` | 框架处理出现异常的 hook，传入 `["exception"=>, "controller"=>, "response"=>]` |
| `YZE_HOOK_GET_USER_ARO_NAME` | 获取登录用户的 aro |
| `YZE_HOOK_FILTER_URI` | 解析地址得到请求 url，uri 过滤 |
| `YZE_HOOK_GET_LOGIN_USER` | 取得登录的用户 |
| `YZE_HOOK_SET_LOGIN_USER` | 设置登录的用户 |
| `YZE_HOOK_AUTO_LOAD_CLASS` | 自动加载类无法找到时触发，传入类名 |
| `YZE_HOOK_GET_LOCALE` | 获取当前语言设置 |

> 文件：`graphql_model.php`

| 常量 | 说明 |
| --- | --- |
| `YZE_GRAPHQL_CUSTOM_QUERY_TYPE` | 通过该 hook 返回自定义的 graphql field / type（`GraphqlCustomType`） |
| `YZE_GRAPHQL_CUSTOM_SEARCH` | 对自定义查询类型执行查询，传入 `['search'=>$node, 'rsts'=>[], 'total'=>0]` |

---

*文档生成时间：2026-08-22 ｜ 基于 yangzie v3.0.1 源码与注释自动整理*
