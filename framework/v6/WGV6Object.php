<?php
/**
 * waggo6
 * @copyright 2013-2019 CIEL, K.K.
 * @license MIT
 */

require_once(dirname(__FILE__)."/WGV6Params.php");

class WGV6Object
{
	/**
	 * @var WGV6Params $params
	 */
	public $params;

	protected $id,$key,$enable,$lock,$focus,$extra;

	/**
	 * @var WGG
	 */
	protected $gauntlet;

	/**
	 * @var WGFController
	 */
	public $controller;

	/**
	 * @var WGFSession
	 */
	public $session;

	/**
	 * WGV6Object constructor.
	 */
	public function __construct()
	{
		$this->params   = new WGV6Params();
		$this->enable   = true;
		$this->lock     = false;
		$this->gauntlet = null;
		$this->id       = $this->newId();
		$this->focus    = false;
		$this->extra    = new stdClass();
		$this->key      = null;
	}

	/**
	 * Generate new identifier.
	 * @return string
	 */
	public function newId()
	{
		$seq = ++$_SESSION["_sOBJSEQ"];
		return sprintf("wgv-%d", $seq);
	}

	public function getId()					{ return $this->id;											}
	public function setId($id)				{ $this->id = $id;							return $this;	}

	public function getIds()				{ return $this->getId();									}
	public function initSession($session)	{ $this->session=$session; 					return $this;	}
	public function initController($controller) { $this->controller=$controller;		return $this;	}

	public function initFirst()				{ return true;												}
	public function init()					{ return true;												}

	public function getParams()				{ return $this->params;										}

	public function getKey()				{ return $this->key;										}
	public function setKey($key)			{ $this->key = $key; 						return $this;	}

	public function getName()				{ return $this->getKey();									}
	public function setName($key)			{ return $this->setKey($key);								}

	public function getValue()				{ return $this->session->get($this->key);					}
	public function setValue($v)			{ $this->session->set($this->key,$v);		return $this;	}
	public function issetValue()			{ return $this->session->isExists($this->key);				}

	public function clear()					{ $this->setValue(null); 					return $this;	}
	public function unsetValue()			{ $this->setValue(null);					return $this;	}

	public function setLocalValue($key,$v)	{ $this->session->set("{$this->key}/{$key}",$v);			return $this;	}
	public function getLocalValue($key)		{ return $this->session->get("{$this->key}/{$key}");		}
	public function issetLocalValue($key)	{ return $this->session->isExists("{$this->key}/{$key}");	}
	public function emptyLocalValue($key)	{ return $this->session->isEmpty("{$this->key}/{$key}");	}
	public function unsetLocalValue($key)	{ $this->session->delete("{$this->key}/{$key}");			return $this;	}

	public function getError()				{ return $this->session->get("{$this->key}#error");			}
	public function setError($v)			{ $this->session->set("{$this->key}#error",$v);				return $this;	}
	public function unsetError()			{ $this->session->delete("{$this->key}#error");				return $this;	}
	public function isError()				{ return !$this->session->isEmpty("{$this->key}#error");	}

	public function getEnable()				{ return $this->enable;										}
	public function isEnable()				{ return $this->getEnable();								}
	public function setEnable($enableFlag)	{ $this->enable = $enableFlag;				return $this;	}
	public function enable()				{ $this->setEnable(true);					return $this;	}
	public function disable()				{ $this->setEnable(false);					return $this;	}

	public function isSubmit() 				{ return false;												}
	public function isShowOnly()			{ return false;												}

	public function formHtml()				{ return "";												}
	public function showHtml()				{ return "";												}

	public function postCopy()				{ return $this;												}

	public function setLock($lockFlag)		{ $this->lock = $lockFlag;					return $this;	}
	public function lock()					{ $this->setLock(true);						return $this;	}
	public function unlock()				{ $this->setLock(false);					return $this;	}
	public function isLock()				{ return $this->lock;										}

	/**
	 * @param WGG $gauntlet
	 * @return $this
	 */
	public function execGauntlet($gauntlet)
	{
		$this->unsetError();
		if(is_null($gauntlet)) return $this;

		$v = $this->getValue();
		$gauntlet->check($v);
		$this->setValue($v);

		if( $gauntlet->hasError() ) $this->setError($gauntlet->getError());
		return $this;
	}

	public function filterGauntlet()
	{
		$this->execGauntlet($this->gauntlet);
		return $this;
	}

	public function check()
	{
		$this->filterGauntlet();
		return $this;
	}

	public function setGauntlet($g)		{ $this->gauntlet = $g;		return $this;						}
	public function getGauntlet()		{ return $this->gauntlet; 										}
	public function clearGauntlet()		{ $this->gauntlet = null;	return $this;						}

	/**
	 * @param WGFController $c
	 *
	 * @return WGV6Object
	 */
	public function controller($c)		{ return $this;													}

	public function getExtra()			{ return $this->extra;											}

	/**
	 * Return publish values for htmltemplate.
	 * {@key:{id,name,value...}}
	 * @return string[]
	 */
	public function publish()			{
		return
			array(
				'id'		=> $this->getId(),
				'name'		=> $this->getKey(),
				'value'		=> htmlspecialchars($this->getValue(), ENT_QUOTES | ENT_HTML5),
				'error'		=> htmlspecialchars($this->getError(), ENT_QUOTES | ENT_HTML5),
 				'rawValue'	=> $this->getValue(),
 				'rawError'	=> $this->getValue(),
 				'params'	=> $this->params->toString()
			);
	}
}
