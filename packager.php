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

jimport('joomla.filesystem.file');
jimport('joomla.filesystem.folder');

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
						$this->exportComponent($extension);
						break;
					case 'file':
						// $this->exportFile($extension);
						// break;
					case 'language':
						// $this->exportLanguage($extension);
						// break;
					case 'library':
						// $this->exportLibrary($extension);
						// break;
					case 'module':
						// $this->exportModule($extension);
						// break;
					case 'package':
						// $this->exportPackage($extension);
						// break;
					case 'plugin':
						// $this->exportPlugin($extension);
						// break;
					case 'template':
						$this->underConstruction($extension);
						break;
				}
			}
		}
	}

	/**
	 * Method to export component.
	 *
	 * @param   JTableExtension  &$item  The extension object.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since   3.1
	 */
	public function exportComponent(&$item)
	{
		// Initialiase variables.
		$app     = JFactory::getApplication();
		$site    = JPATH_SITE . '/components/' . $item->element;
		$admin   = JPATH_ADMINISTRATOR . '/components/' . $item->element;
		$tmp     = $app->getCfg('tmp_path') . '/' . $item->element;
		$ziproot = $app->getCfg('tmp_path') . '/' . uniqid($item->element . '_') . '.zip';

		if (JFolder::exists($tmp))
		{
			JFolder::delete($tmp);
		}

		JFolder::create($tmp);

		if (JFolder::exists($site))
		{
			JFolder::copy($site, $tmp . '/site');
		}

		if (JFolder::exists($admin))
		{
			JFolder::copy($admin, $tmp . '/admin');

			$xml_src = $tmp . '/admin/' . $item->element . '.xml';
			$xml_dest = $tmp . '/' . $item->element . '.xml';

			var_dump($xml_src);
			var_dump($xml_dest);

			JFile::move($xml_src, $xml_dest);
		}

		$files = $this->recursiveScandir($tmp);

		if (!$this->createZip($files, $ziproot, true))
		{
			$this->setError(JText::_('PLG_SYSTEM_PACKAGER_ERR_ZIP_CREATE_FAILURE'));

			return false;
		}

		JFolder::delete($tmp);

		return true;
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

	/**
	 * [recursiveScandir description]
	 *
	 * @param   [type]  $dir  [description]
	 *
	 * @return  array
	 *
	 * @since   3.1
	 */
	public function recursiveScandir($dir)
	{
		$contents = array();

		foreach (scandir($dir) as $file)
		{
			if ($file == '.' || $file == '..')
			{
				continue;
			}

			$path = $dir . '/' . $file;

			if (is_dir($path))
			{
				$contents = array_merge($contents, $this->recursiveScandir($path));
			}
			else
			{
				$contents[] = $path;
			}
		}

		return $contents;
	}

	/**
	 * Method to create a zip file.
	 *
	 * @param   array    $files      Array of files to add to archive.
	 * @param   string   $dest       The path to the destination file.
	 * @param   boolean  $overwrite  True if existing files can be replaced.
	 *
	 * @return  boolean  True if successful, false otherwise and internal error is set.
	 *
	 * @since   3.1
	 */
	public function createZip($files, $dest, $overwrite = false)
	{
		// Get the application.
		$app = JFactory::getApplication();

		if (JFile::exists($dest) && !$overwrite)
		{
			return false;
		}

		$valid_files = array();

		if (is_array($files))
		{
			foreach ($files as $file)
			{
				if (JFile::exists($file))
				{
					$valid_files[] = $file;
				}
			}
		}

		if (count($valid_files))
		{
			$zip = new ZipArchive;

			if ($zip->open($dest, $overwrite ? ZIPARCHIVE::OVERWRITE : ZIPARCHIVE::CREATE) !== true)
			{
				return false;
			}

			foreach ($valid_files as $file)
			{
				$new_filename = str_replace($app->getCfg('tmp_path') . '/', '', $file);
				$zip->addFile($file, $new_filename);
			}

			$zip->close();

			return JFile::exists($dest);
		}
		else
		{
			return false;
		}
	}
}
