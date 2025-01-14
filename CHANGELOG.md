### 1.0.4 (2025-01-14)
1. **PHP 8.4 Compatibility. (Implicitly marking parameter as nullable is deprecated.)**
    1. ***Simpletools\Db\Elasticsearch\Client***
        1. Amended `__construct()` function to allow nullable string parameter `$cluster`
        2. Amended `execute()` function to allow nullable string/array parameters `$data` and `$retryPoints`
    2. ***Simpletools\Db\Elasticsearch\Doc***
        1. Amended `__construct()` function to allow nullable string parameters `$id` and `$index`
        2. Amended `getUpdateQuery()` function to allow nullable DSL parameter `$dsl`
        3. Amended `update()` function to allow nullable DSL parameter `$dsl`
        4. Amended `body()` function to allow nullable Body/array/string parameter `$body`
    3. ***Simpletools\Db\Elasticsearch\Model***
        1. Amended `__construct()` function to allow nullable string parameter `$cluster`
        2. Amended `doc()` function to allow nullable string/array parameters `$id` and `$index`
    4. ***Simpletools\Db\Elasticsearch\Query***
        1. Amended `__construct()` function to allow nullable string parameter `$index`
        2. Amended `callApi()` function to allow nullable string/array parameter `$data`
        3. Amended `&select()` function to allow nullable string/array parameter `$columns`
        4. Amended `doc()` function to allow nullable string/array parameter `$id`

### 1.0.3 (2024-02-07)
1. **Simpletools\Db\Elasticsearch\Client**
   1. Fixed single quotes escaping
2.  **Simpletools\Db\Elasticsearch\Query**
1. Fixed single quotes escaping

### 1.0.1 (2023-01-19)
1. **Simpletools\Db\Elasticsearch\Doc\Body**
  1. Added mixed return type to jsonSerialize() function
2. **Simpletools\Db\Elasticsearch\DSL**
  1. Added mixed return type to jsonSerialize() function
3. **Simpletools\Db\Elasticsearch\SQL**
  1. Added mixed return type to jsonSerialize() function
4. **Simpletools\Db\Elasticsearch\Query**
  1. Updated Iterator functions with return types
    - current() : mixed
    - next() : void
    - key() : mixed
    - rewind() : void
    - valid() : bool
5. **Simpletools\Db\Elasticsearch\Result**
  1. Updated Iterator functions with return types
    - current() : mixed
    - next() : void
    - key() : mixed
    - rewind() : void
    - valid() : bool

### 0.1.14 (2022-07-21)
1. **Simpletools\Db\Elasticsearch\Client**
   1. Improved retries

### 0.1.13 (2022-03-01)
1. **Simpletools\Db\Elasticsearch\Client**
    1. Disabled SSL host verification

### 0.1.12 (2021-09-27)
1. **Simpletools\Db\Elasticsearch\Client**
    1. Fix retries bug

### 0.1.11 (2021-09-25)
1. **Simpletools\Db\Elasticsearch\Client**
    1. Fix retries bug

### 0.1.10 (2021-09-24)
1. **Simpletools\Db\Elasticsearch\Client**
    1. Improved auto-reconnect

### 0.1.9 (2021-09-23)
1. **Simpletools\Db\Elasticsearch\Client**
    1. Added multi hosts support
    2. Added auto-reconnect to the next host
    3. Added `timeout` and `connectTimeout` to the clusrter settings

### 0.1.8 (2021-07-16)
1. **Simpletools\Db\Elasticsearch\Query**
    1. Added params to all endpoints   
2. **Simpletools\Db\Elasticsearch\Doc**
    1. Added `->params()` method  

### 0.1.7 (2021-03-29)
1. **Simpletools\Db\Elasticsearch\Query**
   1. Added `->routing()` method   
2. **Simpletools\Db\Elasticsearch\Batch**
    1. Added `->routing()` method
    2. Added `->params()` method
3. **Simpletools\Db\Elasticsearch\Doc**
    1. Added `->routing()` method  

### 0.1.6 (2020-12-05)
1. **Simpletools\Db\Elasticsearch\Doc**
   1. Validate Doc Id
   
### 0.1.5 (2020-08-25)
1. **Simpletools\Db\Elasticsearch\Query**
   1. Added `->aggs()` to retrieve aggregations
2. **Simpletools\Db\Elasticsearch\Result**
   1. Added `->aggs()` to retrieve aggregations
   2. Updated `->getScrollId()` and `->getTotalCount()` so they can run before `->fetch()`
 
### 0.1.4 (2020-07-17)
1. **Simpletools\Db\Elasticsearch\Batch**
   1. Added error handling

### 0.1.3 (2020-06-29)
1. **Simpletools\Db\Elasticsearch\Query**
   1. Fixed the replication inserted ID bug

### 0.1.2 (2020-06-29)
1. **Simpletools\Db\Elasticsearch\Query**
   1. Fixed the replication object bug
 
### 0.1.1 (2020-05-22)
1. **Simpletools\Db\Elasticsearch\Query**
    1. Added `->getTotalCount()` for search results
2. **Simpletools\Db\Elasticsearch\Result**
    1. Added `->getTotalCount()` for search results

### 0.1.0 (2020-02-18)
1. **Simpletools\Db\Elasticsearch**
    1. Added integration with `Simpletools\Db\Replicator` to replicate data between databases
2. **Simpletools\Db\Elasticsearch\Query**
    1. Added `->addAlias()` and `->removeAlias()` to control aliases
3 **Simpletools\Db\Elasticsearch\Batch**
    1. Added `->constraint()` to force constraint index

### 0.0.3 (2020-02-11)
1. **Simpletools\Db\Elasticsearch\Query**
    1. Added `->autoScroll()` to enable auto pagination on a foreach and `->fetch()`

### 0.0.2 (2020-02-10)
1. **Simpletools\Db\Elasticsearch\Query**
    1. Added `->getCursorId()` and `->getByCursorId()` for the manual SQL pagination
    2. Added `->getScrollId()` and `->getByScrollId()` for the manual Search pagination
    3. Added `->getCurrentPage()` to get all data from the current page
        
### 0.0.1 (2020-02-07)
1. **Simpletools\Db\Elasticsearch**
    1. Structure setup
2. **Simpletools\Db\Elasticsearch\Doc**
3. **Simpletools\Db\Elasticsearch\DSL**
4. **Simpletools\Db\Elasticsearch\Exception**
5. **Simpletools\Db\Elasticsearch\Model**
6. **Simpletools\Db\Elasticsearch\Query**
7. **Simpletools\Db\Elasticsearch\Result**
8. **Simpletools\Db\Elasticsearch\SQL**
9. **Simpletools\Db\Elasticsearch\Batch**
10. **Simpletools\Db\Elasticsearch\Doc\Body**