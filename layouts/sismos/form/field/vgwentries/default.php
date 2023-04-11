<?php

/**
 * @package     Joomla.Plugin
 * @subpackage  Fields.Vgwentries
 *
 * @author      Martina Scholz <martina@simplysmart-it.de>
 * @copyright   Copyright (C) 2023 Martina Scholz. All rights reserved.
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt
 * @link        https://simplysmart-it.de
 */

use Joomla\CMS\Date\Date;
use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Layout\FileLayout;
use Joomla\CMS\Layout\LayoutHelper;
use Joomla\CMS\Plugin\PluginHelper;

defined('_JEXEC') or die;

extract($displayData);

if (empty($displayData['value'])) {
		return;
}

/**
 * Layout variables
 * -----------------
 * @var   Form    $tmpl             The Empty form for template
 * @var   array   $forms            Array of JForm instances for render the rows
 * @var   bool    $multiple         The multiple state for the form field
 * @var   int     $min              Count of minimum repeating in multiple mode
 * @var   int     $max              Count of maximum repeating in multiple mode
 * @var   string  $name             Name of the input field.
 * @var   string  $fieldname        The field name
 * @var   string  $fieldId          The field ID
 * @var   string  $control          The forms control
 * @var   string  $label            The field label
 * @var   string  $description      The field description
 * @var   array   $buttons          Array of the buttons that will be rendered
 * @var   bool    $groupByFieldset  Whether group the subform fields by it`s fieldset
 * @var   Form    $entryEditForm    Form Object for appointment entry edit
 */

$entries = $displayData['value'];
?>

<div class="mt-2">
	<table id="vgwcounter-entries-table" class="table table-striped">
	<thead>
		<tr>
			<th><?php echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_ID_LABEL'); ?></th>
			<th><?php echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_CONTACT_LABEL'); ?></th>
			<th><?php echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_PUBLICIDC_LABEL'); ?></th>
			<th><?php echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_PRIVATEIDC_LABEL'); ?></th>
			<th><?php echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_IMPORTDATE_LABEL'); ?></th>
			<th><?php echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_STATE_LABEL'); ?></th>
			<th><?php echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_CONTENT_LABEL'); ?></th>
			<!-- TODO <th><?php // echo Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_ENTRIES_TABLE_FIELD_ACTIONS_LABEL'); ?></th> -->
		</tr>
	</thead>
	<tbody>
	<?php echo $this->sublayout('items', ['entries' => $entries]); ?>
	</tbody>
	</table>
</div>
