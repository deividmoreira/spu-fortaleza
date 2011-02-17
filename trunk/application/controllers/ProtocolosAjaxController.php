<?php
require_once 'BaseDataTablesController.php';
Loader::loadService('ProtocoloService');
class ProtocolosAjaxController extends BaseDataTablesController
{
    public function listarTodosAction()
    {
        $this->_rows = $this->_getTodosProtocolos();
        $this->_total = 1000;
        
        $this->_helper->layout()->disableLayout();
        $this->view->output = $this->_getOutput();
    }
    
    protected function _getTodosProtocolos()
    {
        $protocoloService = new ProtocoloService($this->getTicket());
        $protocolos = $protocoloService->getTodosProtocolosPaginado($this->_getOffset(),
                                                                    $this->_getPageSize(),
                                                                    $this->_getSearch());
        
        return $this->_convertProtocolosToDataTablesRow($protocolos, true);
    }
    
    protected function _convertProtocolosToDataTablesRow($protocolos, $detalhes = false)
    {
        $rows = array();
        foreach ($protocolos as $protocolo) {
            $row = array();
            $row['nome'] = $protocolo->path;
            
            if ($detalhes) {
                $url = $this->_helper->url('editar', 'protocolos', null, array('id' => $protocolo->id));
                $row['detalhes'] = "<a href='$url'>Detalhes</a>";
            }
            
            $rows[] = $row;
        }
        
        return $rows;
    }
    
    public function listarEstruturaAction()
    {
        $this->_rows = $this->_getEstruturaProtocolos();
        $this->_total = 1000;
        
        $this->_helper->layout()->disableLayout();
        $this->view->output = $this->_getOutput();
    }
    
    protected function _getEstruturaProtocolos()
    {
        $protocoloService = new ProtocoloService($this->getTicket());
        $protocolos = $protocoloService->getTodosProtocolosPaginado($this->_getOffset(),
                                                                    10,
                                                                    $this->_getSearch());
        
        return $this->_convertProtocolosToDataTablesRow($protocolos, false);
    }
    
    public function listarDestinosAction()
    {
        $this->_helper->layout()->disableLayout();
        $this->view->output = $this->_getOutputProtocolos($this->_getListaProtocolosDestino());
    }
    
    public function listarDestinosNovoAction()
    {
        $this->_helper->layout()->disableLayout();
        
        $protocoloOrigemId = $this->_getProtocoloOrigemId();
        $tipoProcessoId = ($this->_getTipoProcessoId()) ? $this->_getTipoProcessoId() : null;
        $filter = ($this->_getSearchTerm()) ? $this->_getSearchTerm() : null;
        
        $this->view->output = $this->_getOutputProtocolos($this->_getListaProtocolosDestinoNovo($protocoloOrigemId,
                                                                                                $tipoProcessoId,
                                                                                                $filter));
    }
    
    protected function _getOutputProtocolos($protocolos) {
        $output = '[';
        if (is_array($protocolos)) {
            $i = 0;
            foreach ($protocolos as $protocolo) {
                $output .= (++$i != 1) ? ',' : '';
                $output .= '{
                                "id":"' . $protocolo->id . '", 
                                "label":"' . $protocolo->path . '", 
                                "value":"' . $protocolo->path . '"
                            }';
            }
        }
        $output .= ']';

        return $output;
    }
    
    protected function _getListaProtocolosDestino()
    {
        $protocoloService = new ProtocoloService($this->getTicket());
        $protocolos = $protocoloService->getTodosProtocolosPaginado($this->_getOffset(),
                                                                    $this->_getPageSize(),
                                                                    $this->_getSearchTerm());
        
        return $protocolos;
    }
    
    protected function _getListaProtocolosDestinoNovo($protocoloOrigemId, $tipoProcessoId = null, $filter = null, $offset = 0, $pageSize = 20)
    {
        $protocoloService = new ProtocoloService($this->getTicket());
        $protocolos = $protocoloService->getProtocolosDestino($protocoloOrigemId,
                                                              $tipoProcessoId,
                                                              $this->_getSearchTerm(),
                                                              $offset,
                                                              $this->_getPageSize());
        
        return $protocolos;
    }
    
    
    protected function _getSearchTerm()
    {
        return $this->getRequest()->getParam('term', null);
    }

    protected function _getProtocolosDestino()
    {
        $protocoloService = new ProtocoloService($this->getTicket());
        $protocolos = $protocolo->getTodosProtocolosPaginado($this->_getOffset(),
                                                             $this->_getPageSize(),
                                                             $this->_getSearch());
        
        return $this->_convertProtocolosDestinoToDataTablesRow($protocolos);
    }
    
    protected function _convertProtocolosDestinoToDataTablesRow($protocolos)
    {
        $rows = array();
        foreach ($protocolos as $protocolo) {
            $row = array();
            $row['input'] = "<input type='radio' name='protocoloDestino' value='" . $protocolo->id . "' />";
            $row['nome'] = $protocolo->path;
            
            $rows[] = $row;
        }
        
        return $rows;
    }

    protected function _getProtocoloOrigemId()
    {
        return $this->getRequest()->getParam('protocoloOrigemId', null);
    }
    
    protected function _getTipoProcessoId()
    {
        return $this->getRequest()->getParam('tipoProcessoId', null);
    }
}