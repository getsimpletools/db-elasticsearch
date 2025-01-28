<?php

namespace Simpletools\Db\Elasticsearch;

class Model extends Client
{
    protected static $____selfModel;
    protected $___cluster;

    public function __construct(mixed $cluster=null)
    {
        $this->___cluster 	= defined('static::CLUSTER') ? static::CLUSTER : $cluster;

        parent::__construct($this->___cluster);
    }

    public static function self()
    {
        if(isset(static::$____selfModel[static::class]))
            return static::$____selfModel[static::class];

        $obj = new static();

        if(method_exists($obj, 'init') && is_callable(array($obj,'init')))
        {
            call_user_func_array(array($obj,'init'),func_get_args());
        }

        return static::$____selfModel[static::class]   = $obj;
    }

    public function index($index)
    {
        $args 	= func_get_args();
				$index 	= array_shift($args);

        $query = new Query($index);

        return $query;
    }

		public function doc(mixed $id =null,mixed $index = null)
		{
			return new Doc($id, $index);
		}
}
