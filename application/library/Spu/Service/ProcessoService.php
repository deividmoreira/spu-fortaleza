<?php
require_once('BaseService.php');
Loader::loadEntity('Processo');
Loader::loadEntity('Folhas');
Loader::loadEntity('Volume');
Loader::loadService('TipoProcessoService');
Loader::loadService('PrioridadeService');
Loader::loadService('StatusService');
Loader::loadService('ProtocoloService');
Loader::loadService('TipoManifestanteService');
Loader::loadService('ArquivamentoService');
Loader::loadService('MovimentacaoService');
Loader::loadService('AssuntoService');
class ProcessoService extends BaseService
{
    protected $_processoBaseUrl = 'spu/processo';
    protected $_processoTicketUrl = 'ticket';
    
    public function getCaixaAnaliseIncorporacao($processo)
    {
        $url = $this->getBaseUrl() . "/" 
                                    . $this->_processoBaseUrl 
                                    . "/incorporacaocaixaanalise" 
                                    . "/{$processo->manifestante->cpf}" 
                                    . "/{$processo->id}" 
                                    . "/{$processo->assunto->id}";
        
        return $this->_loadManyFromHash($this->_getProcessosFromUrl($url));
    }
    
    protected function _getProcessosFromUrl($url)
    {
        $result = $this->_getResultFromUrl($url);
        
        return $result['Processos'][0];
    }
    
    protected function _getResultFromUrl($url)
    {
        $result = $this->_doAuthenticatedGetRequest($url);
        if ($this->isAlfrescoError($result)) {
            throw new Exception($this->getAlfrescoErrorMessage($result));
        }
        
        return $result;
    }
    
    public function abrirProcesso($postData)
    {
        $url = $this->getBaseUrl() . "/" . $this->_processoBaseUrl . "/abrir";
        
        $result = $this->_doAuthenticatedPostRequest($url, $postData);
        if ($this->isAlfrescoError($result)) {
            throw new Exception($this->getAlfrescoErrorMessage($result));
        }
        
        $processo = $this->loadFromHash(array_pop(array_pop($result['Processo'][0])));
        return $this->_getProcessoDetalhado($processo);
    }
    
    protected function _getProcessoDetalhado($processo) {
    	$arquivoService = new ArquivoService($this->getTicket());
        $processo->setRespostasFormulario($arquivoService->getRespostasFormulario($processo->id));
        
        $assuntoService = new AssuntoService($this->getTicket());
        $processo->setAssunto($assuntoService->getAssunto($processo->assunto->id));
        
        $tipoProcessoService = new TipoProcessoService($this->getTicket());
        $processo->setTipoProcesso($tipoProcessoService->getTipoProcesso($processo->tipoProcesso->id));
        
        $processo->setArquivos($arquivoService->getArquivos($processo->id));
        
        $processo->setMovimentacoes($this->getHistorico($processo->id));
        
        return $processo;
    }
    
    public function getProcesso($nodeUuid)
    {
        $url = $this->getBaseUrl() . "/" . $this->_processoBaseUrl . "/get/$nodeUuid";
        
        $result = $this->_doAuthenticatedGetRequest($url);
        
        $processoHash = array_pop(array_pop($result['Processo'][0])); 
        
        return $this->_getProcessoDetalhado($this->loadFromHash($processoHash));
    }
    
    public function getHistorico($nodeUuid)
    {
        $url = $this->getBaseUrl() . "/" . $this->_processoBaseUrl . "/historico/get/$nodeUuid";
        
        $result = $this->_doAuthenticatedGetRequest($url);
        if ($this->isAlfrescoError($result)) {
            throw new Exception($this->getAlfrescoErrorMessage($result));
        }
        
        $hashProcesso = array_pop(array_pop($result['Processo'][0]));
        
        $hashMovimentacao = $this->_getHashValue($hashProcesso, 'movimentacoes');
        
        return $this->_loadMovimentacoesFromHash($hashMovimentacao);
    }
    
    public function incorporar($data)
    {
        $url = $this->getBaseUrl() . "/" . $this->_processoBaseUrl . "/incorporar";
        
        $result = $this->_doAuthenticatedPostRequest($url, $data);
        if ($this->isAlfrescoError($result)) {
            throw new Exception($this->getAlfrescoErrorMessage($result));
        }
        
        return $result;
    }
    
    public function consultar($postData)
    {
        $url = $this->getBaseUrl() . "/" . $this->_processoBaseUrl . "/consultar";
        
        $result = $this->_doAuthenticatedPostRequest($url, $postData);
        if ($this->isAlfrescoError($result)) {
            throw new Exception($this->getAlfrescoErrorMessage($result));
        }
        
        return $this->_loadManyFromHash($result['Processos'][0]);
    }
    
    public function getProcessosParalelos($processoId)
    {
        $url = $this->getBaseUrl() . "/" . $this->_processoBaseUrl . "/paralelos/$processoId";
        
        return $this->_getProcessosFromUrl($url);
    }
    
    public function loadFromHash($hash)
    {
    	$processo = new Processo();
        $processo->setNodeRef($this->_getHashValue($hash, 'noderef'));
        $processo->setNome($this->_getHashValue($hash, 'nome'));
        $processo->setCorpo($this->_getHashValue($hash, 'corpo'));
        $processo->setData($this->_getHashValue($hash, 'data'));
        $processo->setPrioridade($this->_loadPrioridadeFromHash($this->_getHashValue($hash, 'prioridade')));
        $processo->setStatus($this->_loadStatusFromHash($this->_getHashValue($hash, 'status')));
        $processo->setObservacao($this->_getHashValue($hash, 'observacao'));
        $processo->setNumeroOrigem($this->_getHashValue($hash, 'numeroOrigem'));
        $processo->setProtocolo($this->_loadProtocoloFromHash($this->_getHashValue($hash, 'localAtual')));
        $processo->setTipoProcesso($this->_loadTipoProcessoFromHash($this->_getHashValue($hash, 'tipoProcesso')));
        $processo->setProprietario($this->_loadProprietarioFromHash($this->_getHashValue($hash, 'proprietario')));
        $processo->setAssunto($this->_loadAssuntoFromHash($this->_getHashValue($hash, 'assunto')));
        $processo->setManifestante($this->_loadManifestanteFromHash($this->_getHashValue($hash, 'manifestante')));
        $processo->setTipoManifestante($this->_loadTipoManifestanteFromHash($this->_getHashValue($hash,
                                                                                               'tipoManifestante')));
        $processo->setArquivamento($this->_loadArquivamentoFromHash($this->_getHashValue($hash, 'arquivamento')));
        $processo->setMovimentacoes($this->_loadMovimentacoesFromHash($this->_getHashValue($hash, 
                                                                                         'ultimaMovimentacao')));
        if (!empty($hash['folhas'])) {
            $processo->setFolhas($this->_loadFolhasFromHash($hash['folhas']));
        }
        
        return $processo;
    }
    
    protected function _loadFolhasFromHash($hash)
    {
        $folhas = new Folhas();
        $folhas->setQuantidade($hash['quantidade']);
        
        $volumesObjectArray = array();
        foreach ($hash['volumes'] as $volumeHash) {
            $volume = new Volume();
            $volume->setNome($volumeHash['nome']);
            $volume->setInicio($volumeHash['inicio']);
            $volume->setFim($volumeHash['fim']);
            $volume->setObservacao($volumeHash['observacao']);
            $volumesObjectArray[] = $volume;
        }
        
        $folhas->setVolumes($volumesObjectArray);
        return $folhas;
    }
    
    protected function _loadPrioridadeFromHash($hash)
    {
        $hash = array_pop($hash);
        $prioridadeService = new PrioridadeService($this->getTicket());
        $prioridade = $prioridadeService->loadFromHash($hash);
        
        return $prioridade;
    }
    
    protected function _loadStatusFromHash($hash)
    {
        $hash = array_pop($hash);
        $statusService = new StatusService($this->getTicket());
        $status = $statusService->loadFromHash($hash);
        
        return $status;
    }
    
    protected function _loadProtocoloFromHash($hash)
    {
        $hash = array_pop($hash);
        $protocoloService = new ProtocoloService($this->getTicket());
        $protocolo = $protocoloService->loadFromHash($hash);
        
        return $protocolo;
    }
    
    protected function _loadTipoProcessoFromHash($hash)
    {
        $hash = array_pop($hash);
        $tipoProcesso = new TipoProcesso($this->getTicket());
        $tipoProcesso->setNodeRef($this->_getHashValue($hash, 'noderef'));
        $tipoProcesso->setNome($this->_getHashValue($hash, 'nome'));
        
        return $tipoProcesso;
    }
    
    protected function _loadProprietarioFromHash($hash)
    {
        return $this->_loadProtocoloFromHash($hash);
    }
    
    protected function _loadAssuntoFromHash($hash)
    {
        $hash = array_pop($hash);
        $assuntoService = new AssuntoService($this->getTicket());
        $assunto = $assuntoService->loadFromHash($hash);
        
        return $assunto;
    }
    
    protected function _loadManifestanteFromHash($hash)
    {
        $hash = array_pop($hash);
        $manifestanteService = new ManifestanteService($this->getTicket());
        $manifestante = $manifestanteService->loadFromHash($hash);
        
        return $manifestante;
    }
    
    protected function _loadTipoManifestanteFromHash($hash)
    {
        $hash = array_pop($hash);
        $tipoManifestanteService = new TipoManifestanteService($this->getTicket());
        $tipoManifestante = $tipoManifestanteService->loadFromHash($hash);
        
        return $tipoManifestante;
    }
    
    protected function _loadArquivamentoFromHash($hash)
    {
        $hash = array_pop($hash);
        $arquivamentoService = new ArquivamentoService();
        $arquivamento = $arquivamentoService->loadFromHash($hash);
        
        return $arquivamento;
    }
    
    protected function _loadMovimentacoesFromHash($hash)
    {
        $movimentacaoService = new MovimentacaoService();
        return $movimentacaoService->loadManyFromHash($hash);
    }
    
    protected function _loadManyFromHash($hashProcessos)
    {
        $processos = array();
        foreach ($hashProcessos as $hashProcesso) {
            $hashDadosProcesso = array_pop($hashProcesso); 
            $processos[] = $this->loadFromHash($hashDadosProcesso);
        }
        
        return $processos;
    }
}