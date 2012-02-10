<?php

class ProcessoController extends BaseController
{

    public function detalhesAction()
    {
        try {
            $idProcesso = $this->_getIdProcessoUrl();
            $processoService = new Spu_Service_Processo($this->getTicket());
            $processo = $processoService->getProcesso($idProcesso);
            $processosParalelos = $processoService->getProcessosParalelos($processo->id);
            $arquivoService = new Spu_Service_Arquivo($this->getTicket());
            $this->view->oficioMarker = $arquivoService->getOficioUuid($processo->assunto->nodeRef);
            $this->view->diarioMarker = $arquivoService->getDiarioUuid($processo->assunto->nodeRef);
            $this->view->comunicacaoInternaMarker = $arquivoService->getComunicacaoInternaUuid($processo->assunto->nodeRef);
        } catch (Exception $e) {
            echo $e->getMessage();
            exit;
            $this->setMessageForTheView('Não foi possível carregar o processo', 'error');
        }

        if (array_key_exists('etiqueta', $this->_getAllParams())) {
            $this->_redirectEtiqueta($idProcesso);
        }

        if (array_key_exists('etiqueta1', $this->_getAllParams())) {
            $this->_redirectEtiqueta($idProcesso, array('layout' => 'duplo'));
        }

        if (array_key_exists('oficio', $this->_getAllParams())) {
            $this->_helper->redirector('oficio', $this->getController(), 'default', array('id' => $idProcesso));
        }

        if (array_key_exists('diario', $this->_getAllParams())) {
            $this->_helper->redirector('diario', $this->getController(), 'default', array('id' => $idProcesso));
        }

        if (array_key_exists('comunicacao-interna', $this->_getAllParams())) {
            $this->_helper->redirector('comunicacao-interna', $this->getController(), 'default', array('id' => $idProcesso));
        }

        $this->view->processo = $processo;
        $this->view->processosParalelos = $processosParalelos;
        $this->view->processosIncorporados = $processoService->getIncorporados($idProcesso);

        $session = new Zend_Session_Namespace('ap');
        if (isset($session->updateaposentadoria)) {
            $this->view->updateaposentadoria = $session->updateaposentadoria;
        } elseif (isset($session->insertaposentadoria)) {
            $this->view->insertaposentadoria = $session->insertaposentadoria;
        }
        Zend_Session::namespaceUnset('ap');
    }

    public function enviarDadosAposentadoriaAction()
    {
        $this->ajaxNoRender();

        if ($this->isPostAjax()) {
            $res = $this->_enviarDadosAposentadoria($this->_getParam('dados'));
            die($res ? 'enviado' : 'erro');
        }
    }

    private function _enviarDadosAposentadoria($dados)
    {
        $dados = $this->_filtrarDadosAposentadoria($dados);

        if (!$dados)
            return false;

        try {
            $db_aap = new Application_Model_Aposentadoria();
            $aposentado = $db_aap->encontrar($dados['PRONTUARIO']);
            $dados['DTADMISSAO'] = $aposentado->DTADMISSAO;
            $dados['CARGO'] = $aposentado->CARGO;

            $db_aap_processo = new Application_Model_AposentadoriaProcesso();
            $db_aap_processo->inserir($dados);
        } catch (Zend_Db_Exception $e) {
            $dados['erro'] = "Exception:({$e->getCode()}; {$e->getFile()}; {$e->getMessage()})";
            $this->_gerarLog($dados);
            return false;
        }
        return true;
    }

    private function _filtrarDadosAposentadoria($dados)
    {
        $a = array();
        $processoService = new Spu_Service_Processo($this->getTicket());
        $processo = $processoService->getProcesso($dados['id']);
        $a['PRONTUARIO'] = $dados['prontuario'];
        if (!is_numeric($a['PRONTUARIO']))
            return false;

        $id = substr($processo->assunto->nodeRef, 24);
        if (!$this->_isTipoAposentadoria($id))
            return false;

        $a['STATUS'] = 'TRAMITANDO';
        $a['NMASSUNTOPROCESSO'] = strtoupper($processo->assunto->nome);
        $a['NOMETPPROCESSO'] = strtoupper($processo->assunto->tipoProcesso->nome);

        $path = explode("/", $processo->protocolo->path);
        $a['LOTACAO_ATUAL'] = $path[0] . " - " . $path[count($path) - 1];

        $data = new DateTime(implode("/", array_reverse(explode("/", $processo->data))));
        $a['DTABERTURA'] = $data->format('d/m/y');

        $a['CPF_CNPJ'] = str_replace(array('.', '-', '/'), "", $processo->manifestante->cpf);
        $a['NOMEREQUERENTE'] = strtoupper($processo->manifestante->nome);
        $a['NUMPROCESSO'] = str_replace("_", "/", $processo->nome);
        $a['PRONTUARIO'] = (int) $a['PRONTUARIO'];

        return $a;
    }

    public function encaminharAction()
    {
        try {

            $idProcesso = $this->_getIdProcessoUrl();
            $processoService = new Spu_Service_Processo($this->getTicket());
            if ($idProcesso) {
                $processo = $processoService->getProcesso($idProcesso);
            }

            $listaPrioridades = $this->_getListaPrioridades();

            $service = new Spu_Service_Protocolo($this->getTicket());
            $listaProtocolos = $service->getProtocolosRaiz();

            if ($this->getRequest()->isPost()) {
                $tramitacaoService = new Spu_Service_Tramitacao($this->getTicket());
                $tramitacaoService->tramitar($this->getRequest()->getPost());

                $tipo = new Spu_Service_TipoProcesso($this->getTicket());
                $destino[] = $tipo->getTipoProcesso($this->_getParam('destinoId_root'))->nome;
                $destino[] = $tipo->getTipoProcesso($this->_getParam('destinoId_children'))->nome;

                $session = new Zend_Session_Namespace('ap');
                $session->updateaposentadoria['id'] = $idProcesso;
                $session->updateaposentadoria['destino'] = implode(" - ", $destino);

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

    public function arquivoAction()
    {
        try {
            $arquivoHash['id'] = $this->getRequest()->getParam('id');
            $arquivoHash['nome'] = $this->getRequest()->getParam('nome');
            $arquivoService = new Spu_Service_Arquivo($this->getTicket());
            $url = $arquivoService->getArquivoDownloadUrl($arquivoHash, true, Zend_Registry::get('baseDownload'));

            $this->getResponse()->setRedirect($url);
        } catch (Exception $e) {
            $this->setMessageForTheView($e->getMessage(), 'error');
        }
    }

    public function atualizarAposentadoriaAction()
    {
        $this->ajaxNoRender();

        if ($this->isPostAjax()) {
            $ids = $this->_getParam('ids');
            $res = $this->_atualizarAposentadoria(array($ids['id']), array('LOTACAO_ATUAL' => $ids['destino']));
            die($res ? 'atualizado' : 'erro');
        }
    }

    protected function _getIdProcessoUrl()
    {
        $idProcesso = $this->getRequest()->getParam('id');
        return $idProcesso;
    }

    protected function _getListaPrioridades()
    {
        $prioridadeService = new Spu_Service_Prioridade($this->getTicket());
        $prioridades = $prioridadeService->fetchAll();
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

    protected function _redirectDetalhesProcesso($idProcesso)
    {
        $this->_helper->redirector('detalhes', $this->getController(), 'default', array('id' => $idProcesso));
    }

    protected function _redirectEtiqueta($idProcesso, array $params = array())
    {
        $this->_helper->redirector('etiqueta', $this->getController(), 'default', array('id' => $idProcesso, 'layout' => $params));
    }

    /* protected function _redirectOficio($idProcesso)
      {
      $this->_helper->redirector('oficio', $this->getController(), 'default', array('id' => $idProcesso));
      } */

    public function etiquetaAction()
    {
        $processo = new Spu_Entity_Processo();
        try {
            $idProcesso = $this->_getIdProcessoUrl();
            $processoService = new Spu_Service_Processo($this->getTicket());
            $processo = $processoService->getProcesso($idProcesso);
        } catch (Exception $e) {
            $this->setMessageForTheView('Não foi possível carregar o processo', 'error');
        }

        $this->_helper->layout()->setLayout('relatorio');

        $this->view->processo = $processo;

        if ($this->_request->getParam('layout')) {
            $this->view->layout = $this->_request->getParam('layout');
        }
    }

    public function oficioAction()
    {
        $this->_helper->layout()->disableLayout();

        $processoService = new Spu_Service_Processo($this->getTicket());
        $processo = $processoService->getProcesso($this->_getIdProcessoUrl());

        $arquivoService = new Spu_Service_Arquivo($this->getTicket());
        $arquivoString = $arquivoService->getOficioModelo($processo->assunto->nodeRef);

        try {
            $dataAtual = new Zend_Date();
            $arquivoService->substituiVariaveisEmOdt($arquivoString, array('manifestante' => $processo->manifestante->nome,
                'corpo' => $processo->corpo,
                'nr-processo' => $processo->numero,
                'data-abertura' => $processo->data,
                'observacao' => $processo->observacao,
                'tipo-processo' => $processo->tipoProcesso->nome,
                'assunto' => $processo->assunto->nome,
                'data-atual' => $dataAtual->toString('dd/MM/YYYY')));
        } catch (Exception $e) {
            print $e->getMessage();
            exit; // TODO FIXME
        }

        $this->view->arquivoString = $arquivoString;
    }

    public function diarioAction()
    {
        $this->_helper->layout()->disableLayout();

        $processoService = new Spu_Service_Processo($this->getTicket());
        $processo = $processoService->getProcesso($this->_getIdProcessoUrl());

        $arquivoService = new Spu_Service_Arquivo($this->getTicket());
        $arquivoString = $arquivoService->getDiarioModelo($processo->assunto->nodeRef);

        try {
            $dataAtual = new Zend_Date();
            $arquivoService->substituiVariaveisEmOdt($arquivoString, array('manifestante' => $processo->manifestante->nome,
                'corpo' => $processo->corpo,
                'nr-processo' => $processo->numero,
                'data-abertura' => $processo->data,
                'observacao' => $processo->observacao,
                'tipo-processo' => $processo->tipoProcesso->nome,
                'assunto' => $processo->assunto->nome,
                'data-atual' => $dataAtual->toString('dd/MM/YYYY')));
        } catch (Exception $e) {
            print $e->getMessage();
            exit; // TODO FIXME
        }

        $this->view->arquivoString = $arquivoString;
    }

    public function comunicacaoInternaAction()
    {
        $this->_helper->layout()->disableLayout();

        $processoService = new Spu_Service_Processo($this->getTicket());
        $processo = $processoService->getProcesso($this->_getIdProcessoUrl());

        $arquivoService = new Spu_Service_Arquivo($this->getTicket());
        $arquivoString = $arquivoService->getComunicacaoInternaModelo($processo->assunto->nodeRef);

        try {
            $dataAtual = new Zend_Date();
            $arquivoService->substituiVariaveisEmOdt($arquivoString, array('manifestante' => $processo->manifestante->nome,
                'corpo' => $processo->corpo,
                'nr-processo' => $processo->numero,
                'data-abertura' => $processo->data,
                'observacao' => $processo->observacao,
                'tipo-processo' => $processo->tipoProcesso->nome,
                'assunto' => $processo->assunto->nome,
                'data-atual' => $dataAtual->toString('dd/MM/YYYY')));
        } catch (Exception $e) {
            print $e->getMessage();
            exit; // TODO FIXME
        }

        $this->view->arquivoString = $arquivoString;
    }

}

