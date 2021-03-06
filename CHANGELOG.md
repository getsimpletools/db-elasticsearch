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