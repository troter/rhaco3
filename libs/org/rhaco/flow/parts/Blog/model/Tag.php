<?php
namespace org\rhaco\flow\parts\Blog\model;
/**
 * ブログ用のDBモデル
 * @author tokushima
 * @var serial $id
 * @var string $name
 */
class Tag extends \org\rhaco\store\db\Dao{
	protected $id;
	protected $name;
}