<?php
/**
 * @package     Joomla.Plugin
 * @subpackage  System.Packager
 *
 * @copyright   Copyright (C) 2013 AtomTech, Inc. All rights reserved.
 * @license     GNU General Public License version 2 or later; see LICENSE.txt
 */

// No direct access.
defined('_JEXEC') or die;

/**
 * Joomla Packager plugin.
 *
 * @package     Joomla.Plugin
 * @subpackage  System.Packager
 * @since       3.1
 */
class PlgSystemPackager extends JPlugin
{
	/**
	 * Constructor.
	 *
	 * @param   object  &$subject  The object to observe.
	 * @param   array   $config    An array that holds the plugin configuration.
	 *
	 * @access  protected
	 * @since   3.1
	 */
	public function __construct(& $subject, $config)
	{
		parent::__construct($subject, $config);

		$this->loadLanguage();
	}

	/**
	* Before the framework renders the application.
	*
	* @return void
	*
	* @since 3.1
	*/
	public function onBeforeRender()
	{
		// Get the application.
		$app = JFactory::getApplication();

		// Detecting Active Variables.
		$option = $app->input->getCmd('option', '');
		$view   = $app->input->getCmd('view', '');
		$layout = $app->input->getCmd('layout', 'default');

		// Only in Admin.
		if ($app->isSite())
		{
			return;
		}

		if ($option == 'com_installer' && $view == 'manage' && $layout == 'default')
		{
			// Get the toolbar object instance.
			$toolbar = JToolBar::getInstance('toolbar');
			$toolbar->appendButton('Confirm', 'PLG_SYSTEM_PACKAGER_MSG_EXPORT', 'cogs', 'JTOOLBAR_EXPORT', 'export', true);
		}
	}

	/**
	 * After framework load and application initialise.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function onAfterInitialise()
	{
		// Get the application.
		$app = JFactory::getApplication();

		// Only in Admin.
		if ($app->isSite())
		{
			return;
		}

		// Detecting Active Variables.
		$option = $app->input->getCmd('option', '');
		$view   = $app->input->getCmd('view', '');
		$layout = $app->input->getCmd('layout', 'default');

		if ($option == 'com_installer' && $view == 'manage' && $layout == 'default')
		{
			// Get the input.
			$cid = $app->input->post->get('cid', array(), 'array');

			foreach ($cid as $id)
			{
				// Get an instance of the extension table.
				$extension = JTable::getInstance('Extension');
				$extension->load($id);

				$this->loadExtensionLanguage($extension);

				switch ($extension->type)
				{
					case 'component':
						// $this->exportComponent($extension);
						// break;
					case 'file':
					case 'language':
					case 'library':
					case 'module':
					case 'package':
					case 'plugin':
					case 'template':
						$this->underConstruction($extension);
						break;
				}
			}
		}
	}

	/**
	 * Displays a message when the extension type is not supported yet.
	 *
	 * @param   JTableExtension  &$item  The extension object.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	public function underConstruction(&$item)
	{
		// Get the application.
		$app = JFactory::getApplication();

		// Get the current url.
		$current = JUri::getInstance()->toString();

		$app->redirect($current, JText::sprintf('PLG_SYSTEM_PACKAGER_MSG_UNDER_CONSTRUCTION', JText::_(strtoupper($item->name)), $item->type), 'warning');

		return true;
	}

	/**
	 * Method to load the extension language.
	 *
	 * @param   JTableExtension  &$item  The extension object.
	 *
	 * @return  void
	 *
	 * @since   3.1
	 */
	private function loadExtensionLanguage(&$item)
	{
		// Get the language.
		$lang = JFactory::getLanguage();

		$path = $item->client_id ? JPATH_ADMINISTRATOR : JPATH_SITE;

		switch ($item->type)
		{
			case 'component':
				$extension = $item->element;
				$source = JPATH_ADMINISTRATOR . '/components/' . $extension;
				$lang->load("$extension.sys", JPATH_ADMINISTRATOR, null, false, false)
				|| $lang->load("$extension.sys", $source, null, false, false)
				|| $lang->load("$extension.sys", JPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
				|| $lang->load("$extension.sys", $source, $lang->getDefault(), false, false);
			break;
			case 'file':
				$extension = 'files_' . $item->element;
				$lang->load("$extension.sys", JPATH_SITE, null, false, false)
				|| $lang->load("$extension.sys", JPATH_SITE, $lang->getDefault(), false, false);
			break;
			case 'library':
				$extension = 'lib_' . $item->element;
				$lang->load("$extension.sys", JPATH_SITE, null, false, false)
				|| $lang->load("$extension.sys", JPATH_SITE, $lang->getDefault(), false, false);
			break;
			case 'module':
				$extension = $item->element;
				$source = $path . '/modules/' . $extension;
				$lang->load("$extension.sys", $path, null, false, false)
				|| $lang->load("$extension.sys", $source, null, false, false)
				|| $lang->load("$extension.sys", $path, $lang->getDefault(), false, false)
				|| $lang->load("$extension.sys", $source, $lang->getDefault(), false, false);
			break;
			case 'package':
				$extension = $item->element;
				$lang->load("$extension.sys", JPATH_SITE, null, false, false)
				|| $lang->load("$extension.sys", JPATH_SITE, $lang->getDefault(), false, false);
			break;
			case 'plugin':
				$extension = 'plg_' . $item->folder . '_' . $item->element;
				$source = JPATH_PLUGINS . '/' . $item->folder . '/' . $item->element;
				$lang->load("$extension.sys", JPATH_ADMINISTRATOR, null, false, false)
				|| $lang->load("$extension.sys", $source, null, false, false)
				|| $lang->load("$extension.sys", JPATH_ADMINISTRATOR, $lang->getDefault(), false, false)
				|| $lang->load("$extension.sys", $source, $lang->getDefault(), false, false);
			break;
			case 'template':
				$extension = 'tpl_' . $item->element;
				$source = $path . '/templates/' . $item->element;
				$lang->load("$extension.sys", $path, null, false, false)
				|| $lang->load("$extension.sys", $source, null, false, false)
				|| $lang->load("$extension.sys", $path, $lang->getDefault(), false, false)
				|| $lang->load("$extension.sys", $source, $lang->getDefault(), false, false);
			break;
		}
	}
}
