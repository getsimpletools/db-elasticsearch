<?php

namespace Simpletools\Db\Elasticsearch;

class Bulk
{
    protected $_queries = array();
    protected $_client;

    protected $_queriesParsed = array();

    protected $_runOnBulkSize = 0; //0 - only manual bulk run()

    public function __construct($bulkSize = 0)
    {
			$this->_runOnBulkSize = (int)$bulkSize;
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

			foreach ($this->_queries as $query)
			{
				$rawQuery = $query->getRawQuery();

				if ($rawQuery['type'] != 'INSERT' && $rawQuery['type'] != 'UPDATE ONE' && $rawQuery['type'] != 'DELETE ONE')
					throw new Exception("Only ->insert(), ->set(),->updateOne(),->updateOne() allow in the Bulk Operation", 400);


				$query = $query->getQuery();

				if ($rawQuery['type'] == 'INSERT')
				{
					$this->_queriesParsed[] = json_encode(['index' => [
						'_index' => $rawQuery['index'],
						'_id' => $rawQuery['id']]
					]);

					$this->_queriesParsed[] = json_encode($query['data']);
				}
				elseif ($rawQuery['type'] == 'UPDATE ONE')
				{
					$this->_queriesParsed[] = json_encode(['update' => [
						'_index' => $rawQuery['index'],
						'_id' => $rawQuery['id']
					]]);

					$this->_queriesParsed[] = is_string($query['data']) ? json_encode(json_decode($query['data'])) : json_encode($query['data']);
				}
				elseif ($rawQuery['type'] == 'DELETE ONE')
				{
					$this->_queriesParsed[] = json_encode(['delete' => [
						'_index' => $rawQuery['index'],
						'_id' => $rawQuery['id']]
					]);
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


				$result = new Result($this->_client->execute('_bulk','POST',$this->_queriesParsed,'application/x-ndjson'), [
					'type' => "BULK"
				]);

        $this->reset();

        return $result->getRawResult();
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

        return $this;
    }

    public function runEvery($bulkSize)
    {
        $this->_runOnBulkSize = (int) $bulkSize;

        return $this;
    }
}
