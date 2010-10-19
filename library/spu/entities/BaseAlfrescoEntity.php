<?php
class BaseAlfrescoEntity
{
    const ALFRESCO_URL = 'http://localhost:8080/alfresco/service';
    protected $_ticket;
    protected $_repository;
    
    public function __get($property) {
        $methodName = 'get' . ucwords($property);
        return $this->$methodName();
    }
    
    public function __construct($ticket)
    {
        $this->_setTicket($ticket);
    }
    
    protected function _getTicket()
    {
        return $this->_ticket;
    }
    
    protected function _setTicket($ticket)
    {
        $this->_ticket = $ticket;
    }
    
    protected function _getHashValue($hash, $hashField)
    {
        if (!isset($hash[$hashField])) {
            return null;
        }
        
        if (is_array($hash[$hashField])) {
            $value = array();
            foreach ($hash[$hashField] as $hashValue) {
                $value[] = $hashValue;
            }
        } else {
            $value = $hash[$hashField];
        }
        return $value;
    }
}
?>