<?php
namespace yangzie;
use Attribute;

/**
 * 模型字段元数据注解，标注在 YZE_Model 子类的字段属性上，
 * 用于声明字段的类型、是否可空、长度及默认值
 *
 * 用法：
 * <pre>
 * #[Column(type: 'string', nullable: false, length: 45, default: '')]
 * private ?string $name;
 * </pre>
 *
 * 框架在 YZE_Model::get_columns() 中通过反射读取该注解生成字段配置；
 *
 * @package yangzie
 */
#[Attribute(Attribute::TARGET_PROPERTY)]
final class Column {
	/**
	 * @param string $type     字段类型：int、float、string、date、enum
	 * @param bool   $nullable 是否允许为空，对应数据库字段的 null 属性
	 * @param int    $length   字段最大长度，0 表示无限制
	 * @param mixed  $default  字段默认值
	 * @param bool   $encrypt  字段是否加密存储（保存时自动加密、读取时自动解密）
	 */
	public function __construct(
		public string $type = 'string',
		public bool $nullable = true,
		public int $length = 0,
		public mixed $default = null,
		public bool $encrypt = false,
	) {}
}

