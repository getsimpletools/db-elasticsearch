<?php
/*
 * Simpletools Framework.
 * Copyright (c) 2009, Marcin Rosinski. (https://www.getsimpletools.com)
 * All rights reserved.
 *
 * LICENCE
 *
 * Redistribution and use in source and binary forms, with or without modification,
 * are permitted provided that the following conditions are met:
 *
 * - 	Redistributions of source code must retain the above copyright notice,
 * 		this list of conditions and the following disclaimer.
 *
 * -	Redistributions in binary form must reproduce the above copyright notice,
 * 		this list of conditions and the following disclaimer in the documentation and/or other
 * 		materials provided with the distribution.
 *
 * -	Neither the name of the Simpletools nor the names of its contributors may be used to
 * 		endorse or promote products derived from this software without specific prior written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR
 * IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY
 * AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR
 * CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL
 * DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE,
 * DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER
 * IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF
 * THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
 *
 * @framework		Simpletools
 * @copyright  		Copyright (c) 2009 Marcin Rosinski. (http://www.getsimpletools.com)
 * @license    		http://www.opensource.org/licenses/bsd-license.php - BSD
 *
 */

namespace Simpletools\Db\Elasticsearch;

use \Simpletools\Db\Elasticsearch\Doc\Body;


class Doc
{

	protected $_id;
	protected $_query;
	protected $_index;
	protected $_columns;
	protected $_routing;
	protected $_params;

	protected $_body;


	public function __construct(mixed $id=null, mixed $index=null)
	{
		if(!is_string($id) && $id!==null)
			throw new \Exception('Id must be string or null (auto generate)');

		$this->_body = new Body((object) array());
		$this->_id = $id;


		if($index) $this->_index = $index;
	}

	public function id($id = false)
	{
		if($id === false)
		{
			return $this->_id;
		}

		$this->_id = $id;
		return $this;
	}


	public function index($index)
	{
		$this->_index = $index;
		return $this;
	}


	public function columns()
	{
		$args = func_get_args();

		if(count($args) == 1)
		{
			$args = $args[0];
		}

		if($args)
		{
			$this->_columns = $args;
			if(is_string($this->_columns)) $this->_columns =  explode(',',$this->_columns);
		}

		return $this;
	}

	public function getSaveQuery()
	{
		if(!$this->_index)
			throw new \Exception('Please specify index as an argument of ->$index()');

		$this->_query = new Query($this->_index);
		if($this->_routing)
			$this->_query->routing($this->_routing);
		if($this->_params)
			$this->_query->params($this->_params);
		$this->_query
				->set($this->_id,$this->_body);

		return $this->_query;
	}

	public function save()
	{
		$this->getSaveQuery();
		$this->_query->run();
		$this->_id = $this->_query->getRawResult()->_id;
		$this->_query = null;

		return $this;
	}

	public function getUpdateQuery(mixed $dsl = null)
	{
		if(!$this->_index)
			throw new \Exception('Please specify index as an argument of ->$index()');

		$this->_query = new Query($this->_index);
		if($this->_routing)
			$this->_query->routing($this->_routing);
		if($this->_params)
			$this->_query->params($this->_params);
		$this->_query
				->updateOne($this->_id,$dsl ? $dsl : $this->_body);


		return $this->_query;
	}

	public function update(mixed $dsl = null)
	{
		$this->getUpdateQuery($dsl);
		$this->_query->run();
		$this->_query = null;

		return $this;
	}


	public function getLoadQuery()
	{
		if(!$this->_index)
			throw new \Exception('Please specify index as an argument of ->$index()');

		$this->_query = new Query($this->_index);
		if($this->_routing)
			$this->_query->routing($this->_routing);
		if($this->_params)
			$this->_query->params($this->_params);

		$this->_query->columns($this->_columns)->getOne($this->_id);

		return $this->_query;
	}

	public function load()
	{
		$this->getLoadQuery();
		$this->_query->run();


		$this->body($this->_query->fetch());

		$this->_query = null;

		return $this;
	}


	public function __set($name,$value)
	{
		if($name=="body")
		{
			return $this->body($value);
		}
		else
		{
			throw new \Exception("Provided property `{$name}` doesn't exist");
		}
	}

	public function __get($name)
	{
		if($name=='body')
		{
			return !isset($this->_body) ? ($this->_body = new Body($this->_body)) : $this->_body;
		}
	}


	public function body(mixed $body=null)
	{
		if($body===null)
			return $this;

		if($body instanceof Body)
		{
			$this->_body = new Body($body);
			return $this;
		}

		$this->_body = new Body($body);

		return $this;
	}

	public function routing($routing)
	{
		$this->_routing = $routing;
		return $this;
	}

	public function params($params)
	{
		$this->_params = $params;
		return $this;
	}


	public function getRemoveQuery()
	{
		if(!$this->_index)
			throw new \Exception('Please specify index as an argument of ->$index()');

		$this->_query = new Query($this->_index);
		if($this->_routing)
			$this->_query->routing($this->_routing);

		$this->_query->deleteOne($this->_id);

		return $this->_query;
	}

	public function remove()
	{
		$this->getRemoveQuery();
		$this->_query->run();
		$this->_id = null;
		$this->body(array());
	}



	public function resetQuery()
	{
		$this->_query = null;
	}

}
