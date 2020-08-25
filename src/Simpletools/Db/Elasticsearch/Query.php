<?php

namespace Simpletools\Db\Elasticsearch;

use mysql_xdevapi\Statement;
use Simpletools\Db\Elasticsearch\Doc\Body;
use Simpletools\Db\Replicator;

class Query implements \Iterator
{
    protected $_query 	    = array(
    	'params' => []
		);
    protected $_columnsMap  = array();
    protected $_client;

    protected $_result      = null;

    public function __construct($index = null)
    {
        $this->index($index);

        $this->_client = new Client();
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

    public function columns()
    {
        $args = func_get_args();

        if(count($args) == 1)
        {
            $args = $args[0];
        }

        if($args)
        	$this->_query['columns'] = $args;

        return $this;
    }

//    public function join($tableName,$direction='left')
//    {
//        $tableType = 'table';
//
//        if($tableName instanceof Table)
//        {
//            $tableName = '('.$tableName->getQuery().')';
//            $tableType = 'query';
//        }
//
//        $this->_query['join'][$this->_currentJoinIndex] = [
//            'tableType'		=> $tableType,
//            'table'			=> $tableName,
//            'direction'		=> $direction
//        ];
//
//        return $this;
//    }
//
//    public function leftJoin($tableName)
//    {
//        return $this->join($tableName,'left');
//    }
//
//    public function rightJoin($tableName)
//    {
//        return $this->join($tableName,'right');
//    }
//
//    public function innerJoin($tableName)
//    {
//        return $this->join($tableName,'inner');
//    }

//    protected function _on($args,$glue='')
//    {
//        if(
//            $args instanceof Sql OR
//            $args instanceof Json
//        )
//        {
//            $this->_query['join'][$this->_currentJoinIndex]['on'] = (string) $args;
//        }
//        else
//        {
//            $operand 	= '=';
//            $left 		= $args[0];
//
//            if(count($args)>2)
//            {
//                $operand 	= $args[1];
//                $right 		= $args[2];
//            }
//            else
//            {
//                $right 		= $args[1];
//            }
//
//            if($glue)
//            {
//                $this->_currentJoinIndex--;
//                $glue = ' '.$glue.' ';
//            }
//            else
//            {
//                $this->_query['join'][$this->_currentJoinIndex]['on'] = '';
//            }
//
//            $this->_query['join'][$this->_currentJoinIndex]['on'] .= $glue.$left.' '.$operand.' '.$right;
//        }
//
//        $this->_currentJoinIndex++;
//
//        return $this;
//    }

//    public function on()
//    {
//        $args = func_get_args();
//        if(count($args)==1) $args = $args[0];
//
//        $this->_on($args,'');
//
//        return $this;
//    }
//
//    public function orOn()
//    {
//        $args = func_get_args();
//        if(count($args)==1) $args = $args[0];
//
//        $this->_on($args,'OR');
//
//        return $this;
//    }
//
//    public function andOn()
//    {
//        $args = func_get_args();
//        if(count($args)==1) $args = $args[0];
//
//        $this->_on($args,'AND');
//
//        return $this;
//    }
//
//    public function using()
//    {
//        $this->_currentJoinIndex++;
//
//        return $this;
//    }


    public function group()
    {
        $args = func_get_args();

        if(count($args) == 1)
        {
            $args = $args[0];
        }

        $this->_query['groupBy'] = $args;

        return $this;
    }

    public function sort()
    {
        $args = func_get_args();

        if(count($args) == 1)
        {
            $args = $args[0];
        }

        $this->_query['sort'] = $args;

        return $this;
    }

    public function params($params)
		{
			if(is_string($params))
			{
				$arr = json_decode($params, true);
				if(!$arr)
				{
					$arr = [];
					foreach (explode('&',ltrim($params,'?')) as $pair)
					{
						$pair = explode('=', $pair);
						if(count($pair) == 2) $arr[$pair[0]] = $pair[1];
					}
					$this->_query['params'] = $arr;
				}
			}
			elseif (is_object($params)) $params = json_decode(json_encode($params), true);

			if(is_array($params))
			{
				$this->_query['params'] = $params;
			}

			return $this;
		}

		protected function getParamLine()
		{
			$line = [];
			foreach ($this->_query['params'] as $key => $val)
			{
				$line[] = $key.'='.$val;
			}

			if($line) return '?'.implode('&',$line);

			return '';
		}

		public function scroll($alive ='1m')
		{
			$this->_query['params']['scroll'] = $alive;
			return $this;
		}

		public function size($size)
		{
			$this->_query['params']['size'] = $size;
			return $this;
		}

		public function update($dsl)
		{
			$this->_query['type'] = "UPDATE";
			$this->_query['data'] = $dsl;

			return $this;
		}

    public function callApi($endpoint, $method = 'GET', $data = null)
		{
			return $this->_client->execute($endpoint, $method, $data);
		}

		public function sql($sql)
		{
			$this->_query['type'] = "SQL";
			$this->_query['data'] = $sql;
			return $this;
		}

		public function search($dsl)
		{
			$this->_query['type'] = "SEARCH";
			$this->_query['data'] = $dsl;
			return $this;
		}

		public function createIndex($settigns)
		{
			$this->_query['type'] = "CREATE INDEX";
			$this->_query['data'] = $settigns;

			return $this;
		}

		public function dropIndex()
		{
			$this->_query['type'] = "DROP INDEX";
			return $this;
		}

		public function insert($id, $data)
		{
			return $this->set($id, $data);
		}

    public function set($id, $data)
    {
        $this->_query['type'] = "INSERT";
				$this->_query['id'] = $id;
        $this->_query['data'] = $data;

        return $this;
    }

		public function delete($dsl)
		{
			$this->_query['type'] = "DELETE";
			$this->_query['data'] = $dsl;

			return $this;
		}

		public function getOne($id)
		{
			$this->_query['type'] = "GET";
			$this->_query['id'] = $id;

			return $this;
		}

		public function updateOne($id, $data)
		{
			$this->_query['type'] = "UPDATE ONE";
			$this->_query['id'] = $id;
			$this->_query['data'] = $data;

			return $this;
		}

		public function deleteOne($id)
		{
			$this->_query['type'] = "DELETE ONE";
			$this->_query['id'] = $id;

			return $this;
		}

		public function addAlias($index, $alias)
		{
			$this->_query['type'] = "ALIASES";
			if(!@$this->_query['actions'])
				$this->_query['actions'] = [];

			$this->_query['actions'][] = [
				'add' => [
					'index' => $index,
					'alias' => $alias
				]
			];
			return $this;
		}

		public function removeAlias($index, $alias)
		{
			$this->_query['type'] = "ALIASES";
			if(!@$this->_query['actions'])
				$this->_query['actions'] = [];

			$this->_query['actions'][] = [
				'remove' => [
					'index' => $index,
					'alias' => $alias
				]
			];
			return $this;
		}


//    protected $___options = array();
//    public function options($options=array())
//    {
//        $this->___options = $options;
//
//        return $this;
//    }
//

    public function run(array $args = [])
    {
        if($this->_result) return $this;
        $query = $this->getQuery(true);

				if($args)
				{
					if($this->_query['type'] == 'SQL')
					{
						$query['data']['query'] = $this->_prepareQuery($query['data']['query'],$args);
					}
					elseif ($this->_query['type'] == 'SEARCH' || $this->_query['type'] == 'UPDATE' || $this->_query['type'] == 'UPDATE ONE' ||
						$this->_query['type'] == 'DELETE' || $this->_query['type'] == 'DELETE ONE'
					)
					{
						$query['data'] = $this->_prepareQuery($query['data'],$args, true);
					}
				}

				$query['type'] = $this->_query['type'];

        $this->_result = new Result($this->_client->execute($query['endpoint'], $query['method'],$query['data']), $query);

				if($this->_query['type'] == 'INSERT')
				{
					Replicator::trigger('elasticsearch://write@'.$this->_query['index'],(object)[
						'_id' => $this->_result->getInsertedId(),
						'_source' =>is_string($query['data']) ? json_decode($query['data']) : json_decode(json_encode($this->_query['data']))
					]);
				}
				elseif ($this->_query['type'] == 'UPDATE ONE' && !($this->_query['data'] instanceof DSL))
				{
					Replicator::trigger('elasticsearch://update@'.$this->_query['index'],(object)[
						'_id' => $this->_query['id'],
						'_source' =>  is_string($query['data']) ? json_decode($query['data']) : json_decode(json_encode($this->_query['data']))
					]);
				}
				elseif ($this->_query['type'] == 'DELETE ONE')
				{
					Replicator::trigger('elasticsearch://update@'.$this->_query['index'],(object)[
						'_id' => $this->_query['id'],
					]);
				}

        if(@$this->_query['cursorColumns'])
				{
					$this->_result->setCursorColumns($this->_query['cursorColumns']);
				}

				if(@$this->_query['autoScroll'])
				{
					$this->_result->autoScroll();
				}
        
        return $this;
    }

//    public function get($id,$column='id')
//    {
//        $this->_query['type']		= "SELECT";
//        $this->_query['where'][] 	= array($column,$id);
//
//        return $this;
//        //return $this->run();
//    }

    public function _escape($value, $doubleQuote = false)
    {
        if(is_null($value))
        {
            return 'NULL';
        }
        else
        {
        	if($doubleQuote) return '"'.$this->_client->escape($value).'"';
        	else return "'".$this->_client->escape($value)."'";
        }
    }

    private function _prepareQuery($query, array $args, $doubleQuote = false)
    {
        foreach($args as $arg)
        {
            if(is_string($arg))
            {
                if(strpos($arg,'?') !== false)
                {
                    $arg = str_replace('?','<--SimpleMySQL-QuestionMark-->',$arg);
                }

                $arg = $this->_escape($arg, $doubleQuote);
            }
            elseif(
                $arg instanceof Sql
            )
            {
                $arg = (string) $arg;
            }

            if($arg === null)
            {
                $arg = 'NULL';
            }

            $query = $this->replace_first('?', $arg, $query);
        }

        if(strpos($query,'<--SimpleMySQL-QuestionMark-->') !== false)
        {
            $query = str_replace('<--SimpleMySQL-QuestionMark-->','?',$query);
        }

        return $query;
    }

    public function replace_first($needle , $replace , $haystack)
    {
        $pos = strpos($haystack, $needle);

        if ($pos === false)
        {
            // Nothing found
            return $haystack;
        }

        return substr_replace($haystack, $replace, $pos, strlen($needle));
    }

		public function getRawQuery()
		{
			return $this->_query;
		}

    public function getQuery($runtime=false)
    {
        $args = [];

        if(@$this->_query['rawQuery'])
				{
					return [
						'endpoint' => $this->_query['endpoint'],
						'method' => $this->_query['method'],
						'data' => $this->_query['data']
					];
				}

        if(!isset($this->_query['type']))
            $this->_query['type']		= "SELECT";

        if(!isset($this->_query['columns']))
        {
            $this->_query['columns']		= "*";
        }

        if(!is_array($this->_query['columns']) && !(
                $this->_query['columns'] instanceof Sql
            ))
        {
        	$this->_query['columns'] = explode(',',$this->_query['columns']);
					foreach($this->_query['columns'] as $idx => $column)
					{
						$this->_query['columns'][$idx] = trim($column);
					}
        }
        elseif(is_array($this->_query['columns']))
        {
            foreach($this->_query['columns'] as $idx => $column)
            {
                if(!is_integer($idx))
                {
                    $this->_columnsMap[$idx]      = $column;
                    $column                       = $idx;
                }
                elseif($column instanceof Sql){
                    $this->_query['columns'][$idx] = (string) $column;
                }
                else
                {
                    $column = str_replace(' as ',' ',$column);

                    if(strpos($column,' ')!==false)
                    {
                        $column = explode(' ',$column);
                        foreach($column as $_columnKey => $_columnName)
                        {
                            $column[$_columnKey] = $this->escapeKey($_columnName);
                        }

                        if(isset($this->_columnsMap[$idx]))
                            $this->_columnsMap[$_columnName]      = $this->_columnsMap[$idx];

                        $this->_query['columns'][$idx] = implode(' ',$column);
                    }
                    else {
                        $this->_query['columns'][$idx] = $this->escapeKey($column);
                    }
                }
            }
        }

				if($this->_query['type']=='INSERT')
				{
					return [
						'endpoint' => $this->_query['index']."/_doc/".$this->_query['id'],
						'method' => $this->_query['id'] === null ? 'POST' : 'PUT',
						'data' => $this->_query['data']
					];
				}
				elseif($this->_query['type']=='GET')
				{
					$columns= '';
					if($this->_query['columns'] && $this->_query['columns'][0] !='*')
					{
						$columns = '?_source_includes='. str_replace('"','',implode(',',$this->_query['columns']));
					}

					return [
						'endpoint' => $this->_query['index']."/_doc/".$this->_query['id'].$columns,
						'method' => 'GET',
						'data' => null
					];
				}
				elseif($this->_query['type']=='UPDATE')
				{
					return [
						'endpoint' => $this->_query['index']."/_update_by_query",
						'method' => 'POST',
						'data' => $this->_query['data']
					];
				}
				elseif($this->_query['type']=='UPDATE ONE')
				{
					return [
						'endpoint' => $this->_query['index']."/_update/".$this->_query['id'],
						'method' => 'POST',
						'data' => $this->_query['data'] instanceof DSL ? (string)$this->_query['data'] : [
							'doc' => $this->_query['data']
						]
					];
				}
				elseif($this->_query['type']=='DELETE')
				{
					return [
						'endpoint' => $this->_query['index']."/_delete_by_query",
						'method' => 'POST',
						'data' => $this->_query['data']
					];
				}
				elseif($this->_query['type']=='DELETE ONE')
				{
					return [
						'endpoint' => $this->_query['index']."/_doc/".$this->_query['id'],
						'method' => 'DELETE',
						'data' => null
					];
				}
				elseif($this->_query['type']=='SQL')
				{
					return [
						'endpoint' => '/_sql?format=json',
						'method' => 'POST',
						'data' => [
							'query' => (string)$this->_query['data']
						]
					];
				}
				elseif($this->_query['type']=='SEARCH')
				{
					return [
						'endpoint' => $this->_query['index']."/_search".$this->getParamLine(),
						'method' => 'POST',
						'data' => $this->_query['data'],
						'params' => $this->_query['params']
					];
				}
				elseif($this->_query['type']=='CREATE INDEX')
				{
					return [
						'endpoint' => $this->_query['index'],
						'method' => 'PUT',
						'data' => $this->_query['data']
					];
				}
				elseif($this->_query['type']=='DROP INDEX')
				{
					return [
						'endpoint' => $this->_query['index'],
						'method' => 'DELETE',
						'data' => null
					];
				}
				elseif($this->_query['type']=='ALIASES')
				{
					return [
						'endpoint' => '/_aliases',
						'method' => 'POST',
						'data' => [
							'actions' => $this->_query['actions']
						]
					];
				}

			$query 		= array();

        $query[] 	= $this->_query['type'];

        if($this->_query['type']=='SELECT')
        {
            $query[] = is_array($this->_query['columns']) ? implode(', ',$this->_query['columns']) : $this->_query['columns'];
            $query[] = 'FROM';
        }

        $query[] = $this->escapeKey($this->_query['index']);



        if(isset($this->_query['as']))
        {
            $query[] = 'as '.$this->escapeKey($this->_query['as']);
        }

//        if(isset($this->_query['join']))
//        {
//            foreach($this->_query['join'] as $join)
//            {
//                $db = isset($join['db']) ? $join['db'] : $this->_client->getCurrentDb();
//
//                if(strpos($join['table'],'.')===false)
//                {
//                    $syntax 	= strtoupper($join['direction']).' JOIN '.$this->escapeKey($db.'.'.$join['table']);
//                }
//                else
//                {
//                    $syntax 	= strtoupper($join['direction']).' JOIN '.$this->escapeKey($join['table']);
//                }
//
//                if(isset($join['as']))
//                {
//                    $syntax .= ' as '.$join['as'];
//                }
//
//                if(isset($join['on']))
//                {
//                    $syntax .= ' ON ('.$join['on'].')';
//                }
//                elseif(isset($join['using']))
//                {
//                    $syntax .= ' USING ('.$join['using'].')';
//                }
//
//                $query[] 	= $syntax;
//            }
//        }



			if(isset($this->_query['where']))
			{
				$query['WHERE'] = 'WHERE';

				if(is_array($this->_query['where']))
				{
					foreach($this->_query['where'] as $operands)
					{
						if(!isset($operands[2]))
						{
							if($operands[1]===null) {
								$query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " IS NULL";
							}
							else{
								$query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " = " . $this->_escape($operands[1]);
							}

						}
						else
						{
							$operands[1] = strtoupper($operands[1]);

							if($operands[1] == "IN" AND is_array($operands[2]))
							{
								$operands_ = array();

								foreach($operands[2] as $op)
								{
									$operands_[] = $this->_escape($op);
								}

								$query[] = @$operands[-1].' '.$this->escapeKey($operands[0])." ".$operands[1]." (".implode(",",$operands_).')';
							}
							else
							{
								if($operands[2]===null) {
									$query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " " . $operands[1] . " NULL";
								}
								else
								{
									$query[] = @$operands[-1] . ' ' . $this->escapeKey($operands[0]) . " " . $operands[1] . " " . $this->_escape($operands[2]);
								}

							}
						}
					}
				}
				else
				{
					$query[] = 'id = '.$this->_escape($this->_query['where']);
				}
			}

			if(isset($this->_query['whereSql']))
			{
				if(!isset($query['WHERE'])) $query['WHERE'] = 'WHERE';

				if($this->_query['whereSql']['vars'])
				{
					$query[] = $this->_prepareQuery($this->_query['whereSql']['statement'],$this->_query['whereSql']['vars']);
				}
				else
				{
					$query[] = $this->_query['whereSql']['statement'];
				}
			}

			if(isset($this->_query['groupBy']))
			{
				$query[] = 'GROUP BY';

				if(!is_array($this->_query['groupBy']))
				{
					$query[] = $this->_query['groupBy'];
				}
				else
				{
					$groupBy = array();

					foreach($this->_query['groupBy'] as $column)
					{
						$groupBy[] = $column;
					}

					$query[] = implode(', ',$groupBy);
				}
			}

			if(isset($this->_query['sort']))
			{
				$query[] = 'ORDER BY';

				if(!is_array($this->_query['sort']))
				{
					$query[] = $this->_query['sort'];
				}
				else
				{
					$sort = array();

					foreach($this->_query['sort'] as $column)
					{
						$sort[] = $column;
					}

					$query[] = implode(', ',$sort);
				}
			}

			if(isset($this->_query['limit']))
			{
				$query[] = 'LIMIT '.$this->_query['limit'];
			}

			if(isset($this->_query['offset']))
			{
				$query[] = 'OFFSET '.$this->_query['offset'];
			}


			$query = implode(' ',$query);

			return [
				'endpoint' => '/_sql?format=json',
				'method' => 'POST',
				'data' => [
					'query' => $query
				]
			];



			$this->_query = array(
        		'db' => $this->_query['db'],
						'table' => $this->_query['table'],
				);

        $query = implode(' ',$query);

        if(!$runtime)
        {
            $parsedQuery = $query;
            $index = 0;


            while(strpos($parsedQuery,' ?')!==false)
            {
                $parsedQuery = $this->str_replace_first(' ?',' '.$index.'?',$parsedQuery);
                $index++;
            }

            foreach($args as $index => $arg)
            {
                $parsedQuery = str_replace($index.'?',$this->_escape($arg),$parsedQuery);
            }

            return (string) new FullyQualifiedQuery($parsedQuery);
        }
        else
        {
            foreach($args as $i=>$arg)
            {
                if($arg instanceof Map
										|| $arg instanceof Set
                    || $arg instanceof BigInt
                    || $arg instanceof Timestamp
                    || $arg instanceof Uuid
                    || $arg instanceof Timeuuid
                    || $arg instanceof Date
                    || $arg instanceof Time
                    || $arg instanceof Tinyint
                    || $arg instanceof Decimal
                    || $arg instanceof SimpleFloat
                    || $arg instanceof Blob
                    || $arg instanceof Inet
                ){
                    $args[$i] = $arg->value();
                }
            }
        }

        return [
            'preparedQuery'     => (string) new FullyQualifiedQuery($query),
            'arguments'         => $args
        ];
    }

    public function str_replace_first($from, $to, $content)
    {
        $from = '/'.preg_quote($from, '/').'/';

        return preg_replace($from, $to, $content, 1);
    }

    /*
    * Prevent SQL Injection on database name, table name, field names
    */
    public function escapeKey($key)
    {
        if(
            $key instanceof Sql
        )
        {
            return (string) $key;
        }
        elseif(trim($key)=='*')
        {
            return '*';
        }
        elseif(strpos($key,'.')===false)
        {
            return '"'.$key.'"';
        }
        else
        {
            $keys = explode('.',$key);
            foreach($keys as $index => $key)
            {
                $keys[$index] = $key;
            }

            return '"'.implode('.',$keys).'"';
        }
    }

//    public function &whereSql($statement,$vars=null)
//    {
//        $this->_query['whereSql'] = array('statement'=>$statement,'vars'=>$vars);
//
//        return $this;
//    }

    //todo  check ?
//    public function &truncate()
//    {
//        $this->_query['type']		= "TRUNCATE";
//
//        return $this;
//    }


    public function &select($columns =null)
    {
        $this->_query['type']		= "SELECT";
        $this->_query['columns']	= $columns;

        return $this;
    }

    public function &offset($offset)
    {
        $this->_query['offset'] 	= $offset;

        return $this;
    }

    public function &limit($limit)
    {
        $this->_query['limit'] 		= $limit;

        return $this;
    }

//    public function &find()
//    {
//        $args = func_get_args();
//        if(count($args)==1) $args = $args[0];
//
//        $this->_query['where'][] 	= $args;
//
//        return $this;
//    }

//    public function &filter()
//    {
//        $args = func_get_args();
//        if(count($args)==1) $args = $args[0];
//
//        if(isset($this->_query['where']))
//        {
//            $args[-1] 	= 'AND';
//        }
//
//        $this->_query['where'][] 	= $args;
//
//        return $this;
//    }

    public function &where()
    {
        $args = func_get_args();
        if(count($args)==1) $args = $args[0];

        $this->_query['where'][] 	= $args;

        return $this;
    }

//    public function &alternatively()
//    {
//        $args = func_get_args();
//
//        $args[-1] 	= 'OR';
//        $args[0] 	= $args[0];
//
//        $this->_query['where'][] 	= $args;
//
//        return $this;
//    }

    public function &also()
    {
        $args = func_get_args();

        $args[-1] 	= 'AND';
        $args[0] 	= $args[0];

        $this->_query['where'][] 	= $args;

        return $this;
    }

    public function &index($index)
    {
        $this->_query['index'] 		= $index;

        return $this;
    }

//    public function &aka($as)
//    {
//        if(!isset($this->_query['join']))
//            $this->_query['as'] 									= $as;
//        else
//            $this->_query['join'][$this->_currentJoinIndex]['as'] 	= $as;
//
//        return $this;
//    }


	/**
	 * @return Client
	 */
	public function getResult()
	{
		return $this->_result;
	}

	public function getRawResult()
	{
		return $this->_result->getRawResult();
	}


    /*
    * AUTO RUNNNERS
    */
    public function __get($name)
    {
        $this->run();
        return $this->_result->{$name};
    }

    public function getAffectedRows()
    {
        $this->run();
        return $this->_result->getAffectedRows();
    }

    public function getInsertedId()
    {
        $this->run();
        return $this->_result->getInsertedId();
    }

    public function isEmpty()
    {
        $this->run();
        return $this->_result->isEmpty();
    }

    public function fetch()
    {
        $this->run();
        return $this->_result->fetch();
    }

    public function getFirstRow()
    {
        $this->run();
        return $this->_result->getFirstRow();
    }

    public function fetchAll()
    {
        $this->run();
        return $this->_result->fetchAll();
    }

    public function length()
    {
        $this->run();
        return $this->_result->length();
    }

    public function rewind()
    {
        $this->run();
        $this->_result->rewind();
    }

    public function current()
    {
        return $this->_result->current();
    }

    public function key()
    {
        return $this->_result->key();
    }

    public function next()
    {
        return $this->_result->next();
    }

    public function valid()
    {
        return $this->_result->valid();
    }

    public function getKeyspace()
		{
			return $this->_query['db'];
		}

		public function getTable()
		{
			return $this->_query['table'];
		}

		public function getWhereArguments()
		{
			return $this->_query['where'];
		}

		public function resetResult()
		{
			$this->_result = null;
			return $this;
		}

		public function nextPage()
		{
			$this->_result->nextPage();
		}

    public function __toString()
    {
        return $this->getQuery();
    }

		public function doc($id =null)
		{
			return (new Doc($id))->table($this->_query['index']);
		}

		public function getCursorId()
		{
			return $this->_result->getCursorId();
		}

		public function getScrollId()
		{
			return $this->_result->getScrollId();
		}

        public function aggs()
        {
            return $this->_result->aggs();
        }

		public function getCurrentPage()
		{
			return $this->_result->getCurrentPage();
		}

		public function getByCursorId($cursorId, $columns = [])
		{
			$this->_query['rawQuery'] = true;
			$this->_query['type'] = "SQL";
			$this->_query['data'] = [
				'cursor' => $cursorId,
				//"columnar" => true
			];
			$this->_query['method'] = 'POST';
			$this->_query['endpoint'] = '/_sql?format=json';
			$this->_query['cursorColumns'] = $columns;
			

			return $this;
		}

		public function getCursorColumns()
		{
			return $this->_result->getCursorColumns();
		}

	public function getByScrollId($scrollId, $alive = '1m')
	{
		$this->_query['rawQuery'] = true;
		$this->_query['type'] = "SEARCH";
		$this->_query['data'] = [
			'scroll_id' => $scrollId,
			"scroll" => $alive
		];
		$this->_query['method'] = 'POST';
		$this->_query['endpoint'] = '/_search/scroll';

		return $this;
	}

	public function autoScroll()
	{
		$this->_query['autoScroll'] = true;
		return $this;
	}

	public function getTotalCount()
	{
		return $this->_result->getTotalCount();
	}

}
