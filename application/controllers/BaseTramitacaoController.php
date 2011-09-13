<?php
class BaseTramitacaoController extends BaseController
{
    public function init()
    {
        parent::init();
        $this->view->abaAtiva = $this->getController();    
    }
    
    protected function _redirectEmAnalise()
    {
        $this->_helper->redirector('index', 'analise', 'default');
    }
    
    protected function _redirectEntrada()
    {
        $this->_helper->redirector('index', 'entrada', 'default');
    }
    
    protected function _redirectArquivo()
    {
        $this->_helper->redirector('index', 'arquivo', 'default');
    }
    
    protected function _redirectEncaminhar()
    {
        $this->_helper->redirector('index', 'encaminhar', 'default');
    }
    
    protected function _getListaCarregadaProcessos($listaComIdsProcessos)
    {
        $processos = array();
        foreach ($listaComIdsProcessos as $processoId) {
            $processoService = new Spu_Service_Processo($this->getTicket());
            $processos[] = $processoService->getProcesso($processoId);
        }
        
        return $processos;
    }
    
    public function pesquisarAction()
    {
    	if ($this->getRequest()->isPost()) {
    		$this->_helper->redirector(null, null, null, array('q' => urlencode($_POST['q'])));
    	}
    }
}