<?php

namespace app;

use \yangzie\YZE_Hook;

/**
 * 按照yangzie框架规范放置的文件和类命名，不需要手动引入代码文件，框架可自动引入代码文件；
 * 其他情况框架找不到类文件时，会触发该hook，传入类全名，该hook中include对应的代码文件;类命名有如下几种情况
 * <ol>
 * <li>控制器文件：文件放置在app/modules/模块名/controllers/控制器名_controller.class.php, 类命名规则：控制器名_Controller</li>
 * <li>模型文件：文件放置在app/modules/模块名/models/模型名_model.class.php, 类命名规则：模型名_Model</li>
 * <li>模型文件逻辑代码文件：文件放置在app/modules/模块名/models/模型名_model_method.trait.php, 类命名规则：模型名_Model_Method</li>
 * <li>模块的配置文件：文件放置在app/modules/模块名/__config__.php, 类命名规则：模块名_Module</li>
 * <li>视图文件：文件可放置在app下任何地方, 但命名空间和和其存储路径要对应，比如放置在app/foo/bar.view.php，那么其命名空间就是namespace app\foo，文件名命名规则：视图.view.php, 类命名规则：视图名_View</li>
 * <li>其他情况下的类文件，可以放置在app任何地方, 但命名空间和和其存储路径要对应，比如放置在app/foo/bar.class.php，那么其命名空间就是namespace app\foo，文件名命名规则：类名.class.php或者类名.trait.php</li>
 * </ol>
 */
YZE_Hook::add_hook(YZE_HOOK_AUTO_LOAD_CLASS, function ( &$class ) {

});
?>
