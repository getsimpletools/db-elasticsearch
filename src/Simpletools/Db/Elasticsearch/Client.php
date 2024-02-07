<?php

namespace Simpletools\Db\Elasticsearch;

class Client
{
		protected static $_gSettings            = array();
		//protected static $_pluginSettings            = array(); //['convertMapToJson' => true|false]

		protected static $_defaultCluster       = 'default';
    protected $___cluster	                = 'default';

    protected $___accessPoints =[];
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

				$hosts = $settings['host'];
				if(!is_array($hosts)) $hosts = [$hosts];

				foreach ($hosts as $host)
				{
					$accessPoint = $settings['protocol'];
				if(isset($settings['username']) && isset($settings['password']))
				{
						$accessPoint.=$settings['username'].':'.$settings['password']."@";
				}
					$accessPoint.= $host.":".$settings['port']."/";
					$this->___accessPoints[] = $accessPoint;
				}
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

		public function execute($endpoint, $method = 'GET', $data = null,  $contentType ='application/json',$attempt=0, $retryPoints=null)
		{
			$settings = self::$_gSettings[self::$_defaultCluster];

			$endpoint = ltrim($endpoint, '/');

			if(strpos($endpoint,'?') === false)
				$endpoint.='?pretty=true';
			else
				$endpoint = str_replace('?','?pretty=true&',$endpoint);

			if($retryPoints ===null)
				$retryPoints = $this->___accessPoints;

			$randKey = array_rand($retryPoints);
			$randAccessPoint = $retryPoints[$randKey];

			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $randAccessPoint.$endpoint);
			curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
			curl_setopt($curl, CURLOPT_FRESH_CONNECT,true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, @$settings['connectTimeout']?:5);
			curl_setopt($curl, CURLOPT_TIMEOUT, @$settings['timeout']?:30);
			if ($data)
			{
				if (!is_string($data)) $data = json_encode($data);

				curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
				curl_setopt( $curl, CURLOPT_HTTPHEADER, array('Content-Type:'.$contentType));
			}
			$res = curl_exec($curl);
			$err =curl_errno($curl);
			$errMsg = curl_error($curl);
			$httpcode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
			curl_close($curl);

			if ($err || $httpcode == 503)
			{
				//6 - CURLE_COULDNT_RESOLVE_HOST
				//7 - CURLE_COULDNT_CONNECT
				//28 - CURLE_OPERATION_TIMEDOUT
        //52 - Empty reply from server

				if(($httpcode == 503 || in_array($err,[6,7,28,52])) && $attempt<3)
				{
					if($attempt)
						usleep($attempt*500);

					if(count($retryPoints) > 1)
					{
						unset($retryPoints[$randKey]);
						$retryPoints = array_values($retryPoints);
					}
					return $this->execute($endpoint, $method, $data,  $contentType,$attempt+1, $retryPoints);
				}
				else
					throw new Exception("Curl Error: ".$err.'|'.$errMsg,500);
			}

			return $res;
		}

    public function escape($string,$fromEncoding='UTF-8',$toEncoding='UTF-8', $doubleQuote = false)
    {
        if($fromEncoding!='UTF-8' OR $toEncoding!='UTF-8')
        {
            $string = mb_convert_encoding ($string,$fromEncoding,$toEncoding);
        }
        if($doubleQuote)
        {
          $search = array("\\",  "\x00", "\n",  "\r",  '"', "\x1a");
          $replace = array("\\\\","\\0","\\n", "\\r",  '\"', "\\Z");
        }
        else
        {
          $search = array("\\",  "\x00", "\n",  "\r",  "'",  '"', "\x1a");
          $replace = array("\\\\","\\0","\\n", "\\r", "\'", '\"', "\\Z");
        }

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
