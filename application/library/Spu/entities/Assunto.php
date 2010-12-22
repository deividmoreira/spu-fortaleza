<?php
require_once('BaseEntity.php');
Loader::loadDao('AssuntoDao');
class Assunto extends BaseEntity
{
    protected $_nodeRef;
    protected $_nome;
    protected $_categoria;
    protected $_corpo;
    protected $_notificarNaAbertura;
    
    public function getNodeRef()
    {
        return $this->_nodeRef;
    }
    
    public function setNodeRef($nodeRef)
    {
        $this->_nodeRef = $nodeRef;
    }
    
    public function getNome()
    {
        return $this->_nome;
    }
    
    public function setNome($nome)
    {
        $this->_nome = $nome;
    }
    
    public function getCategoria()
    {
        return $this->_categoria;
    }
    
    public function setCategoria($categoria)
    {
        $this->_categoria = $categoria;
    }
    
    public function getCorpo()
    {
        return $this->_corpo;
    }

    public function setCorpo($value)
    {
        $this->_corpo = $value;
    }
    
	public function getNotificarNaAbertura()
    {
        return $this->_notificarNaAbertura;
    }

	public function setNotificarNaAbertura($value)
    {
        $this->_notificarNaAbertura = $value;
    }

    public function getId()
    {
        $nodeRef = $this->getNodeRef();
        return substr($nodeRef, strrpos($nodeRef, '/') + 1);
    }
    
    public function listar()
    {
    	$dao = $this->_getDao();
    	$hashDeAssuntos = $dao->getAssuntos();
        
        $assuntos = array();
        foreach ($hashDeAssuntos[0] as $hashAssunto) {
            $assunto = new Assunto($this->_getTicket());
            $assunto->_loadAssuntoFromHash($hashAssunto[0]);
            $assunto->setCategoria($hashAssunto[0]['tipoProcesso']);
            $assuntos[] = $assunto;
        }
        
        return $assuntos;
    }
    
	protected function _getDao()
    {
    	$dao = new AssuntoDao(self::ALFRESCO_URL, $this->_getTicket());
    	return $dao;
    }
    
    protected function _loadAssuntoFromHash($hash)
    {
        $this->setNodeRef($this->_getHashValue($hash, 'noderef'));
        $this->setNome($this->_getHashValue($hash, 'nome'));
        $this->setCorpo($this->_getHashValue($hash, 'corpo'));
        $this->setNotificarNaAbertura($this->_getHashValue($hash, 'notificarNaAbertura') ? true : false);
    }
    
    public function listarPorTipoProcesso($nomeTipoProcesso)
    {
        $dao = $this->_getDao();
        $hashDeAssuntos = $dao->getAssuntosPorTipoProcesso($nomeTipoProcesso);
        
        $assuntos = array();
        foreach ($hashDeAssuntos as $hashAssunto) {
            $assunto = new Assunto($this->_getTicket());
            $assunto->_loadAssuntoFromHash($hashAssunto);
            $assuntos[] = $assunto;
        }
        
        return $assuntos;
    }
    
    public function carregarPeloId($id)
    {
        $dao = $this->_getDao();
        $hashDeAssuntos = $dao->getAssunto($id);
        
        foreach ($hashDeAssuntos as $hashAssunto) {
            $hashDadosAssunto = array_pop($hashAssunto); 
            $this->_loadAssuntoFromHash($hashDadosAssunto);
            $this->setCategoria($hashDadosAssunto['tipoProcesso']);
        }
    }
}
?>