<?php

/**
 * Classe para acessar os serviços de Protocolo do SPU
 *
 * @author Bruno Cavalcante <brunofcavalcante@gmail.com>
 * @package SPU
 * @see Spu_Service_Abstract
 */
class Spu_Service_Protocolo extends Spu_Service_Abstract
{

    /**
     * URL Base dos serviços (a ser acrescentada à url dos serviços do Alfresco)
     * @var string
     */
    private $_protocoloBaseUrl = 'spu/protocolo';

    /**
     * Retorna o protocolo pelo seu id
     *
     * @param string $nodeUuid
     * @return Spu_Entity_Protocolo
     */
    public function getProtocolo($nodeUuid)
    {
        $url = $this->getBaseUrl() . "/" . $this->_protocoloBaseUrl . "/get/$nodeUuid";
        $result = $this->_doAuthenticatedGetRequest($url);

        return $this->loadFromHash(array_pop($result['Protocolo']));
    }

    /**
     * Retorna todos os protocolos do usuário logado
     *
     * @return Spu_Entity_Protocolo[]
     */
    public function getProtocolos()
    {
        $url = $this->getBaseUrl() . "/" . $this->_protocoloBaseUrl . "/listar";

        $result = $this->_doAuthenticatedGetRequest($url);

        return $this->_loadManyFromHash($result['Protocolos']);
    }

    /**
     * Altera os dados de um protocolo
     *
     * @param string $id
     * @param array $postData array com os dados do protocolo
     * @return Spu_Entity_Protocolo
     */
    public function alterar($id, $postData)
    {
        $url = $this->getBaseUrl() . "/" . $this->_protocoloBaseUrl . "/editar/$id";
        $result = $this->_doAuthenticatedPostRequest($url, $postData);

        return $this->loadFromHash($result['Protocolo'][0]);
    }

    /**
     * Retorna todos os protocolos do SPU, paginados
     *
     * @param integer $offset
     * @param integer $pageSize
     * @param string $filter
     * @return Spu_Entity_Protocolo[]
     */
    public function getTodosProtocolosPaginado($offset = 0, $pageSize = 20, $filter = null)
    {
        $filter = urlencode($filter);
        $url = "{$this->getBaseUrl()}/{$this->_protocoloBaseUrl}/listarTodosPaginado/$offset/$pageSize/?s=$filter";

        $result = $this->_doAuthenticatedGetRequest($url);

        return $this->_loadManyFromHash($result['Protocolos'][0]);
    }

    /**
     * Retorna os protocolos (paginados) que podem ser destino, à partir do protocolo de origem e o tipo de processo
     *
     * @param string $protocoloOrigemId
     * @param string $tipoProcessoId
     * @param string $filter
     * @param integer $offset
     * @param integer $pageSize
     * @return Spu_Entity_Protocolo[]
     */
    public function getProtocolosDestino($protocoloOrigemId, $tipoProcessoId, $filter, $offset, $pageSize)
    {
        $url = "{$this->getBaseUrl()}/{$this->_protocoloBaseUrl}/listardestinos?protocoloRaizId={$protocoloOrigemId}"
                . "&tipoProcessoId={$tipoProcessoId}&filter={$filter}&offset={$offset}&pageSize={$pageSize}";

        $result = $this->_doAuthenticatedGetRequest($url);

        return $this->_loadManyFromHash($result['Protocolos'][0]);
    }

    /**
     * Carrega o protocolo através de um hash
     *
     * @param array $hash
     * @return Spu_Entity_Protocolo
     */
    public function loadFromHash($hash)
    {
        $protocolo = new Spu_Entity_Protocolo();

        $protocolo->setNodeRef($this->_getHashValue($hash, 'noderef'));
        $protocolo->setNome($this->_getHashValue($hash, 'nome'));
        $protocolo->setParent($this->_loadParentFromHash($this->_getHashValue($hash, 'parentId')));
        $protocolo->setDescricao($this->_getHashValue($hash, 'descricao'));
        $protocolo->setPath($this->_getHashValue($hash, 'path'));

        return $protocolo;
    }

    /**
     * Carrega somente os dados básicos do protocolo pai
     *
     * @param string $id
     * @return Spu_Entity_Protocolo
     */
    protected function _loadParentFromHash($id)
    {
        $parent = new Spu_Entity_Protocolo();
        $parent->setNodeRef($id);

        return $parent;
    }

    /**
     * Carrega vários protocolos através de um hash
     *
     * @param array $hash
     * @return Spu_Entity_Protocolo[]
     */
    public function _loadManyFromHash($hash)
    {
        if ($hash) {
            $listaOrgaoSetor = array();

            foreach ($hash as $value) {
                $listaOrgaoSetor[substr($value[key($value)]["noderef"], 24)] = $value;
            }

            foreach ($listaOrgaoSetor as $hashProtocolo) {
                $hashProtocolo = array_pop($hashProtocolo);
                $protocolos[] = $this->loadFromHash($hashProtocolo);
            }
        }

        return $protocolos;
    }

    /**
     * Retorna todos os protocolos do usuário logado
     *
     * @return Spu_Entity_Protocolo[]
     */
    public function getProtocolosRaiz($protocoloOrigemId = null, $tipoProcessoId = null)
    {
        $url = "{$this->getBaseUrl()}/{$this->_protocoloBaseUrl}/listar-raizes";
        $url .= $this->_getParametrosAdicionarListagemProtocolos($protocoloOrigemId, $tipoProcessoId);

        $name = $this->getNameForMethod('getProtocolosRaiz', $protocoloOrigemId . $tipoProcessoId);
        if (($result = $this->getCache()->load($name)) === false) {

            $result = $this->_doAuthenticatedGetRequest($url);

            $this->getCache()->save($result, $name);
        }

        return $this->_loadManyFromHash($result['Protocolos']);
    }

    protected function _getParametrosAdicionarListagemProtocolos($protocoloOrigemId = null, $tipoProcessoId = null)
    {
        $url = '';
        if ($protocoloOrigemId || $tipoProcessoId) {
            $url .= "?";
            if ($protocoloOrigemId) {
                $url .= "protocolo-origem-id=$protocoloOrigemId";
                if ($tipoProcessoId) {
                    $url .= "&";
                }
            }

            if ($tipoProcessoId) {
                $url .= "tipo-processo-id=$tipoProcessoId";
            }
        }

        return $url;
    }

    /**
     * Retorna todos os protocolos do usuário logado
     *
     * @return Spu_Entity_Protocolo[]
     */
    public function getProtocolosFilhos($parentId, $protocoloOrigemId = null, $tipoProcessoId = null)
    {
        $url = "{$this->getBaseUrl()}/{$this->_protocoloBaseUrl}/listar-filhos/$parentId";
        $url .= $this->_getParametrosAdicionarListagemProtocolos($protocoloOrigemId, $tipoProcessoId);

        $idNome = $parentId . $this->getNameProtocolo($protocoloOrigemId);
        $name = $this->getNameForMethod('getProtocolosFilhos', $idNome);
        if (($result = $this->getCache()->load($name)) === false) {

            $result = $this->_doAuthenticatedGetRequest($url);

            $this->getCache()->save($result, $name);
        }

        return $this->_loadManyFromHash($result['Protocolos']);
    }

    public function getNameProtocolo($protocoloOrigemId)
    {
        $pathTotal = $this->getProtocolo($protocoloOrigemId)->path;
        $pathTotal = explode("/", $pathTotal);
        $nameProtocolo = (null == $pathTotal[0] ? $pathTotal[1] : $pathTotal[0]);

        return $nameProtocolo;
    }

}