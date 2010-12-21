<?php
/**
 * Classe de Excessões de Regra de Negócio do SGC
 * @author bruno
 * @since 13/05/2010
 */
class AlfrescoApiException extends Exception
{
	const ERROR_CODE = 'AlfrescoApi';
	protected $code;
	protected $description;
    
	/**
     * Construct the exception
     *
     * @param  string $msg
     * @param  string $trace
     * @return void
     */
    public function __construct($msg = '', $description = null)
    {
    	$this->code = self::ERROR_CODE;
    	parent::__construct($msg);
    	$this->description = $description;
    }
    
    public function hasDescription()
    {
    	return $this->description;
    }
    
    public function getDescription()
    {
    	return $this->description;
    }
}