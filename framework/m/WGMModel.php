<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once dirname(__FILE__)."/../../waggo.php";
require_once dirname(__FILE__)."/../v6/WGV6Object.php";
require_once dirname(__FILE__)."/WGMModelFilter.php";
require_once dirname(__FILE__)."/WGMVarsObject.php";

global $WGMModelID;
$WGMModelID = [];

//
class WGMModelGetKeys
{
	private $keys;
	private $model;
	public function __construct($model)
	{
		$this->keys  = [];
		$this->model = $model;
	}

	/**
	 * @return WGMModel
	 */
	public function getModel()		{ return $this->model;	}
	public function setKeys($keys)	{ $this->keys = $keys;	}
	public function getKeys()		{ return $this->keys;	}
}

/**
 * SQL基底モデル(O/Rマッピング用基底モデル)
 */
class WGMModel
{
	const N=0,S=1,B=2,T=3,D=4,P=5;
	const JNULL=0, JINNER=1, JLEFT=2, JRIGHT=3;

	public $uniqueids;
	public $avars, $vars;
	public $fields;
	public $dbms;

	protected $echo;
	public    $assign, $tablename, $alias, $backvars, $initymds, $updymds, $recs;
	protected $defaultfilter;
	protected $oid;
	protected $conditions;

	/**
	 * @var WGV6BasicPagination
	 */
	protected $pager;

	protected $joins;

	// SQL dependency
	protected $depconf;

	// SQL params
	protected $a_orderby, $p_order, $s_offset, $s_limit;

	public function __construct($tablename,$dbms=null)
	{
		global $WGMModelID;

		$this->uniqueids=[];

		$kid = $tablename[0];
		if(empty($WGMModelID[$kid])) $WGMModelID[$kid]=1; else $WGMModelID[$kid]++;
		$id  = $WGMModelID[$kid];

		$this->alias = $kid.$id;
		$this->dbms  = (is_null($dbms)) ? _QC() : $dbms ;

		$this->initDBMSConf();

		$this->defaultfilter = new WGMModelFILTER();

		$this->tablename		= $tablename;
		$this->fields			= [];
		$this->assign			= [];
		$this->vars				= [];
		$this->avars			= [];
		$this->backvars			= [];
		$this->joins			= [];
		$this->initFields($tablename);
		$this->recs				= 0;
		$this->echo				= WG_MODELDEBUG;
		$this->conditions		= [];
		$this->initymds			= [];
		$this->updymds			= [];
		$this->a_orderby		= [];
		$this->p_order			= 1 << 31;
	}

	protected function initDBMSConf()
	{
		$this->depconf = new stdClass();
		if( $this->dbms instanceof WGDBMSPostgreSQL )
		{
			$this->depconf->N = '/^(int|smallint)/';
			$this->depconf->T = '/^(date|timestamp)/';
			$this->depconf->S = '/^(char|text|time|varchar|json)/';
			$this->depconf->D = '/^(double|real|numeric)/';
			$this->depconf->B = '/^bool/';
			$this->depconf->P = '/^point/';

			$this->depconf->BOOL_TRUE = 't';
		}
		else if( $this->dbms instanceof WGDBMSMySQL )
		{
			$this->depconf->N = '/^(int|smallint)/';
			$this->depconf->T = '/^(date|timestamp)/';
			$this->depconf->S = '/^(char|text|time|varchar|json)/';
			$this->depconf->D = '/^(double|real|numeric)/';
			$this->depconf->B = '/^tinyint\(1\)/';
			$this->depconf->P = '/^point/';

			$this->depconf->BOOL_TRUE = '1';
		}
		else
		{
			$this->logFatal("DBMS の種別が特定できません。");
		}
	}

	protected function logInfo($s)
	{
		if( $this->echo ) wg_log_write(WGLOG_INFO, $s);
	}

	protected function logInfoDump($v)
	{
		if( $this->echo ) wg_log_write(WGLOG_INFO, $v);
	}

	protected function logWarning($s)
	{
		wg_log_write(WGLOG_WARNING, $s);
	}

	protected function logError($s)
	{
		wg_log_write(WGLOG_ERROR, $s);
	}

	protected function logFatal($s)
	{
		wg_log_write(WGLOG_FATAL, $s);
	}

	private function toFlatArray($a)
	{
		$buf = [];
		foreach($a as $v)
			if(is_array($v))	$buf=array_merge($buf,$this->toFlatArray($v));
			else				$buf[]=$v;
		return $buf;
	}

	protected function getFieldTypeFromFormat($format_type)
	{
		if(     preg_match($this->depconf->N ,$format_type)) return self::N;
		else if(preg_match($this->depconf->T ,$format_type)) return self::T;
		else if(preg_match($this->depconf->S ,$format_type)) return self::S;
		else if(preg_match($this->depconf->D ,$format_type)) return self::D;
		else if(preg_match($this->depconf->B ,$format_type)) return self::B;
		else if(preg_match($this->depconf->P ,$format_type)) return self::P;
		else return false;
	}

	private function getOID($tablename)
	{
		if( $this->dbms instanceof WGDBMSPostgreSQL )
		{
			list($oid,$nspname,$relname) =
				$this->dbms->QQ(
					"SELECT c.oid,n.nspname,c.relname FROM pg_catalog.pg_class c ".
					"LEFT JOIN pg_catalog.pg_namespace n ON n.oid=c.relnamespace ".
					"WHERE pg_catalog.pg_table_is_visible(c.oid) ".
					"AND c.relname=%s;", $this->dbms->S($tablename));
			return array($oid,$nspname,$relname);
		}
		else if( $this->dbms instanceof WGDBMSMySQL )
		{
			return array($tablename,$tablename,$tablename);
		}
		else
		{
			$this->logFatal("Unrecognized DBMS type");
		}
	}

	protected function initFields($tablename)
	{
		if( $this->dbms instanceof WGDBMSPostgreSQL )
		{
			list($oid,$nspname,$relname) = $this->getOID($tablename);
			$this->dbms->Q(
				"SELECT a.attname,pg_catalog.format_type(a.atttypid,a.atttypmod),".
				"(SELECT d.adsrc FROM pg_catalog.pg_attrdef d ".
				"WHERE d.adrelid=a.attrelid and d.adnum=a.attnum and a.atthasdef),a.attnotnull,a.attnum ".
				"FROM pg_catalog.pg_attribute a ".
				"WHERE a.attrelid=%s AND a.attnum>0 AND NOT a.attisdropped;",
				$this->dbms->S($oid));

			foreach($this->dbms->FALL() as $f)
			{
				list($name,$format_type,$deflt,$notnull,$num) = $f;
				$type = $this->getFieldTypeFromFormat($format_type);
				if( $type===false ) $this->logFatal("Unrecognized field type, {$format_type} on PostgreSQL/WGMModel");

				$this->fields[$name] = array($type,$format_type,($notnull=="t"),$this->getAlias().".".$name);
				$this->logInfo("Fields[{$name}] = [Type:{$type}] [Format:{$format_type}] [NotNull:{$notnull}] [Func:{$name}]\n");
			}
		}
		else if( $this->dbms instanceof WGDBMSMySQL )
		{
			$this->dbms->Q("DESCRIBE %s", $tablename);

			foreach($this->dbms->FALL() as $f)
			{
				list($name,$format_type,$null,$key,$deflt,$extra) = $f;
				$type = $this->getFieldTypeFromFormat($format_type);
				if( $type===false ) $this->logFatal("Unrecognized field type, {$format_type} on mySQL/WGMModel");

				$this->fields[$name] = array($type,$format_type,($null=="YES"),$this->getAlias().".".$name);
				$this->logInfo("Fields[{$name}] = [Type:{$type}] [Format:{$format_type}] [Null:{$null}] [Func:{$name}]\n");
			}
		}
		else
		{
			$this->logFatal("Unrecognized DBMS type");
		}
	}

	protected function initFieldsPrimarykey($tablename)
	{
		if( $this->dbms instanceof WGDBMSPostgreSQL )
		{
			list($oid,$nspname,$relname) = $this->getOID($tablename);
			list($pk) = $this->dbms->QQ(
				"SELECT c2.relname ".
				"FROM pg_catalog.pg_class c, pg_catalog.pg_class c2, pg_catalog.pg_index i ".
				"WHERE c.oid = %s AND c.oid = i.indrelid AND i.indexrelid = c2.oid AND i.indisprimary = true;",
				$this->dbms->S($oid));
			if(empty($pk)) return false;

			list($ioid,,) = $this->getOID($pk);
			$pks = [];
			$this->dbms->Q(
				"SELECT a.attname ".
				"FROM pg_catalog.pg_attribute a, pg_catalog.pg_index i ".
				"WHERE a.attrelid = %s AND a.attnum > 0 AND NOT a.attisdropped AND a.attrelid = i.indexrelid ".
				"ORDER BY a.attnum;",
				$this->dbms->S($ioid));
			foreach($this->dbms->FALL() as $f) $pks[] = $f["attname"];
			return $pks;
		}
		else if( $this->dbms instanceof WGDBMSMySQL )
		{
			$pks = [];
			$this->dbms->Q("DESCRIBE %s", $tablename);
			foreach($this->dbms->FALL() as $f)
			{
				list($name,$format_type,$null,$key,$deflt,$extra) = $f;
				if( $key=="PRI" ) $pks[] = $name;
				return $pks;
			}
		}
	}

	public function setField($name,$format_type,$func)
	{
		$type = $this->getFieldTypeFromFormat($format_type);
		if( $type===false ) $this->logFatal("Unrecognized field type, {$format_type}");
		$this->fields[$name] = array($type,$format_type,false,$this->expansion($func));
	}
	
	public function getTable()
	{	return $this->tablename;			}

	public function getAlias()
	{	return $this->alias;				}

	public function getFields()
	{	return array_keys($this->fields);	}

	public function getFieldType($f)
	{	return !empty($this->fields[$f][0]) ? $this->fields[$f][0] : false;	}

	public function getFieldFormat($f)
	{	return !empty($this->fields[$f][1]) ? $this->fields[$f][1] : false;	}

	public function IsAllowNullField($f)
	{	return !empty($this->fields[$f][2]) ? $this->fields[$f][2] : false;	}

	public function getPrimaryKeys()
	{	return $this->initFieldsPrimarykey($this->tablename);					}

	public function expansion($exp)
	{
		$alias = $this->getAlias();
		$cb = function($m) use ($alias) { return $alias.".".$m[1]; };
		$exp = preg_replace_callback('/\{(\w+?)\}/', $cb, $exp);
		return $exp;
	}
	
	public function setAutoTimestamp($initymds=array("initymd"),$updymds=array("updymd"))
	{
		if(!is_array($initymds)||!is_array($updymds)) $this->logFatal("setAutoTimestamp is not an array");
		$this->initymds = $initymds;
		$this->updymds  = $updymds;
	}

	public function getRecs()
	{
		return $this->recs;
	}

	public function setFilter($key,$filterobj)
	{
		if(!$filterobj instanceof WGMModelFilter)
		$this->logFatal("The filter instance to be set to '{$key}' field does not inherit the WGMModelFILTER.");
		$this->assign[$key]["filter"] = $filterobj;
	}

	public function assign($key,$viewobj,$filterobj=null)
	{
		if(!isset($this->fields[$key])) $this->logFatal("'{$key}' not found.");
		if(!$viewobj instanceof WGV6Object) $this->logFatal("The filter instance to be set to '{$key}' field does not inherit the WGV6Object.");
		$this->assign[$key]["viewobj"] = $viewobj;
		$this->assign[$key]["filter"] = ($filterobj instanceof WGMModelFILTER)?$filterobj:$this->defaultfilter;
	}

	public function release($key)
	{	unset($this->assign[$key]);		}

	protected function checkNullField($k,$v)
	{
		if($this->fields[$k][2] && (strtolower($v)==="null" || is_null($v)))
			$this->logFatal("Field '{$k}' does not allow NULL.");
	}

	protected function checkNull($d,$is_all_fields=false)
	{
		$scan = ($is_all_fields) ? $this->fields : $d;
		foreach($scan as $k=>$v) $this->checkNullField($k,$v);
	}

	protected function posValue($pos)
	{
		if(preg_match('/\(([\-0-9\.]+),([\-0-9\.]+)\)/',$pos,$m)) return array($m[1],$m[2]);
		else return null;
	}

	protected function fieldValue($key,$val,$dir)
	{
		if($dir!="PHP" && $dir!="DB") $this->logFatal("Internal error on fieldValue.");

		$anl = !$this->fields[$key][2];
		$v   = null;
		switch($this->fields[$key][0])
		{
			case self::N: $v=($dir=="DB") ? $this->dbms->N($val,$anl) : (int)$val;							break;
			case self::S: $v=($dir=="DB") ? $this->dbms->S($val,$anl) : $val;								break;
			case self::B: $v=($dir=="DB") ? $this->dbms->B($val,$anl) : ($val==$this->depconf->BOOL_TRUE);	break;
			case self::T: $v=($dir=="DB") ? $this->dbms->T($val,$anl) : $val;								break;
			case self::D: $v=($dir=="DB") ? $this->dbms->D($val,$anl) : (double)$val;						break;
			case self::P: $v=($dir=="DB") ? $this->dbms->P($val,$anl) : $this->posValue($val);				break;
			default:
				$this->logFatal("Field '{$key}' conversion failed.");
		}

		$this->logInfo("[{$this->tablename}] {$this->alias}.{$key} src[{$val}] [to {$dir}] dst[{$v}]\n");

		if($dir=="DB" && $this->fields[$key][2] && $v==="null")
			$this->logFatal("Field '{$key}' does not allow NULL.");

		return $v;
	}

	protected function compareField($key,$v1,$v2)
	{
		switch($this->fields[$key][0])
		{
			case self::N: return ($v1==$v2);
			case self::S:
			case self::B:
			case self::D: return ($v1===$v2);
			case self::T: return (wg_timediff($v1,$v2)===0);
			case self::P: return ($v1[0]==$v2[0] && $v1[1]==$v2[1]);
		}
		$this->logFatal("Unrecognized field type, '{$key}'.");
	}

	protected function setAssignedValue($k,$v) // model(1)-->view(*)
	{
		if(isset($this->assign[$k]["viewobj"]))
			$this->assign[$k]["filter"]->modelToView($this->assign[$k]["viewobj"],$this->assign[$k]["filter"]->output($v));
		else
			$this->vars[$k]=$v;
	}

	protected function getAssignedValue($k)    // view(*)-->model(1)
	{
		if(isset($this->assign[$k]["viewobj"]) && !$this->assign[$k]["viewobj"]->isShowOnly())
			return $this->assign[$k]["filter"]->input($this->assign[$k]["filter"]->viewToModel($this->assign[$k]["viewobj"]));
		else
			return $this->vars[$k];
	}

	public function unJoin()
	{
		$this->joins = [];
		return $this;
	}

	public function left($model,$on)
	{
		$this->joins[] = array(self::JLEFT,$model,$on);
		return $this;
	}

	public function right($model,$on)
	{
		$this->joins[] = array(self::JRIGHT,$model,$on);
		return $this;
	}

	public function inner($model,$on)
	{
		$this->joins[] = array(self::JINNER,$model,$on);
		return $this;
	}

	public function fieldVars($key)
	{
		wg_log_write(WGLOG_WARNING, "fieldVars is a deprecated method. use getFieldVars.");
		return $this->getFieldVars($key);
	}

	public function selectVars($kf,$df)
	{
		wg_log_write(WGLOG_WARNING, "selectVars is a deprecated method. use getSelectVars.");
		return $this->getSelectVars($kf, $df);
	}

	/**
	 * 特定のフィールドのみのデータを、配列として生成する。
	 * @param String $dataField データとなるフィールド
	 * @return array データ配列[$dataFieldの値,...]
	 */
	public function getFieldVars($dataField)
	{
		$r = [];
		foreach($this->avars as $av) $r[]=$av[$dataField];
		return $r;
	}

	/**
	 * 選択肢用の連想配列を生成する。
	 * @param String $keyField 選択肢のキーとなるフィールド
	 * @param String $dataField 選択肢のデータとなるフィールド
	 * @return array 選択肢を構成する連想配列[$keyFieldの値=>$dataFieldの値,...]
	 */
	public function getSelectVars($keyField,$dataField)
	{
		$r = [];
		foreach($this->avars as $av) $r[$av[$keyField]]=$av[$dataField];
		return $r;
	}

	/**
	 * 追加WHERE句を設定する。追加される条件はすべて AND で組み合わされます。
	 * @param string $where 条件。フィールド名は{}で囲むことによって、実行時に実際のフィールド名に変換されます。
	 * @return string 追加した識別子
	 */
	public function addCondition($where)
	{
		if( !isset($this->uniqueids['cond']) ) $this->uniqueids['cond'] = 0;
		$key = sprintf('cond-%d', $this->uniqueids['cond'] ++);
		$this->conditions[$key] = $this->expansion($where);
		return $key;
	}

	/**
	 * 追加WHERE句を削除する。
	 * @param string $key この条件の識別名(任意)
	 * @return WGMModel Modelインスタンス
	 */
	public function delCondition($key)
	{
		$this->conditions[$key] = null;
		unset($this->conditions[$key]);
		return $this;
	}

	/**
	 * 追加WHERE句の条件を配列で取得する。
	 * @return array 追加WHERE句の文字列。
	 */
	public function getConditions()
	{
		return $this->conditions;
	}

	/**
	 * 追加WHERE句をすべてクリアする。
	 * @return WGMModel Modelインスタンス
	 */
	public function clearConditions()
	{
		$this->conditions = [];
		return $this;
	}

	public function orderby()
	{
		$this->logInfo("**** ORDER BY\n");
		$keys = $this->toFlatArray(func_get_args());
		$this->dumpKeys($keys);
		$this->logInfo("****\n");

		$orders = [];
		foreach($keys as $k)
		{
			if( is_int($k) )
			{
				$this->p_order = $k;
			}
			else
			{
				$e = explode(" ",$k);
				$e[0] = $this->getAlias().".{$e[0]}";
				$orders[] = implode(" ",$e);
			}
		}

		$this->a_orderby = $orders;
		return $this;
	}

	public function offset($offset=null,$limit=null)
	{
		$this->logInfo("**** OFFSET LIMIT ({$offset}) ({$limit})\n");
		if( wg_is_dbms_postgresql() )
		{
			$this->s_offset = (!is_null($offset)) ? " OFFSET {$offset}" : "";
			$this->s_limit  = (!is_null($limit) ) ? " LIMIT {$limit}"   : "";
		}
		else if( wg_is_dbms_mysql() || wg_is_dbms_mariadb() )
		{
			if (!is_null($limit))
			{
				$this->s_offset = '';
				$this->s_limit = (!is_null($offset)) ? " LIMIT {$offset},{$limit}" : "LIMIT ${limit}";
			}
		}

		return $this;
	}

	public function pager($pager)
	{
		$this->pager = $pager;
		return $this;
	}

	public function findJoinModel($name)
	{
		/**
		 * @var WGMModel[] $jm
		 */
		foreach($this->joins as $jm) if($jm[1]->getTable()===$name) return $jm[1];
		return false;
	}


	public function whereOptCondExpression()
	{
		/**
		 * @var WGMModel[] $jm
		 */
		$wheres = [];
		foreach($this->joins as $jm) $wheres = array_merge($wheres,$jm[1]->whereOptCondExpression());
		foreach($this->getConditions() as $w) $wheres[] = $w;
		return $wheres;
	}

	public function whereCondExpression($keys)
	{
		$wheres = [];
		foreach($keys as $k)
		{
			if($k instanceof WGMModelGetKeys)
			{
				/**
				 * @var WGMModel $m
				 */
				$m = $k->getModel();
				$wheres = array_merge($wheres, $m->whereCondExpression($k->getKeys()));
			}
			else
			{
				$av = $this->getAssignedValue($k);
				$v  = $this->fieldValue($k,$av,"DB");
				$this->checkNullField($k,$v);
				$wheres[] = "{$this->alias}.{$k}={$v}";
			}
		}
		$wheres = array_merge($wheres, $this->whereOptCondExpression());

		return $wheres;
	}

	public function whereExpression($keys)
	{
		$wheres = [];
		foreach($keys as $k)
		{
			$av = $this->getAssignedValue($k);
			$v  = $this->fieldValue($k,$av,"DB");
			$this->checkNullField($k,$v);
			$wheres[] = "{$k}={$v}";
		}
		return $wheres;
	}

	public function getJoinExternalFields()
	{
		/**
		 * @var WGMModel[] $jm
		 */
		$fields = [];
		foreach($this->getFields() as $f) $fields[] = array($this->getAlias().".".$f, $this->fields[$f][3]);
		foreach($this->joins as $jm)
		{
			$fields = array_merge($fields, $jm[1]->getJoinExternalFields());
		}
		return $fields;
	}

	public function getJoinTables($base)
	{
		/**
		 * @var WGMModel[] $jm
		 */
		foreach($this->joins as $jm)
		{
			$on = [];
			foreach($jm[2] as $l=>$r)
			{
				$l = is_int($l) ? $r : $l;
				if(!in_array($l,$this->getFields()))  $this->logFatal("Joined LEFT, no '{$l}' field.");
				if(!in_array($r,$jm[1]->getFields())) $this->logFatal("Joined RIGHT, no '{$r}' field.");
				$on[] = $this->getAlias().".{$l}=".$jm[1]->getAlias().".{$r}";
			}
			$on = implode(" AND ",$on);
			$j  = '';
			switch($jm[0])
			{
				case self::JLEFT:	$j = "LEFT JOIN";	break;
				case self::JRIGHT:	$j = "RIGHT JOIN";	break;
				case self::JINNER:	$j = "INNER JOIN";	break;
				default:			$this->logFatal("Unrecognized join type.");
			}
			$base  = "(".$base." {$j} ".$jm[1]->getTable()." AS ".$jm[1]->getAlias()." ON {$on})";
			$base  = $jm[1]->getJoinTables($base);
		}
		return $base;
	}

	public function getJoinOrders($orders)
	{
		/**
		 * @var WGMModel[] $jm
		 */
		if( count($this->a_orderby)>0 ) $orders[] = [$this->p_order, $this->a_orderby];
		foreach($this->joins as $jm) $orders = $jm[1]->getJoinOrders($orders);
		return $orders;
	}

	public function getJoinModels()
	{
		/**
		 * @var WGMModel[] $jm
		 */
		$ret = array($this);
		foreach($this->joins as $jm) $ret = array_merge($ret, $jm[1]->getJoinModels());
		return $ret;
	}

	//    aaaa as t1
	//    aaaa as t1 inner join bbbb as t2 on t1.id=t2.id
	//               ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
	//   (aaaa as t1 inner join bbbb as t2 on t1.id=t2.id) inner join cccc as t3 on t1.id=t3.id
	//   =           ------------------------------------======================================
	//  ((aaaa as t1 inner join bbbb as t2 on t1.id=t2.id) inner join cccc as t3 on t1.id=t3.id
	// (((aaaa as t1 inner join bbbb as t2 on t1.id=t2.id) inner join cccc as t3 on t1.id=t3.id
	public function getJoinExternalTables()
	{
		/**
		 * @var WGMModel[] $jm
		 */
		$ret = [];
		foreach($this->joins as $jm) $ret = array_merge($ret, $jm[1]->getJoinExternalTables());
		return $ret;
	}

	public function keys()
	{
		$k = new WGMModelGetKeys($this);
		$k->setKeys(func_get_args());
		return $k;
	}

	private function dumpKeys($keys)
	{
		foreach($keys as $k)
		{
			if($k instanceof WGMModelGetKeys)
				foreach($k->getKeys() as $kk) $this->logInfo($k->getModel()->getAlias().".".$kk);
			else
				$this->logInfo($this->getAlias().".".$k);
		}
	}

	/**
	 * SELECTクエリ用パラメータ生成
	 * @param mixed $keys
	 * @return array クエリパラメータ配列
	 */
	protected function makeQuery($keys)
	{
		// フィールド結合
		$flds = [];
		foreach($this->getJoinExternalFields() as $f) $flds[] = "{$f[1]} AS \"{$f[0]}\"";
		
		// テーブル結合
		$tables  = $this->getJoinTables($this->getTable()." as ".$this->getAlias());
		$orders  = $this->getJoinOrders([]);
		$ford = [];
		usort($orders, function($a,$b) { return $a[0]==$b[0] ? 0 : ( $a[0] < $b[0] ? -1 : 1 ); });
		foreach($orders as $v) $ford += $v[1];
		$orderby = count($ford) > 0 ? " ORDER BY ".implode(",", $ford) : '';

		$this->recs  = 0;
		$this->avars = [];

		// 条件式作成
		$wheres = $this->whereCondExpression($keys);
		$wheres = count($wheres)>0 ? " WHERE ".implode(" AND ",$wheres) : "";

		return array(implode(",",$flds), $tables, $wheres, $orderby, $this->s_offset, $this->s_limit);
	}

	/**
	 * テーブルを指定されたキーで検索する。
	 * @param string|array... キー文字列、配列
	 * @return WGMModel インスタンス
	 */
	public function select()
	{
		// キー解析
		$this->logInfo("**** GET\n");
		$keys = $this->toFlatArray(func_get_args());
		$this->dumpKeys($keys);
		$this->logInfo("****\n");

		// ページャーがモデルにアサインされている
		if($this->pager)
		{
			$count = $this->count($keys);
			$this->pager->setTotal($count);
			$ofs = $this->pager->offset();
			$lim = $this->pager->limit();
			$this->logInfo("**** PAGER {$count}(rows) offset {$ofs} limit {$lim}\n");
			$this->offset($ofs,$lim);
		}

		// クエリ実行
		list($f,$t,$w,$ord,$ofs,$lim) = $this->makeQuery($keys);

		$q = sprintf("SELECT %s FROM %s%s%s%s%s;", $f, $t, $w, $ord, $ofs, $lim);
		$this->dbms->E($q);
		$this->recs = $this->dbms->RECS();

		// データ振分用モデル列挙
		$m = $this->getJoinModels();

		// 結合先
		$n = 0;
		while($f=$this->dbms->F())
		{
			foreach($m as $jm)
			{
				foreach($jm->getFields() as $k) $jm->avars[$n][$k] = $jm->fieldValue($k,$f["{$jm->alias}.{$k}"],"PHP");
			}
			$n++;
		}
		foreach($m as $jm)
		{
			foreach($jm->getFields() as $k) $jm->setAssignedValue($k,$jm->avars[0][$k]);
		}

		return $this;
	}

	/**
	 * テーブルを指定されたキーで検索し、検索された件数を返す。
	 * @param string|array... キー文字列、配列
	 * @return int 件数
	 */
	public function get()
	{
		return $this->select(func_get_args())->recs;
	}

	public function check()
	{
		// キー解析
		$this->logInfo("**** CHECK\n");
		$keys = $this->toFlatArray(func_get_args());
		$this->dumpKeys($keys);
		$this->logInfo("****\n");

		// クエリ作成
		list(,$t,$w) = $this->makeQuery($keys);
		return ($this->dbms->QQ("SELECT true FROM %s%s;", $t, $w)!=false);
	}

	public function count()
	{
		// キー解析
		$this->logInfo("**** COUNT\n");
		$keys = $this->toFlatArray(func_get_args());
		$this->dumpKeys($keys);
		$this->logInfo("****\n");

		// クエリ作成
		list(,$t,$w) = $this->makeQuery($keys);
		list($count) = $this->dbms->QQ("SELECT count(*) FROM %s%s;", $t, $w);

		return (int)$count;
	}

	/**
	 * テーブルを指定されたキーで追記する。
	 * @param string|array... キー文字列、配列
	 * @return WGMModel インスタンス
	 */
	public function insert()
	{
		$this->logInfo("**** INSERT\n");
		$this->recs = 0;

		$flds = $this->getFields();

		$dd=[];
		foreach($flds as $k) $dd[$k] = $this->getAssignedValue($k);
		foreach(array_merge($this->initymds,$this->updymds) as $ff) $dd[$ff]="CURRENT_TIMESTAMP";

		$fs=[]; $vs=[];
		foreach($dd as $f=>$v)
		{
			if(!in_array($f,$flds)) continue;
			$fs[]=$f; $vs[]=$this->fieldValue($f,$v,"DB");
		}
		if(count($fs)==0) $q=false;
		else $q=sprintf("INSERT INTO %s(%s) VALUES(%s);", $this->tablename, implode(",",$fs), implode(",",$vs));

		if($q)
		{
			$this->dbms->E($q);
			if(!$this->dbms->OK()) $this->logFatal("Can't insert into '{$this->tablename}'.\n{$q}");
		}

		return $this;
	}

	/**
	 * テーブルを指定されたキーで更新する。キーが存在しない場合は追加レコードが生成される。
	 * @param string|array... キー文字列、配列
	 * @return WGMModel インスタンス
	 */
	public function update()
	{
		$this->logInfo("**** UPDATE\n");
		$keys = $this->toFlatArray(func_get_args());
		$this->dumpKeys($keys);
		$flds = $this->getFields();
		$this->logInfo("****\n");

		$this->recs = 0;

		// 条件式作成
		$wheres = $this->whereExpression($keys);
		$wheres = count($wheres)>0 ? " WHERE ".implode(" AND ",$wheres) : "";

		$q  = sprintf("SELECT %s FROM %s%s;", implode(",",$flds), $this->tablename, $wheres);
		$this->dbms->Q("%s",$q);

		if(($r=$this->dbms->RECS())>1) $this->logFatal("Can't select the unique record from '{$this->tablename}' on update.\n{$q}");

		$is_insert = ($r==0);
		if($r==1)
		{
			$f=$this->dbms->F();
			foreach($flds as $k) $this->backvars[$k] = $this->fieldValue($k,$f[$k],"PHP");
		}

		if($is_insert)
		{
			$this->logInfo("---> INSERT MODE\n");

			$dd=[];
			foreach($flds as $k) $dd[$k] = $this->getAssignedValue($k);
			foreach(array_merge($this->initymds,$this->updymds) as $ff) $dd[$ff]="CURRENT_TIMESTAMP";

			$fs=[]; $vs=[];
			foreach($dd as $f=>$v)
			{
				if(!in_array($f,$flds)) continue;
				$fs[]=$f; $vs[]=$this->fieldValue($f,$v,"DB");
			}
			if(count($fs)==0) $q=false;
			else $q=sprintf("INSERT INTO %s(%s) VALUES(%s);", $this->tablename, implode(",",$fs), implode(",",$vs));
		}
		else
		{
			$this->logInfo("---> UPDATE MODE\n");

			$d1=[];
			$dd=[];

			$ws=array_intersect(array_unique(array_merge(array_keys($this->assign),array_keys($this->vars))),$flds);
			foreach($ws as $k) $d1[$k] = $this->getAssignedValue($k);
			foreach($d1 as $k=>$v) if(!$this->compareField($k,$this->backvars[$k],$v)) $dd[$k]=$v;

			foreach($this->initymds as $ff) unset($dd[$ff]);
			foreach($this->updymds as $ff)  $dd[$ff]="CURRENT_TIMESTAMP";

			$ss=[];
			foreach($dd as $f=>$v)
			{
				if(in_array($f,$keys)) continue;
				if(!in_array($f,$flds)) continue;
				$ss[] = "{$f}=".$this->fieldValue($f,$v,"DB");
			}
			if(count($ss)==0) $q=false;
			else $q=sprintf("UPDATE %s SET %s%s;", $this->tablename, implode(",",$ss), $wheres);
		}

		if($q)
		{
			$this->dbms->E($q);
			if(!$this->dbms->OK()) $this->logFatal("Can't update '{$this->tablename}'.\n{$q}");
		}

		return $this;
	}

	/**
	 * テーブルを指定されたキーで削除する。
	 * @param string|array... キー文字列、配列
	 * @return WGMModel インスタンス
	 */
	public function delete()
	{
		$this->logInfo("**** DELETE\n");
		$keys = $this->toFlatArray(func_get_args());
		$this->dumpKeys($keys);
		$this->logInfo("****\n");

		$this->recs = 0;

		// 条件式作成
		$wheres = $this->whereExpression($keys);
		$wheres = count($wheres)>0 ? " WHERE ".implode(" AND ",$wheres) : "";

		$q = sprintf("DELETE FROM %s%s;", $this->tablename, $wheres);
		$this->dbms->E($q);
		if(!$this->dbms->OK()) $this->logFatal("Can't delete from '{$this->tablename}'.\n{$q}");

		return $this;
	}

	public function result()
	{
		return count($this->avars);
	}

	public function setVars($vars=[])
	{
		$this->vars = $vars;
		foreach($vars as $k=>$v) $this->setAssignedValue($k,$v);
		return $this;
	}

	public function getVars($vars=[])
	{
		return $this->setVars($vars)->get(array_keys($vars));
	}

	public function getJoinedAvars()
	{
		$r  = [];
		$jm = $this->getJoinModels();
		$n  = $this->result();
		foreach($jm as $m)
		{
			$tn = $m->getTable();
			for($i=0; $i<$n; $i++) $r[$i][$tn] = $m->avars[$i];
		}
		return $r;
	}
}
