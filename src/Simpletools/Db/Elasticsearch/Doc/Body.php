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

namespace Simpletools\Db\Elasticsearch\Doc;


class Body implements \JsonSerializable
{
    protected $_object;

    public function __construct($object)
    {
        if($object instanceof Body)
        {
            $this->_object    = $object->toObject();
        }
        else
        {
					if(is_array($object)) $object = (object)$object;
					elseif(is_string($object)) $object = json_decode($object);

            $this->_object = $object;
        }
    }

		public function jsonSerialize() {
			return $this->_object;
		}

    public function __toString()
    {
        if(is_string($this->_object)) return $this->_object;
        else return $this->toJson(null);
    }

    public function toJson($options=JSON_PRETTY_PRINT)
    {
        return json_encode($this->_object,$options);
    }

		public function toArray()
		{
			return json_decode(json_encode($this->_object), true);
		}

    public function __isset($name)
    {
        return isset($this->_object->{$name});
    }

		public function __set($name,$value)
		{
			if(is_object($this->_object))
				$this->_object->{$name} = &$value;
			else
				$this->_object = $value;
		}

		public function __unset($name)
		{
			//throw new \Exception("You can't unset property, set it for some default value instead");
			$this->_object->{$name} = null;
		}

    public function &__get($name)
    {
        if(property_exists($this->_object, $name))
        {
            return $this->_object->{$name};
        }
        else
        {
        	return null;
        }
    }

    public function toObject()
    {
        return $this->_object;
    }

    public function to2d()
    {
        $ritit = new \RecursiveIteratorIterator(new \RecursiveArrayIterator((array) $this->_object));
        $result = array();
        foreach ($ritit as $leafValue) {
            $keys = array();
            foreach (range(0, $ritit->getDepth()) as $depth) {
                $keys[] = $ritit->getSubIterator($depth)->key();
            }
            $result[ join('.', $keys) ] = $leafValue;
        }

        return $result;
    }
}
