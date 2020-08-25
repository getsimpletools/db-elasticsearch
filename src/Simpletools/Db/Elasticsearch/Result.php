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



class Result implements \Iterator
{
    protected $_result 	= '';
    protected $_query = null;
    protected $_cursor = null;
		protected $_scroll_id = null;
		protected $_client = null;
		protected $_autoScroll = false;

    protected $_firstRowCache	= null;
    protected $_firstRowCached	= false;

    protected $_position 		= 0;
    protected $_currentRow 		= false;
    protected $_columnsMap      = array();
    protected $_schema = array();
		protected $_convertMapToJson;
		protected $_data = array();
		protected $_cursorColumns  = [];
		protected $_totalCount=0;

    public function __construct($result, $query)
		{
			$this->_result = $result;
			$this->_query = $query;

			$this->_position = 0;

			if(in_array($this->_query['type'],['INSERT','UPDATE ONE','UPDATE','DELETE','DELETE ONE', 'BULK','CREATE INDEX','DROP INDEX']))
			{
				$this->parseResponse();
			}
		}

		public function aggs()
        {
            $this->parseResponse();

            return isset($this->_result->aggregations) ? $this->_result->aggregations  : null;
        }

		public function parseResponse()
		{
			if(!is_string($this->_result)) return $this;

			if(!$this->_result = json_decode($this->_result)) throw new Exception('JSON Parsing Error',500);

			$this->_totalCount =0;

			if($this->_query['type'] == 'GET')
			{
				if(!@$this->_result->found)
					throw new Exception('Document not found',404);

				$this->_data[] = $this->_result->_source;
			}
			elseif($this->_query['type'] == 'SELECT' || $this->_query['type'] == 'SQL')
			{
				if(!$this->_cursorColumns && @$this->_result->columns)
				{
					foreach ($this->_result->columns as  $col)
					{
						$this->_cursorColumns[] = $col->name;
					}
				}

				if($this->_cursorColumns)
				{
					foreach ($this->_result->rows as $row)
					{
						$this->_data[] = (object) array_combine($this->_cursorColumns, $row);
					}
				}
				else
				{
					foreach ($this->_result->rows as $row)
					{
						$this->_data[] = $row;
					}
				}
			}
			elseif($this->_query['type'] == 'SEARCH')
			{
				if(isset($this->_result->hits->total->value))
					$this->_totalCount = $this->_result->hits->total->value;

				if(isset($this->_result->hits->hits))
				{
					$this->_data = $this->_result->hits->hits;
				}
				else
					throw new Exception('Unexpected search error',500);
			}

			if(@$this->_result->error)
			{
				if(is_string($this->_result->error))
					throw new Exception($this->_result->error,$this->_result->status?:500);
				elseif (@$this->_result->error->reason && is_string($this->_result->error->reason))
					throw new Exception($this->_result->error->reason,$this->_result->status?:500);
				else
					throw new Exception('Unexpected Result Error',500);
			}


			$this->_cursor = @$this->_result->cursor;
			$this->_scroll_id = $this->_data ? @$this->_result->_scroll_id : null;

			return $this;
		}



    public function isEmpty()
    {
        if(!$this->_result) return true;

        return count($this->_data) > 0 ? false : true;
    }

    public function length()
    {
        if(!$this->_result) return 0;

        return count($this->_data);
    }

    public function fetch()
    {
    		$this->parseResponse();

        $result = array_shift($this->_data);

				if(!$result && $this->_autoScroll)
				{
					if($this->_cursor === null && $this->_scroll_id === null)
						return false;

					$this->nextPage();

					return $this->fetch();
				}

        return $result;
    }

    public function nextPage()
		{
			if(is_string($this->_result) && $this->_result)
			{
				$this->parseResponse();
			}

			if(!$this->_client)
				$this->_client = new Client();

			$this->_result = '';
			if($this->_cursor)
			{
				$this->_result = $this->_client->execute($this->_query['endpoint'], $this->_query['method'],[
					'cursor' => $this->_cursor
				]);
			}
			elseif ($this->_scroll_id)
			{
				$this->_result = $this->_client->execute('/_search/scroll','POST',[
					'scroll_id' => $this->_scroll_id,
					'scroll' => $this->_query['params']['scroll']
				]);
			}
		}


    public function fetchAll()
    {
				$this->parseResponse();
        if($this->isEmpty()) return array();

        $datas = array();
        while($data = $this->fetch())
        {
            $datas[] = $data;
        }

        $this->free();
        return $datas;
    }

    public function &getRawResult()
    {
        return $this->_result;
    }

    public function free()
    {
        $this->_result = '';
    }

    public function __desctruct()
    {
			$this->_result = '';
    }

    public function getAffectedRows()
    {
			$this->parseResponse();
			return @$this->_result->total;
    }

    public function getInsertedId()
    {
			$this->parseResponse();
			return @$this->_result->_id;
    }

    protected function _loadFirstRowCache()
    {
        if(!$this->_result) return;

        if(!$this->_firstRowCached)
        {
						$this->parseResponse();

            $this->_firstRowCache 	= reset($this->_data);
            $this->_firstRowCached 	= true;
        }
    }

    public function getFirstRow()
    {
    		$this->_loadFirstRowCache();
        return $this->_firstRowCache;
    }

    public function __get($name)
    {
        $this->_loadFirstRowCache();
        return isset($this->_firstRowCache->{$name}) ? $this->_firstRowCache->{$name} : null;
    }

    public function __isset($name)
    {
        $this->_loadFirstRowCache();
        return isset($this->_firstRowCache->{$name});
    }

    public function rewind()
    {
        $this->_position 	= 0;

        if($this->_currentRow===false)
        {
            $this->_currentRow = $this->fetch();
        }
    }

    public function current()
    {
        return $this->_currentRow;
    }

    public function key()
    {
        return $this->_position;
    }

    public function next()
    {
        $this->_currentRow = $this->fetch();
        ++$this->position;
        return $this->_currentRow;
    }

    public function valid()
    {
        return ($this->_currentRow) ? true : false;
    }

	public function client($client)
	{
		if (!($client instanceof Client))
		{
			throw new \Exception("Provided client is not an instance of \Simpletools\Db\Elasticsearch\Client", 404);
		}

		$this->_client = $client;

		return $this;
	}

	public function getCursorId()
	{
		return $this->_cursor;
	}

	public function getCursorColumns()
	{
		$this->parseResponse();
		return $this->_cursorColumns;
	}

	public function setCursorColumns(array $columns)
	{
		$this->_cursorColumns = $columns;
	}

	public function getScrollId()
	{
        $this->parseResponse();
		return $this->_scroll_id;
	}

	public function getCurrentPage()
	{
		$this->parseResponse();
		return $this->_data;
	}

	public function autoScroll()
	{
		$this->_autoScroll = true;
		return $this;
	}

	public function getTotalCount()
	{
        $this->parseResponse();
		return $this->_totalCount;
	}


}
