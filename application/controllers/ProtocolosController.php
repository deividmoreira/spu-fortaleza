<?php
Loader::loadEntity('Protocolo');
class ProtocolosController extends BaseController
{
    public function indexAction()
    {
    	$protocolo = new Protocolo($this->getTicket());
        $listaProtocolos = $protocolo->listarTodos();
        $this->view->lista = $listaProtocolos;
    }
    
    public function editarAction()
    {
        $id = $this->_getIdFromUrl();
        
        $protocolo = new Protocolo($this->getTicket());
        $protocolo->carregarPeloId($id);
        
        $this->view->protocolo = $protocolo;
        $this->view->id = $protocolo->getId();
    }
    
    private function _getIdFromUrl()
    {
        $id = $this->getRequest()->getParam('id');
        return $id;
    }
}