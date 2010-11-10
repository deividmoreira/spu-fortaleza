<?php
/*
 * Copyright (C) 2005 Alfresco, Inc.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.

 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.

 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.

 * As a special exception to the terms and conditions of version 2.0 of 
 * the GPL, you may redistribute this Program in connection with Free/Libre 
 * and Open Source Software ("FLOSS") applications as described in Alfresco's 
 * FLOSS exception.  You should have recieved a copy of the text describing 
 * the FLOSS exception, and it is also available here: 
 * http://www.alfresco.com/legal/licensing"
 */

require_once 'Store.php';
require_once 'Node.php';
require_once 'WebService/WebServiceFactory.php';
require_once 'ContentType.php';

class AlfSession extends AlfBaseObject {
// AW LAVA FLOW? public $authenticationService;
    public $repositoryService;
    public $contentService;
    public $dictionaryService;

    private $_repository;
    private $_ticket;
    private $_stores = NULL;
    private $_namespaceMap = NULL;

    private $nodeCache;
    private $idCount = 0;

    /**
     * Constructor
     *
     * @param userName the user name
     * @param ticket the currenlty authenticated users ticket
     */
    public function __construct($repository, $ticket) {
        $this->nodeCache = array();

        $this->_repository = $repository;
        $this->_ticket = $ticket;

        $this->repositoryService = AlfWebServiceFactory::getRepositoryService($this->_repository->connectionUrl, $this->_ticket);
        $this->contentService = AlfWebServiceFactory::getContentService($this->_repository->connectionUrl, $this->_ticket);
        $this->dictionaryService = AlfWebServiceFactory::getDictionaryService($this->_repository->connectionUrl, $this->_ticket);
    }

    //
    // aw@abcona.de 2009-10-09:
    // To support (de)serialization, we need to implement the magic
    // __sleep() and __wakeup() methods - the xxService objects are
    // not serializable!

    public function __sleep() {
    //
    // Nullify service references
    //
        $this->repositoryService = NULL;
        $this->contentService = NULL;
        $this->dictionaryService = NULL;

        //
        // Also nullify cached stores and namespacemap
        // These will be lazily restored when first get() after wakeup
        //
        $this->_stores = NULL;
        // TODO: Explain this!! $this->_namespaceMap = NULL;

        //
        // Return array of object variables to be saved...
        // (Include the NULLified ones, not sure if we need this..
        //
        return array('_repository', '_ticket', 'nodeCache', 'idCount',
        'repositoryService', 'contentService', 'dictionaryService',
        '_stores', '_namespaceMap');
    }

    public function __wakeup() {
        //
        // Restore service references
        //
        $this->repositoryService = AlfWebServiceFactory::getRepositoryService($this->_repository->connectionUrl, $this->_ticket);
        $this->contentService = AlfWebServiceFactory::getContentService($this->_repository->connectionUrl, $this->_ticket);
        $this->dictionaryService = AlfWebServiceFactory::getDictionaryService($this->_repository->connectionUrl, $this->_ticket);
    }

    /**
     * Creates a new store in the current respository
     *
     * @param $address the address of the new store
     * @param $scheme the scheme of the new store, default value of 'workspace'
     * @return Store the new store
     */
    public function createStore($address, $scheme="workspace") {
    // Create the store
        $result = $this->repositoryService->createStore(array(
            "scheme" => $scheme,
            "address" => $address));
        $store = new AlfStore($this, $result->createStoreReturn->address, $result->createStoreReturn->scheme);

        // Add to the cached list if its been populated
        if (isset($this->_stores) == true) {
            $this->_stores[] = $store;
        }

        // Return the newly created store
        return $store;
    }

    /**
     * Get the store
     *
     * @param $address the address of the store
     * @param $scheme the scheme of the store.  The default it 'workspace'
     * @return Store the store
     */
    public function getStore($address, $scheme="workspace") {
        return new AlfStore($this, $address, $scheme);
    }

    /**
     * Get the store from it string representation (eg: workspace://SpacesStore)
     *
     * @param $value the stores string representation
     * @return Store the store
     */
    public function getStoreFromString($value) {
        list($scheme, $address) = split("://", $value);
        return new AlfStore($this, $address, $scheme);
    }

    public function getNode($store, $id, $score=1.0) {
        $node = $this->getNodeImpl($store, $id);
        if ($node == null) {
            $node = new AlfNode($this, $store, $id, $score);
            $this->addNode($node);
        }
        return $node;
    }

    public function getNodeFromString($value) {
    // TODO
        throw new Exception("getNode($value) not yet implemented");
    }

    /**
     * Adds a new node to the session.
     */
    public function addNode($node) {
        $this->nodeCache[$node->__toString()] = $node;
    }

    private function getNodeImpl($store, $id) {
        $result = null;
        $nodeRef = $store->scheme . "://" . $store->address . "/" . $id;
        if (array_key_exists($nodeRef, $this->nodeCache) == true) {
            $result = $this->nodeCache[$nodeRef];
        }
        return $result;
    }

    /**
     * Commits all unsaved changes to the repository
     */
    public function save($debug=false) {
    // Build the update statements from the node cache
        $statements = array();
        foreach ($this->nodeCache as $node) {
            $node->onBeforeSave($statements);
        }

        if ($debug == true) {
            var_dump($statements);
            echo ("<br><br>");
        }

        if (count($statements) > 0) {
        // Make the web service call
            $result = $this->repositoryService->update(array("statements" => $statements));
            //var_dump($result);

            // Update the state of the updated nodes
            foreach ($this->nodeCache as $node) {
                $node->onAfterSave($this->getIdMap($result));
            }
        }
    }

    /**
     * Clears the current session by emptying the node cache.
     *
     * WARNING:  all unsaved changes will be lost when clearing the session.
     */
    public function clear() {
    // Clear the node cache
        $this->nodeCache = array();
    }

    private function getIdMap($result) {
        $return = array();
        $statements = $result->updateReturn;
        if (is_array($statements) == true) {
            foreach ($statements as $statement) {
                if ($statement->statement == "create") {
                    $id = $statement->sourceId;
                    $uuid = $statement->destination->uuid;
                    $return[$id] = $uuid;
                }
            }
        }
        else {
            if ($statements->statement == "create") {
                $id = $statements->sourceId;
                $uuid = $statements->destination->uuid;
                $return[$id] = $uuid;
            }
        }
        return $return;
    }

    public function query($store, $query, $language='lucene') {
    // TODO need to support paged queries
        $result = $this->repositoryService->query(array(
            "store" => $store->__toArray(),
            "query" => array(
            "language" => $language,
            "statement" => $query),
            "includeMetaData" => false));

        // TODO for now do nothing with the score and the returned data
        $resultSet = $result->queryReturn->resultSet;
        return $this->resultSetToNodes($this, $store, $resultSet);
    }

    public function getTicket() {
        return $this->_ticket;
    }

    public function getRepository() {
        return $this->_repository;
    }

    public function getNamespaceMap() {
        if ($this->_namespaceMap == null) {
            //$this->_namespaceMap = new AlfNamespaceMap();
            $this->_namespaceMap = AlfNamespaceMap::getInstance();
        }
        return $this->_namespaceMap;
    }

    public function getStores() {
        if (isset ($this->_stores) == false) {
            $this->_stores = array ();
            $results = $this->repositoryService->getStores();

            foreach ($results->getStoresReturn as $result) {
                $this->_stores[] = new AlfStore($this, $result->address, $result->scheme);
            }
        }

        return $this->_stores;
    }

    /** Want these methods to be package scope some how! **/

    public function nextSessionId() {
        $sessionId = "session".$this->_ticket.$this->idCount;
        $this->idCount ++;
        return $sessionId;
    }

    //
    // Extensions for access to DictionaryService
    //

    public function getContentType($typeName = "cm:content") {

        $nsm =  $this->getNamespaceMap();

        $soapArgs = array(
            'types' => array(
            'names' => $nsm->getShortName($typeName, ':'),
            'followSubClass' => false,
            'followSuperClass' => false
            ),
            'aspects' => array(
            'followSubClass' => false,
            'followSuperClass' => false
            ),
        );

        $soapRC = $this->dictionaryService->getClasses($soapArgs);
        // TODO: Error handling

        $clazz = $soapRC->getClassesReturn;
        return new AlfContentType($this, $clazz->name, $clazz->title, $clazz->superClass);
    }


}
?>