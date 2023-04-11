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

 use Joomla\CMS\HTML\HTMLHelper;

defined('_JEXEC') or die;

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
 * @var   array   $entries          Entries Array
 */

if (empty($displayData['entries'])) {
	return;
}

$entries = $displayData['entries'];

?>

<?php foreach ($entries as $entry) : ?>
	<tr>
			<th scope="id"><?php echo $entry->id; ?></th>
			<td><?php echo $entry->name; ?></td>
			<td><?php echo $entry->public_idc; ?></td>
			<td><?php echo $entry->private_idc; ?></td>
			<td><?php echo HTMLHelper::date($entry->created); ?></td>
			<td>
				<span class="<?php echo (!$entry->content_id) ? 'text-info icon-clock' : 'text-success icon-check' ; ?>" aria-hidden="true" aria-describedby="tip-state-vgw<?php echo $entry->id; ?>"></span>
				<?php if ($entry->content_id) : ?>
				<div role="tooltip" id="tip-state-vgw<?php echo $entry->id; ?>">
					<?php echo HTMLHelper::date($entry->in_use_since); ?>
				</div>
				<?php endif; ?>
			</td>
			<td><?php echo $entry->title; ?></td>
	</tr>
<?php endforeach; ?>