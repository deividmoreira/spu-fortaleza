<?php
/**
 * BaseController: Controlador base para as telas do SPU
 * @author bruno
 */
require_once 'BaseAuthenticatedController.php';
abstract class BaseController extends BaseAuthenticatedController
{
    public function getController()
    {
        return $this->getRequest()->getParam('controller');
    }
    
    public function getAction()
    {
        return $this->getRequest()->getParam('action');
    }
    
    /**
     * Passa a mensagem para o helper exibí-la
     * @param $texto
     * @param $tipo
     */
    public function setMessageForTheView($texto, $tipo = 'info')
    {
        $this->view->message()->setTexto($texto);
        $this->view->message()->setTipo($tipo);
    }
    
    public function setMessage($texto)
    {
        $this->_helper->flashMessenger($texto);
    }
    
    public function setSuccessMessage($texto)
    {
        $this->_helper->flashMessenger(array('success' => $texto));
    }
    
    public function setErrorMessage($texto) 
    {
        $this->_helper->flashMessenger(array('error' => $texto));
    }
    
    public function init()
    {
        $this->view->controller = $this->getController();
        $this->view->action = $this->getAction();
        $authInstance = Zend_Auth::getInstance()->getIdentity();
        $this->view->pessoa = $authInstance['user'];
        
        $this->_setVersaoSistema();
        
        $this->setMessageFromFlashMessenger();
        $this->setMessageFromUrl();
        
        parent::init();
    }
    
    protected function _setVersaoSistema()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $aConfig = $bootstrap->getOptions();
        
        $versao = (isset($aConfig['spu']) AND isset($aConfig['spu']['versao'])) ? $aConfig['spu']['versao'] : '0.3';
        $this->view->versao = $versao;
    }

    private function setMessageFromFlashMessenger()
    {
        if ($this->_helper->flashMessenger->getMessages()) {
            $messages = $this->_helper->flashMessenger->getMessages();
            $message = $messages[0];
            $type = key($message);
            $this->setMessageForTheView($message[$type], $type);
        }
    }
    
    private function setMessageFromUrl()
    {
        if ($this->getRequest()->getParam('method')) {
            // Parametro da URL
            $param = strtoupper($this->getRequest()->getParam('method'));
            
            //Procura na classe de mensagens
            $constante = constant('Mensagem::' . $param);
            $mensagem  = ($constante) ? $constante : $param;
            
            $this->setMessageForTheView($mensagem, 'success');
        }
    }
    
    protected function _uploadFilePathConverter($fileName, $fileTmpName) {
        $uploadFolder = dirname($fileTmpName);

        $tmpFilePath = $uploadFolder . "/" . basename($fileTmpName);
        $newFilePath = $uploadFolder . "/" . basename($fileName);

        rename($tmpFilePath, $newFilePath);

        return "@" . $newFilePath;
    }
}
