<?php
/**
 * Kunena Component
 * @package Kunena.Administrator
 * @subpackage Models
 *
 * @copyright (C) 2008 - 2012 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

jimport ( 'joomla.application.component.model' );
jimport( 'joomla.html.pagination' );

/**
 * Users Model for Kunena
 *
 * @since 2.0
 */
class KunenaAdminModelUsers extends KunenaModel {
	protected $__state_set = false;

	/**
	 * Method to auto-populate the model state.
	 *
	 * @return	void
	 * @since	1.6
	 */
	protected function populateState() {
		// List state information
		$value = $this->getUserStateFromRequest ( "com_kunena.admin.users.list.limit", 'limit', $this->app->getCfg ( 'list_limit' ), 'int' );
		$this->setState ( 'list.limit', $value );

		$value = $this->getUserStateFromRequest ( 'com_kunena.admin.users.list.ordering', 'filter_order', 'username', 'cmd' );
		$this->setState ( 'list.ordering', $value );

		$value = $this->getUserStateFromRequest ( "com_kunena.admin.users.list.start", 'limitstart', 0, 'int' );
		$this->setState ( 'list.start', $value );

		$value = $this->getUserStateFromRequest ( 'com_kunena.admin.users.list.direction', 'filter_order_Dir', 'asc', 'word' );
		if ($value != 'asc')
			$value = 'desc';
		$this->setState ( 'list.direction', $value );

		$value = $this->getUserStateFromRequest ( 'com_kunena.admin.users.list.search', 'search', '', 'string' );
		$this->setState ( 'list.search', $value );
	}

	public function getUsers() {
		$db = JFactory::getDBO ();

		$where = '';
		if ( $this->getState('list.search') ) {
			$where = ' u.username LIKE '.$db->Quote( '%'.$db->escape( $this->getState ( 'list.search' ), true ).'%', false ).' OR u.email LIKE '.$db->Quote( '%'.$db->escape( $this->getState ( 'list.search' ), true ).'%', false ).' OR u.name LIKE '.$db->Quote( '%'.$db->escape( $this->getState ( 'list.search' ), true ).'%', false );
		}

		$query = $db->getQuery(true);
		$query->select(array('COUNT(*)'));
		$query->from('#__kunena_users AS ku');
		$query->join('INNER', '#__users AS u ON (ku.userid=u.id)');
		if ( !empty($where) ) $query->where($where);

		$db->setQuery($query);
		$total = $db->loadResult ();
		KunenaError::checkDatabaseError();

		$order = '';
		if ($this->getState('list.ordering') == 'id') {
			$order = ' u.id '. $this->getState('list.direction');
		} else if ($this->getState('list.ordering') == 'username') {
			$order = ' u.username '. $this->getState('list.direction');
		} else if ($this->getState('list.ordering') == 'name') {
			$order = ' u.name '. $this->getState('list.direction');
		} else if ($this->getState('list.ordering') == 'moderator') {
			$order = ' ku.moderator '. $this->getState('list.direction');
		}

		$this->setState ( 'list.total', $total );

		$query = $db->getQuery(true);
		$query->select(array('u.id', 'u.username', 'u.name', 'ku.moderator'));
		$query->from('#__kunena_users AS ku');
		$query->join('INNER', '#__users AS u ON (ku.userid=u.id)');
		if ( !empty($where) ) $query->where($where);
		if ( !empty($order) ) $query->order($order);

		$db->setQuery ( $query, $this->getState ( 'list.start'), $this->getState ( 'list.limit') );

		$users = $db->loadObjectList ();
		if (KunenaError::checkDatabaseError()) return;

		return $users;
	}

	public function getUser() {
		$userid = $this->app->getUserState ( 'kunena.user.userid');

		$user = KunenaUserHelper::get($userid);

		return $user;
	}

	public function getSubscriptions() {
		$db = JFactory::getDBO ();
		$userid = $this->app->getUserState ( 'kunena.user.userid');

		$query = $db->getQuery(true);
		$query->select(array('topic_id AS thread'));
		$query->from('#__kunena_user_topics');
		$query->where('user_id=\''.$userid.'\' AND subscribed=1');
		$db->setQuery ( $query );
		$subslist = $db->loadObjectList ();
		if (KunenaError::checkDatabaseError()) return;

		return $subslist;
	}

	public function getCatsubcriptions() {
		$db = JFactory::getDBO ();
		$userid = $this->app->getUserState ( 'kunena.user.userid');

		$query = $db->getQuery(true);
		$query->select(array('category_id'));
		$query->from('#__kunena_user_categories');
		$query->where('user_id=\''.$userid.'\'');
		$db->setQuery ( $query );
		$subscatslist = $db->loadObjectList ();
		if (KunenaError::checkDatabaseError()) return;

		return $subscatslist;
	}

	public function getIPlist() {
		$db = JFactory::getDBO ();
		$userid = $this->app->getUserState ( 'kunena.user.userid');

		$query = $db->getQuery(true);
		$query->select(array('ip'));
		$query->from('#__kunena_messages');
		$query->where('userid=\''.$userid.'\'');
		$query->qroup('ip');
		$db->setQuery ( $query );
		$iplist = implode("','", $db->loadColumn ());
		if (KunenaError::checkDatabaseError()) return;

		$list = array();
		if ($iplist) {
			$iplist = "'{$iplist}'";
			$query = $db->getQuery(true);
			$query->select(array('m.ip', 'm.userid', 'u.username', 'COUNT(*) as mescnt'));
			$query->from('#__kunena_messages AS m');
			$query->join('INNER', '#__users AS u ON (m.userid=u.id)');
			$query->where('m.ip IN ('.$iplist.')');
			$query->group('m.userid,m.ip');

			$db->setQuery ( $query );
			$list = $db->loadObjectlist ();
		if (KunenaError::checkDatabaseError()) return;
		}
		$useripslist = array();
		foreach ($list as $item) {
			$useripslist[$item->ip][] = $item;
		}

		return $useripslist;
	}

	public function getListmodcats() {
		$db = JFactory::getDBO ();
		$user = $this->getUser();

		$modCatList = array_keys(KunenaAccess::getInstance()->getModeratorStatus($user));
		if (empty($modCatList)) $modCatList[] = 0;

		$categoryList = array(JHtml::_('select.option', 0, JText::_('COM_KUNENA_GLOBAL_MODERATOR')));
		$params = array (
			'sections' => false,
			'action' => 'read');
		$modCats = JHtml::_('kunenaforum.categorylist', 'catid[]', 0, $categoryList, $params, 'class="inputbox" multiple="multiple" size="15"', 'value', 'text', $modCatList, 'kforums');

		return $modCats;
	}

	public function getListuserranks() {
		$db = JFactory::getDBO ();
		$user = $this->getUser();
		//grab all special ranks
		$query = $db->getQuery(true);
		$query->select(array('r.*'));
		$query->from('#__kunena_ranks AS r');
		$query->where('rank_special = \'1\'');
		$db->setQuery ( $query );

		$specialRanks = $db->loadObjectList ();
		if (KunenaError::checkDatabaseError()) return;

		$yesnoRank [] = JHtml::_ ( 'select.option', '0', JText::_('COM_KUNENA_RANK_NO_ASSIGNED') );
		foreach ( $specialRanks as $ranks ) {
			$yesnoRank [] = JHtml::_ ( 'select.option', $ranks->rank_id, $ranks->rank_title );
		}
		//build special ranks select list
		$selectRank = JHtml::_ ( 'select.genericlist', $yesnoRank, 'newrank', 'class="inputbox" size="5"', 'value', 'text', $user->rank );
		return $selectRank;
	}

	public function getMovecatslist() {
		return JHtml::_('kunenaforum.categorylist', 'catid', 0, array(), array(), 'class="inputbox"', 'value', 'text');
	}

	public function getMoveuser() {
		$db = JFactory::getDBO ();

		$userids = (array) $this->app->getUserState ( 'kunena.usermove.userids');
		if (!$userids) return $userids;

		$userids = implode(',', $userids);

		$query = $db->getQuery(true);
		$query->select(array('id', 'username'));
		$query->from('#__users');
		$query->where('id IN('.$userids.')');
		$db->setQuery ( $query );

		$userids = $db->loadObjectList ();
		if (KunenaError::checkDatabaseError()) return;

		return $userids;
	}

	public function getAdminNavigation() {
		$navigation = new JPagination ($this->getState ( 'list.total'), $this->getState ( 'list.start'), $this->getState ( 'list.limit') );
		return $navigation;
	}
}
