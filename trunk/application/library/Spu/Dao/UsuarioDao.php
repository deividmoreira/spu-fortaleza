<?php
require_once('BaseDao.php');
Loader::loadAlfrescoApiClass('AlfrescoPeople');
class UsuarioDao extends BaseDao
{
	public function fetchAll($filter = null)
	{
		$api = $this->_getApi();
		return $api->listPeople(filter);
	}
	
	public function find($username)
	{
		$api = $this->_getApi();
		return $api->getPerson($username);
	}
	
	public function fetchGroups($username)
	{
		$api = $this->_getApi();
		return $api->getGroups($username);
	}
	
	protected function _getApi()
	{
		$api = new AlfrescoPeople(self::ALFRESCO_URL, $this->getTicket());
		return $api;
	}
}