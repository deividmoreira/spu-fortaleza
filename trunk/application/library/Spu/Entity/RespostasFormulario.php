<?php
require_once('BaseEntity.php');
class RespostasFormulario extends BaseEntity
{
    protected $_data;
    
    //FIXME: Pegar o Label da Pergunta na Resposta do Formulario
    public function loadFromXML($xmlString)
    {
        $xml = simplexml_load_string($xmlString);
        $data = $this->_XMLToArray($xml);
        $data = array_pop($data);
        
        $this->_data = $data;
    }
    
    protected function _XMLToArray($xml)
    { 
        if ($xml instanceof SimpleXMLElement) { 
            $children = $xml->children(); 
            $return = null; 
        } 
    
        foreach ($children as $element => $value) { 
            if ($value instanceof SimpleXMLElement) { 
                $values = (array)$value->children(); 
          
                if (count($values) > 0) { 
                    $return[$element] = $this->_XMLToArray($value); 
                } else { 
                    if (!isset($return[$element])) { 
                        $return[$element] = (string)$value; 
                    } else { 
                        if (!is_array($return[$element])) { 
                            $return[$element] = array($return[$element], (string)$value); 
                        } else { 
                            $return[$element][] = (string)$value; 
                        } 
                    } 
                } 
            } 
        } 
      
        if (is_array($return)) { 
            return $return;
        } else { 
            return $false; 
        } 
    } 
    
    public function hasData()
    {
        return (is_array($this->_data));
    }
    
    public function getData() {
        return $this->_data;
    }
    
    public function setData($value)
    {
        $this->_data = $value;
    }
}