<?php

namespace Simpletools\Db\Elasticsearch;
use Simpletools\Db\Replicator;

class Batch
{
    protected $_queries = array();
    protected $_client;

    protected $_queriesParsed = array();

    protected $_runOnBulkSize = 0;
    protected $_index;
		protected $_replication = false;
		protected $_replicationQuery;
		protected $_throwError = true;
		protected $_result = false;
		protected $_params = array();

    public function __construct($bulkSize = 0)
    {
			$this->_runOnBulkSize = (int)$bulkSize;

			$this->_replicationQuery =  (object)[
				'insert' => [],
				'update' =>[],
				'delete' =>[]
			];
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

		public function constraint($index)
		{
			$this->_index = $index;
			return $this;
		}

		public function throwError($bool=true)
		{
			$this->_throwError = (bool)$bool;
			return $this;
		}

    public function add($query)
    {
				if($query instanceof Doc)
				{
					$query = $query->getSaveQuery();
				}

        if(!($query instanceof Query))
        {
            throw new Exception("Query is not of a Query type",400);
        }

        $this->_queries[] = $query;

        if($this->_runOnBulkSize && count($this->_queries)==$this->_runOnBulkSize)
        {
            $this->run();
        }

        return $this;
    }

    public function query($index)
    {
        $q = new Query($index);

        $this->add($q);

        return $q;
    }

    public function getQuery()
    {
    	if($this->_queriesParsed) return $this->_queriesParsed;

    	if($this->_index)//constraint
			{
				$this->_replication = Replicator::exists('elasticsearch://bulk@'.$this->_index);
			}


			foreach ($this->_queries as $query)
			{
				$rawQuery = $query->getRawQuery();

				if ($rawQuery['type'] != 'INSERT' && $rawQuery['type'] != 'UPDATE ONE' && $rawQuery['type'] != 'DELETE ONE')
					throw new Exception("Only ->insert(), ->set(),->updateOne(),->updateOne() allow in the Bulk Operation", 400);


				if($this->_index && $rawQuery['index'] != $this->_index)
					throw new \Exception("Your bulk Query(".$rawQuery['index'].") does not match constraint index(".$this->_index.")");


				$query = $query->getQuery();

				if ($rawQuery['type'] == 'INSERT')
				{
					$this->_queriesParsed[] = json_encode(['index' => [
						'_index' => $rawQuery['index'],
						'_id' => $rawQuery['id']]
					]);

					$this->_queriesParsed[] = json_encode($query['data']);

					if($this->_replication)
					{
						$this->_replicationQuery->insert[] = (object)[
							'_id' => $rawQuery['id'],
							'_source' => $query['data']
						];
					}
				}
				elseif ($rawQuery['type'] == 'UPDATE ONE')
				{
					$this->_queriesParsed[] = json_encode(['update' => [
						'_index' => $rawQuery['index'],
						'_id' => $rawQuery['id']
					]]);

					$this->_queriesParsed[] = is_string($query['data']) ? json_encode(json_decode($query['data'])) : json_encode($query['data']);

					if($this->_replication)
					{
						$this->_replicationQuery->update[] = (object)[
							'_id' => $rawQuery['id'],
							'_source' => is_string($query['data']) ? json_decode($query['data']) : $query['data']
						];
					}
				}
				elseif ($rawQuery['type'] == 'DELETE ONE')
				{
					$this->_queriesParsed[] = json_encode(['delete' => [
						'_index' => $rawQuery['index'],
						'_id' => $rawQuery['id']]
					]);

					if($this->_replication)
					{
						$this->_replicationQuery->delete[] = (object)[
							'_id' => $rawQuery['id'],
						];
					}
				}
			}

			$this->_queriesParsed = implode(PHP_EOL, $this->_queriesParsed).PHP_EOL;
			return $this->_queriesParsed;
    }

    public function size()
    {
        return count($this->_queries);
    }

    public function run()
    {
				$this->_result = null;
        if(!$this->_queries)
        {
            throw new Exception("Empty bulk",400);
        }

        if(!$this->_queriesParsed)
        {
					$this->getQuery();
        }

        if(!$this->_client)
            $this->_client = new Client();


				$this->_result = new Result($this->_client->execute('_bulk'.$this->getParamLine(),'POST',$this->_queriesParsed,'application/x-ndjson'), [
					'type' => "BULK"
				]);

				if($this->_throwError && (isset($this->_result->getRawResult()->errors) && $this->_result->getRawResult()->errors))
				{
					$msg = 'Undefined bulk insert error, check raw result for more details';

					foreach ($this->_result->getRawResult()->items as $index => $item)
					{
						if(isset($item->index->error->reason) && $item->index->error->reason)
						{
							$msg = 'Item Index: '.$index.' : '.$item->index->error->reason;
							break;
						}
					}
					throw new Exception($msg,400);
				}

				if($this->_replication)
				{
					Replicator::trigger('elasticsearch://bulk@'.$this->_index, $this->_replicationQuery);
				}

        $this->reset();

				return $this->_result->getRawResult();
    }

    public function getRawResult()
		{
			if($this->_result)
				return $this->_result->getRawResult();

			return null;
		}

		public function routing($routing)
		{
			$this->_params = $routing;
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
					$this->_params = array_merge(	$this->_params,$arr);
				}
			}
			elseif (is_object($params)) $params = json_decode(json_encode($params), true);

			if(is_array($params))
			{
				$this->_params = array_merge(	$this->_params,$params);
			}

			return $this;
		}

		protected function getParamLine()
		{
			$line = [];
			foreach ($this->_params as $key => $val)
			{
				$line[] = $key.'='.$val;
			}

			if($line) return '?'.implode('&',$line);

			return '';
		}

    public function runIfNotEmpty()
    {
        if(!$this->_queries)
            return false;
        else
            return $this->run();
    }

    public function reset()
    {
        $this->_queries         = array();
        $this->_queriesParsed   = array();
				$this->_replicationQuery =  (object)[
					'insert' => [],
					'update' =>[],
					'delete' =>[]
				];

        return $this;
    }

    public function runEvery($bulkSize)
    {
        $this->_runOnBulkSize = (int) $bulkSize;

        return $this;
    }
}
