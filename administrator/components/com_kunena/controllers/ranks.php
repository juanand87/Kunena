<?php
/**
 * Kunena Component
 * @package Kunena.Administrator
 * @subpackage Controllers
 *
 * @copyright (C) 2008 - 2012 Kunena Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL
 * @link http://www.kunena.org
 **/
defined ( '_JEXEC' ) or die ();

/**
 * Kunena Ranks Controller
 *
 * @since 2.0
 */
class KunenaAdminControllerRanks extends KunenaController {
	protected $baseurl = null;

	public function __construct($config = array()) {
		parent::__construct($config);
		$this->baseurl = 'administrator/index.php?option=com_kunena&view=ranks';
	}

	function add() {
		if (! JSession::checkToken('post')) {
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_ERROR_TOKEN' ), 'error' );
			$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
		}

		$this->setRedirect(KunenaRoute::_($this->baseurl."&layout=add", false));
	}

	function edit() {
		if (! JSession::checkToken('post')) {
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_ERROR_TOKEN' ), 'error' );
			$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
		}

		$cid = JRequest::getVar ( 'cid', array (), 'post', 'array' );
		$id = array_shift($cid);
		if (!$id) {
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_A_NO_RANKS_SELECTED' ), 'notice' );
			$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
		} else {
			$this->setRedirect(KunenaRoute::_($this->baseurl."&layout=add&id={$id}", false));
		}
	}

	function save() {
		$db = JFactory::getDBO ();

		if (!JSession::checkToken('post')) {
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_ERROR_TOKEN' ), 'error' );
			$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
			return;
		}

		$rank_title = JRequest::getVar ( 'rank_title' );
		$rank_image = JRequest::getVar ( 'rank_image' );
		$rank_special = JRequest::getVar ( 'rank_special' );
		$rank_min = JRequest::getVar ( 'rank_min' );
		$rankid = JRequest::getInt( 'rankid', 0 );



		if ( !$rankid ) {
			$query = $db->getQuery(true);

			// Insert columns.
			$columns = array('rank_title', 'rank_image', 'rank_special', 'rank_min');

			// Insert values.
			$values = array($db->quote($rank_title), $db->quote($rank_image), $db->quote($rank_special), $db->quote($rank_min));

			// Prepare the insert query.
			$query
			->insert($db->quoteName('#__kunena_ranks'))
			->columns($db->quoteName($columns))
			->values(implode(',', $values));

			// Reset the query using our newly populated query object.
			$db->setQuery($query);
			$db->query ();
			if (KunenaError::checkDatabaseError()) return;
		} else {
			$query = $db->getQuery(true);

			// Fields to update.
			$fields = array(
					'rank_title = \''.$rank_title.'\'',
					'rank_image = \''.$rank_image.'\'',
					'rank_special = \''.$rank_special.'\'',
					'rank_min = \''.$rank_min.'\''
					);

			// Conditions for which records should be updated.
			$conditions = array('rank_id = '.$db->quote($rankid));

			$query->update($db->quoteName('#__kunena_ranks'))->set($fields)->where($conditions);

			$db->setQuery($query);
			$db->query ();
			if (KunenaError::checkDatabaseError()) return;
		}

		$this->app->enqueueMessage ( JText::_('COM_KUNENA_RANK_SAVED') );
		$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
	}

	function rankupload() {
		if (!JSession::checkToken('post')) {
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_ERROR_TOKEN' ), 'error' );
			$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
			return;
		}

		$file 			= JRequest::getVar( 'Filedata', '', 'files', 'array' );
		$format			= JRequest::getVar( 'format', 'html', '', 'cmd');
		$view			= JRequest::getVar( 'view', '');

		$upload = KunenaUploadHelper::upload($file, 'ranks', $format, $view);
		if ( $upload ) {
			$this->app->enqueueMessage ( JText::_('COM_KUNENA_A_RANKS_UPLOAD_SUCCESS') );
		} else {
			$this->app->enqueueMessage ( JText::_('COM_KUNENA_A_RANKS_UPLOAD_ERROR_UNABLE') );
		}
		$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
	}

	function delete() {
		$db = JFactory::getDBO ();

		if (!JSession::checkToken('post')) {
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_ERROR_TOKEN' ), 'error' );
			$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
			return;
		}

		$cids = JRequest::getVar ( 'cid', array (), 'post', 'array' );
		$cids = implode ( ',', $cids );
		if ($cids) {
			$query = $db->getQuery(true);

			$conditions = array('rank_id IN ('.$cids.')');

			$query->delete($db->quoteName('#__kunena_ranks'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->query ();
			if (KunenaError::checkDatabaseError()) return;
		}

		$this->app->enqueueMessage (JText::_('COM_KUNENA_RANK_DELETED') );
		$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
	}
}
