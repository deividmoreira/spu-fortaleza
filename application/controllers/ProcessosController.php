<?php
Loader::loadEntity('Processo');
class ProcessosController extends BaseController
{
    public function indexAction()
    {
        $processo = new Processo($this->getTicket());
        $this->view->lista = $processo->listarProcessosCaixaEntrada();
    }
    
    public function detalhesAction()
    {
    	try {
    		$idProcesso = $this->_getIdProcessoUrl();
	    	$processo = new Processo($this->getTicket());
	        if ($idProcesso) {
	            $processo->carregarPeloId($idProcesso);
	        }
    	} catch (Exception $e) {
    		
    	}
    	$this->view->processo = $processo;
    }
    
	public function encaminharAction()
    {
    	try {
    		$idProcesso = $this->_getIdProcessoUrl();
    		$processo = new Processo($this->getTicket());
    		if ($idProcesso) {
	            $processo->carregarPeloId($idProcesso);
	        }
	        
    		$listaPrioridades = $this->_getListaPrioridades();
            $listaProtocolos = $this->_getListaProtocolos();
            
    		if ($this->getRequest()->isPost()) {
    			$processo = new Processo($this->getAdminTicket());
    			$processo->tramitar($this->getRequest()->getPost());
    			$this->setSuccessMessage('Processo tramitado com sucesso.');
    			$this->_redirectDetalhesProcesso($idProcesso);
	    	}
    	} catch (Exception $e) {
    		$this->setMessageForTheView($e->getMessage(), 'error');
    	}
    	$this->view->processo = $processo;
    	$this->view->listaPrioridades = $listaPrioridades;
        $this->view->listaProtocolos = $listaProtocolos;
    }
    
	protected function _getIdProcessoUrl()
    {
        $idProcesso = $this->getRequest()->getParam('id');
        return $idProcesso;
    }
    
    protected function _getListaPrioridades()
    {
        $prioridade = new Prioridade($this->getTicket());
        $prioridades = $prioridade->listar();
        $listaPrioridades = array();
        foreach ($prioridades as $prioridade) {
            $listaPrioridades[$prioridade->id] = $prioridade->descricao;
        }
        
        if (count($listaPrioridades) == 0) {
            throw new Exception(
                'Não existe nenhuma prioridade de processo cadastrada no sistema. 
                Por favor, entre em contato com a administração do sistema.'
            );
        }
        
        return $listaPrioridades;
    }
    
    protected function _getListaProtocolos()
    {
        $protocolo = new Protocolo($this->getTicket());
        $protocolos = $protocolo->listar();
        $listaProtocolos = array();
        foreach ($protocolos as $protocolo) {
            $listaProtocolos[$protocolo->id] = $protocolo->descricao;
        }
        
        if (count($listaProtocolos) == 0) {
            throw new Exception(
                'Não existe nenhum protocolo cadastrado no sistema. 
                Por favor, entre em contato com a administração do sistema.'
            );
        }
        
        return $listaProtocolos;
    }
    
    protected function _redirectDetalhesProcesso($idProcesso)
    {
    	$this->_helper->redirector(
            'detalhes', 
            $this->getController(), 
            'default',
            array('id' => $idProcesso)
        );
    }
    
    public function saidaAction()
    {
        $processo = new Processo($this->getTicket());
        $this->view->lista = $processo->listarProcessosCaixaSaida();
    }
    
    public function receberAction()
    {
    	try {
    		if (!$this->getRequest()->isPost() OR !$this->getRequest()->getParam('processosParaReceber')) {
    			throw new Exception("Por favor, selecione pelo menos um processo para receber.");
    		}
    		$processo = new Processo($this->getTicket());
    		$processo->receberVarios($this->getRequest()->getPost());
    	} catch (Exception $e) {
    		$this->setErrorMessage($e->getMessage());
    		$this->_redirectEntrada();
    	}
    	$this->_redirectEmAnalise();
    }
    
	protected function _redirectEmAnalise()
    {
    	$this->_helper->redirector('analise', $this->getController(), 'default');
    }
    
	protected function _redirectEntrada()
    {
    	$this->_helper->redirector('index', $this->getController(), 'default');
    }
    
    public function analiseAction()
    {
    	$processo = new Processo($this->getTicket());
        $this->view->lista = $processo->listarProcessosCaixaAnalise();
    }
}