<?php

class ConsultarController extends BaseController
{

    public function indexAction()
    {
        $session = $this->_getSession();
        $session->unsetAll();

        $tiposProcesso = array();
        $listaStatus = array();
        try {
            $tiposProcesso = $this->_getListaTiposProcesso();
            $listaStatus = $this->_getListaStatus();
            $service = new Spu_Service_Protocolo($this->getTicket());
            $listaProtocolos = $service->getProtocolosRaiz();
        } catch (Exception $e) {
            $this->setMessageForTheView($e->getMessage());
        }

        $this->view->tiposProcesso = $tiposProcesso;
        $this->view->listaStatus = $listaStatus;
        $this->view->listaProtocolos = $listaProtocolos;
        $this->view->abaAtiva = 'dadosGerais';
    }

    protected function _getListaTiposProcesso()
    {
        $tipoProcessoService = new Spu_Service_TipoProcesso($this->getTicket());
        $tiposProcesso = $tipoProcessoService->getTiposProcesso();
        $listaTiposProcesso = array();
        $listaTiposProcesso = array_merge($listaTiposProcesso, $this->_getOpcaoVazia());
        foreach ($tiposProcesso as $tipoProcesso) {
            $listaTiposProcesso[$tipoProcesso->id] = $tipoProcesso->nome;
        }

        return $listaTiposProcesso;
    }

    protected function _getListaStatus()
    {
        $statusService = new Spu_Service_Status($this->getTicket());
        $status = $statusService->listar();
        $listaStatus = array();
        $listaStatus = array_merge($listaStatus, $this->_getOpcaoVazia());
        foreach ($status as $s) {
            $listaStatus[$s->id] = $s->nome;
        }

        return $listaStatus;
    }

    protected function _getOpcaoVazia()
    {
        $opcaoVazia = array();
        $opcaoVazia[''] = '';
        return $opcaoVazia;
    }

    public function resultadosAction()
    {
        $session = $this->_getSession();

        if (!$this->getRequest()->isPost() && !isset($session->filtrosConsulta)) {
            $this->setErrorMessage('Busca inválida.');
            $this->_redirectToConsulta();
        }

        if ($this->getRequest()->getPost()) {
            $session->filtrosConsulta = $this->getRequest()->getPost();
        }

        $postData = $session->filtrosConsulta;

        if (isset($postData['globalSearch'])) {
            $globalSearch = $postData['globalSearch'];
            $field = $this->_getFieldFromFilter($globalSearch);
            $postData[$field] = $globalSearch;
        }

        $processoService = new Spu_Service_Processo($this->getTicket());
        $resultado = $processoService->consultar($postData, 0, 4999);
        $this->view->totalDocumentos = count($resultado);
        $this->view->paginator = $this->_helper->paginator()->paginate($resultado);

        if (count($this->view->processos) == 1) {
            $processoId = $processos[0]->id;
            $this->_redirectToProcesso($processoId);
        }

        $this->view->abaAtiva = 'dadosGerais';
    }

    /**
     * @return Zend_Session_Namespace
     */
    protected function _getSession()
    {
        return new Zend_Session_Namespace('spu-consultar');
    }

    private function _getFieldFromFilter($filter)
    {
        $field = 'any';
        if ($this->_isNumeroProcesso($filter)) {
            $field = 'numero';
        }
        return $field;
    }

    private function _isNumeroProcesso($filter)
    {
        // Modelo: AP0712095609___/2010
        return (strlen($filter) == 20);
    }

    private function _redirectToConsulta()
    {
        $this->_helper->redirector('index');
    }

    private function _redirectToProcesso($processoId)
    {
        $this->_helper->redirector('detalhes', 'processo', 'default', array('id' => $processoId));
    }

    public function anexosAction()
    {
        $this->view->abaAtiva = 'anexos';
    }

    public function anexoResultadosAction()
    {
        $service = new Spu_Service_Processo($this->getTicket());
        $this->view->paginator = $this->_helper->paginator()->paginate(
            $service->consultarAnexos(
                $this->_helper->paginator()->getOffset(), $this->_helper->paginator()->getPageSize(), $this->_getParam('conteudo')
            )
        );

        $this->view->conteudo = $this->_getParam('conteudo');
        $this->view->abaAtiva = 'anexos';
    }

    private function _redirectToConsultaAnexos()
    {
        $this->_helper->redirector('anexos');
    }

    public function serviceAnexoResultadosAction()
    {
        
    }

}