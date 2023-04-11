<?php

/**
 * @package     Sismosvgwcounter
 * @subpackage  Fields.VgwentriesField
 *
 * @copyright   (C) 2023 Martina Scholz, SimplySmart-IT <https://simplysmart-it.de>
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt

 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

namespace Joomla\Plugin\Content\Sismosvgwcounter\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Layout\FileLayout;

// phpcs:disable PSR1.Files.SideEffects
\defined('JPATH_PLATFORM') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Field to load the Entries form inside plugin settings current form
 *
 * @since  1.0.0
 */
class VgwentriesField extends FormField
{
	/**
	 * The form field type.
	 * @var    string
	 */
	protected $type = 'Vgwentries';

	/**
	 * Name of the layout being used to render the field
	 *
	 * @var    string
	 * @since  1.0.0
	 */
	protected $layout = 'sismos.form.field.vgwentries.default';

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		// Value is in json format, so decode double-quotes for html value="..."
		if (empty($this->value)) {
			$this->value = '[]';
		}

		$this->value = json_decode($this->value, true);

		// Get User
		// $user = Factory::getApplication()->getIdentity();

		return parent::getInput();
	}

	/**
	 * Method to get the field label markup.
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   1.0.0
	 */
	protected function getLabel()
	{
		return '';
	}

	/**
	 * Method to get the data to be passed to the layout for rendering.
	 *
	 * @return  array
	 *
	 * @since 1.0.0
	 */
	protected function getLayoutData()
	{
		$layoutData = parent::getLayoutData();

		$data = $this->getData();

		$layoutData['value']= $data;

		/* TODO $entryEditForm = new Form('plg_sismosvgwcounter.entry_edit');

		FormHelper::addFormPath(dirname(__DIR__, 2) . '/forms');
		$entryEditForm->loadFile('sismosvgwcounter.entry');

		$entryEditForm->setDatabase(Factory::getDbo());

		$layoutData['entryEditForm'] = $entryEditForm; */

		return $layoutData;
	}


	/**
	 * Allow to override renderer include paths in child fields
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	protected function getLayoutPaths()
	{
		$renderer = new FileLayout($this->layout);

		$renderer->setIncludePaths(parent::getLayoutPaths());

		$renderer->addIncludePaths(dirname(__DIR__, 2) . '/layouts');

		$paths = $renderer->getIncludePaths();

		return $paths;
	}

	/**
	 * Get the data from database
	 *
	 * @return  array
	 *
	 * @since   1.0.0
	 */
	protected function getData()
	{
		try {
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select([
						$db->qn('a.id'),
						$db->qn('a.contact_id'),
						$db->qn('a.content_id'),
						$db->qn('a.private_idc'),
						$db->qn('a.public_idc'),
						$db->qn('a.created'),
						$db->qn('a.in_use_since'),
						$db->qn('a.in_use_since'),
						$db->qn('ct.name'),
						$db->qn('co.title')
					])
				->from($db->quoteName('#__sismos_vgwcounter', 'a'))
				->join('LEFT', $db->quoteName('#__content', 'co'), $db->quoteName('co.id') . ' = ' . $db->quoteName('a.content_id'))
				->join('LEFT', $db->quoteName('#__contact_details', 'ct'), $db->quoteName('ct.id') . ' = ' . $db->quoteName('a.contact_id'))
				->order($db->qn('a.id') . ' DESC');
			

			return $db->setQuery($query)->loadObjectList();
		} catch (\Exception $e) {
			Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
			return [];
		}
	}
}
