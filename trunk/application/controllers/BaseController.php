<?php

/**
 * @see BaseAuthenticatedController
 */
require_once 'BaseAuthenticatedController.php';

/**
 * BaseController: Controlador base para as telas do SPU
 * @author Bruno Cavalcante <brunofcavalcante@gmail.com>
 * @package SPU
 * @see BaseAuthenticatedController
 */
abstract class BaseController extends BaseAuthenticatedController
{

    /**
     * Alias para retornar controller corrent
     * 
     * @return string
     */
    public function getController()
    {
        return $this->getRequest()->getParam('controller');
    }

    /**
     * Alias para retornar a action corrent
     *
     * @return string
     */
    public function getAction()
    {
        return $this->getRequest()->getParam('action');
    }

    /**
     * Passa a mensagem para o helper exibí-la
     * 
     * @param $texto
     * @param $tipo
     * @see Zend_View_Helper_Message
     */
    public function setMessageForTheView($texto, $tipo = 'notice')
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

        $this->_setMessageFromFlashMessenger();

        parent::init();
    }

    protected function _setVersaoSistema()
    {
        $bootstrap = $this->getInvokeArg('bootstrap');
        $aConfig = $bootstrap->getOptions();

        $versao = (isset($aConfig['spu']) AND isset($aConfig['spu']['versao'])) ? $aConfig['spu']['versao'] : '0.3';
        $this->view->versao = $versao;
    }

    private function _setMessageFromFlashMessenger()
    {
        if ($this->_helper->flashMessenger->getMessages()) {
            $messages = $this->_helper->flashMessenger->getMessages();
            $message = $messages[0];
            $type = key($message);
            $this->setMessageForTheView($message[$type], $type);
        }
    }

    protected function _uploadFilePathConverter($fileName, $fileTmpName)
    {
        $uploadFolder = dirname($fileTmpName);

        $tmpFilePath = $uploadFolder . "/" . basename($fileTmpName);
        $newFilePath = $uploadFolder . "/" . basename($fileName);

        rename($tmpFilePath, $newFilePath);

        return "@" . $newFilePath;
    }
    
    protected function _gerarLog(array $array)
    {
        $stream = @fopen('../data/logs/aposentadoria.txt', 'a', false);
        
        if (!$stream) {
            throw new Exception('Failed to open stream');
        }
        
        $logger = new Zend_Log(new Zend_Log_Writer_Stream($stream));
        $logger->addPriority('APOSENTADORIA', 10)
               ->log(implode(" | ", $array), 10);
    }
    
    protected function _isTipoAposentadoria($id = null)
    {
        $a = array();
        $apos = Zend_Registry::get('aposentadorias');
        foreach ($apos as $value)
            $a[] = $value;

        return in_array($id, $a);
    }
    
}
