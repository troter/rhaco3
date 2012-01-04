<?php
namespace org\rhaco\store\db;
/**
 * DBコントローラ
 * @author tokushima
 * @var string $type モジュールのクラス
 * @var string $host 接続先ホスト
 * @var string $dbname 接続先DB名
 * @var string $user 接続ユーザ
 * @var string $password 接続パスワード
 * @var integer $port 接続ポート番号
 * @var string $sock 接続先のソケットパス(unix_socket)
 * @var string $encode エンコード
 */
class Dbc extends \org\rhaco\Object implements \Iterator{
	protected $type;
	protected $host;
	protected $dbname;
	protected $user;
	protected $password;
	protected $port;
	protected $sock;
	protected $encode;

	private $connection;
	private $statement;
	private $resultset;
	private $resultset_counter;

	static private $log_level;
	
	/**
	 * コンストラクタ
	 * @param string $json 接続情報のjson文字列
	 */
	public function __new__($json=null){
		if(is_string($json)) $json = empty($json) ? null : json_decode($json,true);
		if(is_array($json)){
			foreach(array('type','host','dbname','user','password','port','sock','encode') as $k){
				if(isset($json[$k])) $this->{$k}($json[$k]);
			}
			$this->connect();
		}
		switch($log_level = \org\rhaco\Conf::get('log_level')){
			case 'error':
			case 'warn':
			case 'info':
			case 'debug':
				self::$log_level = $log_level;
		}
	}
	/**
	 * 接続
	 * @throws \RuntimeException
	 */
	public function connect(){
		if($this->connection === null){
			if(empty($this->type)) $this->type('org.rhaco.store.db.module.Mysql');
			if(empty($this->encode)) $this->encode('utf8');
			if(empty($this->user)){
				$this->user('root');
				$this->password('root');
			}
			if(empty($this->type) || !class_exists($this->type)) throw new \RuntimeException('undef type');
			$r = new \ReflectionClass($this->type);
			$module = $r->newInstanceArgs(array($this->encode));
			$this->set_object_module($module);
			$this->connection = $this->object_module('connect',$this->dbname,$this->host,$this->port,$this->user,$this->password,$this->sock);
			if(empty($this->connection)) throw new \RuntimeException('connection fail '.$this->dbname);
			$this->connection->beginTransaction();
		}
		return $this;
	}
	protected function __set_type__($type){
		if(!empty($type)){
			$this->type = str_replace('.',"\\",$type);
			if($this->type[0] !== "\\") $this->type = "\\".$this->type;
		}
		return $this;
	}
	protected function __del__(){
		if($this->connection !== null){
			try{
				$this->connection->commit();
			}catch(\Exception $e){}
		}
	}
	/**
	 * コミットする
	 */
	public function commit(){
		$this->connection->commit();
		$this->connection->beginTransaction();
		if(($log_level = self::$log_level) !== null) \org\rhaco\Log::$log_level('#commit');
	}
	/**
	 * ロールバックする
	 */
	public function rollback(){
		$this->connection->rollBack();
		$this->connection->beginTransaction();
		if(($log_level = self::$log_level) !== null) \org\rhaco\Log::$log_level('#rollback');
	}
	/**
	 * 文を実行する準備を行う
	 * @param string $sql
	 * @return PDOStatement
	 */
	public function prepare($sql){
		return $this->connection->prepare($sql);
	}
	/**
	 * SQL ステートメントを実行する
	 * @param string $sql 実行するSQL
	 */
	public function query($sql){
		$args = func_get_args();
		$this->statement = $this->prepare($sql);
		if($this->statement === false) throw new \LogicException($sql);
		array_shift($args);
		$this->statement->execute($args);
		$errors = $this->statement->errorInfo();
		if(($log_level = self::$log_level) !== null) \org\rhaco\Log::$log_level($sql,$args);
		if(isset($errors[1])){
			$this->rollback();
			throw new \LogicException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : '').' : '.$sql);
		}
		return $this;
	}
	/**
	 * 直前に実行したSQL ステートメントに値を変更して実行する
	 */
	public function re(){
		if(!isset($this->statement)) throw new \LogicException();
		$args = func_get_args();
		$this->statement->execute($args);
		$errors = $this->statement->errorInfo();
		if(($log_level = self::$log_level) !== null) \org\rhaco\Log::$log_level('#requery',$args);
		if(isset($errors[1])){
			$this->rollback();
			throw new \LogicException('['.$errors[1].'] '.(isset($errors[2]) ? $errors[2] : '').' : #requery');
		}
		return $this;
	}
	/**
	 * 結果セットから次の行を取得する
	 * @param string $name 特定のカラム名
	 * @return string/arrray
	 */
	public function next_result($name=null){
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
		if($this->resultset !== false){
			if($name === null) return $this->resultset;
			return (isset($this->resultset[$name])) ? $this->resultset[$name] : null;
		}
		return null;
	}
	/**
	 * @see \Iterator
	 */
	public function rewind(){
		$this->resultset_counter = 0;
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
	}
	/**
	 * @see \Iterator
	 */
	public function current(){
		return $this->resultset;
	}
	/**
	 * @see \Iterator
	 */
	public function key(){
		return $this->resultset_counter++;
	}
	/**
	 * @see \Iterator
	 */
	public function valid(){
		return ($this->resultset !== false);
	}
	/**
	 * @see \Iterator
	 */
	public function next(){
		$this->resultset = $this->statement->fetch(\PDO::FETCH_ASSOC);
	}
}
