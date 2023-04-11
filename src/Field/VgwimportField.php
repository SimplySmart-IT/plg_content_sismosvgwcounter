<?php

/**
 * @package     Sismosvgwcounter
 * @subpackage  Fields.Vgwimport
 *
 * @copyright   (C) 2023 Martina Scholz, SimplySmart-IT <https://simplysmart-it.de>
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt

 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

namespace Joomla\Plugin\Content\Sismosvgwcounter\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SubformField;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('JPATH_PLATFORM') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Field to show, create and refresh a oauth token
 *
 * @since  1.0.0
 */
class VgwimportField extends SubformField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 *
	 * @since   1.0.0
	 */
	protected $type = 'Vgwimport';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the <field /> tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value.
	 *
	 * @return  boolean  True on success.
	 *
	 * @since   1.0.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		/**
		 * When you have subforms which are not repeatable (i.e. a subform custom field with the
		 * repeat attribute set to 0) you get an array here since the data comes from decoding the
		 * JSON into an associative array, including the media subfield's data.
		 *
		 * However, this method expects an object or a string, not an array. Typecasting the array
		 * to an object solves the data format discrepancy.
		 */
		$value = is_array($value) ? (object) $value : $value;

		/**
		 * If the value is not a string, it is
		 * most likely within a custom field of type subform
		 * and the value is a stdClass with properties
		 * access_token. So it is fine.
		*/
		if (\is_string($value)) {
			json_decode($value);

			// Check if value is a valid JSON string.
			if ($value !== '' && json_last_error() !== JSON_ERROR_NONE) {
					$value = '';
			}
		} elseif (!is_object($value)
			|| !property_exists($value, 'access_token')
		) {
			$value->access_token = "ERROR";
		}

		if (!parent::setup($element, $value, $group)) {
			$value = '';
		}

		$xml = <<<XML
<?xml version="1.0" encoding="utf-8"?>
<form>
	<fieldset
		name="vgwimport"
		label="PLG_CONTENT_SISMOSVGWCOUNTER_SETTING_IMPORT_UPLOAD"
	>
		<field
			name="import_file"
			type="file"
			label="PLG_CONTENT_SISMOSVGWCOUNTER_SETTING_IMPORT_FIELD_FILE_LABEL"
			multiple="false"
			parentclass="stack span-2"
			accept="text/csv"
			/>

		<field
			name="import_contactId"
			type="modal_contact"
			label="PLG_CONTENT_SISMOSVGWCOUNTER_SETTING_IMPORT_FIELD_CONTACT_LABEL"
			select="true"
			new="true"
			clear="true"
			parentclass="stack span-2"
			addfieldprefix="Joomla\\Component\\Contact\\Administrator\\Field"
		/>
	</fieldset>
</form>
XML;
		$this->formsource = $xml;

		return true;
	}

	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 * @since   1.0.0
	 */
	protected function getInput()
	{
		/** @var Joomla\CMS\WebAsset\WebAssetManager $wa */
		$wa = Factory::getApplication()->getDocument()->getWebAssetManager();
		//$wa->registerAndUseStyle('plg_sismos_zoom.token', 'plg_sismos_zoom/sismos_token.css')
		$wa->registerAndUseScript('plg_content_sismosvgwcounter.import', 'plg_content_sismosvgwcounter/sismosvgwimport.js');

		Text::script('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_GLOBAL_MSG');
		
		$input = parent::getInput();
		$input .= '<div class="d-flex float-end">';
		$input .= '<button type="button" id ="importbtnsismosvgw" class="btn btn-success">';
		$input .= Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_SETTING_IMPORT_UPLOAD_ACTION') . '</button>';
		$input .= '</div>';
		$input .='<input type="hidden" value="" name="import_sismosvgw" />';
		return $input;
	}
}
