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
 * Kunena Smileys Controller
 *
 * @since 2.0
 */
class KunenaAdminControllerSmilies extends KunenaController {
	protected $baseurl = null;

	public function __construct($config = array()) {
		parent::__construct($config);
		$this->baseurl = 'administrator/index.php?option=com_kunena&view=smilies';
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
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_A_NO_SMILEYS_SELECTED' ), 'notice' );
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

		$smiley_code = JRequest::getString ( 'smiley_code' );
		$smiley_location = JRequest::getVar ( 'smiley_url' );
		$smiley_emoticonbar = JRequest::getInt ( 'smiley_emoticonbar', 0 );
		$smileyid = JRequest::getInt( 'smileyid', 0 );

		if ( !$smileyid ) {
			$query = $db->getQuery(true);

			// Insert columns.
			$columns = array('code', 'location', 'emoticonbar');

			// Insert values.
			$values = array($db->quote($smiley_code), $db->quote($smiley_location), $db->quote($smiley_emoticonbar));

			// Prepare the insert query.
			$query
			->insert($db->quoteName('#__kunena_smileys'))
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
					'code = \''.$smiley_code.'\'',
					'location = \''.$smiley_location.'\'',
					'emoticonbar = \''.$smiley_emoticonbar.'\''
			);

			// Conditions for which records should be updated.
			$conditions = array('id = '.$db->quote($smileyid));

			$query->update($db->quoteName('#__kunena_smileys'))->set($fields)->where($conditions);

			$db->setQuery($query);
			$db->query ();
			if (KunenaError::checkDatabaseError()) return;
		}

		$this->app->enqueueMessage ( JText::_('COM_KUNENA_SMILEY_SAVED') );
		$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
	}

	function smileyupload() {
		if (!JSession::checkToken('post')) {
			$this->app->enqueueMessage ( JText::_ ( 'COM_KUNENA_ERROR_TOKEN' ), 'error' );
			$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
			return;
		}

		$file 			= JRequest::getVar( 'Filedata', '', 'files', 'array' );
		$format			= JRequest::getVar( 'format', 'html', '', 'cmd');
		$view 			= JRequest::getVar( 'view', '' );

		$upload = KunenaUploadHelper::upload($file, 'emoticons', $format, $view);
		if ( $upload ) {
			$this->app->enqueueMessage ( JText::_('COM_KUNENA_A_EMOTICONS_UPLOAD_SUCCESS') );
		} else {
			$this->app->enqueueMessage ( JText::_('COM_KUNENA_A_EMOTICONS_UPLOAD_ERROR_UNABLE') );
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

			$conditions = array('id IN ('.$cids.')');

			$query->delete($db->quoteName('#__kunena_smileys'));
			$query->where($conditions);
			$db->setQuery($query);
			$db->query ();
			if (KunenaError::checkDatabaseError()) return;
		}

		$this->app->enqueueMessage (JText::_('COM_KUNENA_SMILEY_DELETED') );
		$this->app->redirect ( KunenaRoute::_($this->baseurl, false) );
	}
}
