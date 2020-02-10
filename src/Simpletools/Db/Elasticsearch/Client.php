<?php

namespace Simpletools\Db\Elasticsearch;

class Client
{
		protected static $_gSettings            = array();
		//protected static $_pluginSettings            = array(); //['convertMapToJson' => true|false]

		protected static $_defaultCluster       = 'default';
    protected $___cluster	                = 'default';

    protected $___accessPoint ='';
    protected $___connection;


    public function __construct($cluster=null)
    {
        if($cluster)
            $this->___cluster = $cluster;
        else
            $this->___cluster = self::$_defaultCluster;

        if($cluster && !isset(self::$_gSettings[$cluster]))
        {
            throw new Exception("No settings for provided cluster $cluster",400);
        }

				if(isset(self::$_gSettings[$cluster]))
				{
					$settings = self::$_gSettings[$cluster];
				}
				elseif(isset(self::$_gSettings[self::$_defaultCluster]))
				{
					$settings = self::$_gSettings[self::$_defaultCluster];
				}
				else
					throw new Exception("No settings for default cluster",400);


				//todo add multi hosts
				$this->___accessPoint.=$settings['protocol'];
				if(isset($settings['username']) && isset($settings['password']))
				{
					$this->___accessPoint.=$settings['username'].':'.$settings['password']."@";
				}

				$this->___accessPoint.=$settings['host'][0].":".$settings['port']."/";


				//self::$_pluginSettings[$cluster] = array();
    }

    public static function cluster(array $settings,$cluster='default')
    {
        $cluster                = (isset($settings['name']) ? $settings['name'] : $cluster);
        $default                = (isset($settings['default']) ? (bool) $settings['default'] : false);

        $settings['host']       = isset($settings['host']) ? $settings['host'] : @$settings['hosts'];
        $settings['port']       = isset($settings['port']) ? (int) $settings['port'] : 9200;
				$settings['protocol']   = isset($settings['protocol']) ? $settings['protocol'] : 'http://';

        if(!isset($settings['host']))
        {
            throw new \Exception('Please specify host or hosts');
        }

        if(!is_array($settings['host']))
        {
            $settings['host'] = array($settings['host']);
        }

        if($default)
        {
            self::$_defaultCluster = $cluster;
        }

        self::$_gSettings[$cluster] = $settings;
    }

    public function getCluster()
		{
			return $this->___cluster;
		}

		public function get($endpoint)
		{
			return $this->execute($endpoint, 'GET');
		}

		public function put($endpoint, $data = null)
		{
			return $this->execute($endpoint, 'PUT', $data);
		}

		public function post($endpoint, $data = null)
		{
			return $this->execute($endpoint, 'POST', $data);
		}

		public function delete($endpoint, $data = null)
		{
			return $this->execute($endpoint, 'DELETE', $data);
		}

		public function execute($endpoint, $method = 'GET', $data = null,  $contentType ='application/json')
		{
			$endpoint = ltrim($endpoint, '/');

			if(strpos($endpoint,'?') === false)
				$endpoint.='?pretty=true';
			else
				$endpoint = str_replace('?','?pretty=true&',$endpoint);

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $this->___accessPoint.$endpoint);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			if ($data)
			{
				if (!is_string($data)) $data = json_encode($data);

				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-Type:'.$contentType));
			}
			$res = curl_exec($curl);
			curl_close($curl);

			return $res;
		}

    public function escape($string,$fromEncoding='UTF-8',$toEncoding='UTF-8')
    {
        if($fromEncoding!='UTF-8' OR $toEncoding!='UTF-8')
        {
            $string = mb_convert_encoding ($string,$fromEncoding,$toEncoding);
        }

        $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
        $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");

        return str_replace($search, $replace, $string);
    }

    public function __get($index)
    {
        $query = new Query($index);

        return $query;
    }

    public function __call($table,$args)
    {
				if(count($args) == 1)
				{
					$args = $args[0];
				}

        $query = new Query($table, $this->___keyspace);
        $query->columns($args);

        return $query;
    }
}
