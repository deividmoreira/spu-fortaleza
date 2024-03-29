<?php
/**
 * Renderiza um formulário do XSDForms
 * @author bruno <brunofcavalcante@gmail.com>
 * @package SPU
 */
class Zend_View_Helper_XsdForms extends Zend_View_Helper_Abstract
{
    protected $_idFormulario;
    protected $_valorDestino;
    protected $_idContainer;
    protected $_addScript;
    protected $_html;
    
    public function xsdForms($idFormulario, $valorDestino, $idContainer, $addScript = null)
    {
        $this->_idFormulario = $idFormulario;
        $this->_valorDestino = $valorDestino;
        $this->_idContainer = $idContainer;
        $this->_addScript = $addScript;
        
        $this->_render();
    }
    
    private function _render()
    {
        $script = "jQuery(document).ready(function() {
                        var xsdUrl = \"" . $this->_getUrl() . "\"
                        generateForm(xsdUrl,'xsdform_container');
                        jQuery('#" . $this->_idContainer . "').submit(function() {
                            try {
                                generateXml(xsdUrl, this." . $this->_valorDestino . ");
                            } catch (e) {
                                alert(e);
                                return false;
                            }
                            return true;
                        });
                        generateXsdFormUI();
                        
                        $('.xsdForm__mandatory').each(function() {
				       		$('label[for=' + $(this).attr('name') + ']').addClass('required')
				        });
                    });";
        
        if ($this->_addScript)
            $script .= $this->_addScript;
        
        $this->view->headScript()->appendScript($script, 'text/javascript');
    }
    
    private function _getUrl()
    {
        return $this->_getBaseUrl() . '/formulario/content/id/' . $this->_idFormulario;
    }
    
    private function _getBaseUrl()
    {
        return $this->view->baseUrl();
    }
}