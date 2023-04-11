<?php

/**
 * @package     Sismosvgwcounter
 * @subpackage  Fields.Vgwinfo
 *
 * @copyright   (C) 2023 Martina Scholz, SimplySmart-IT <https://simplysmart-it.de>
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt

 * @phpcs:disable PSR1.Classes.ClassDeclaration.MissingNamespace
 */

namespace Joomla\Plugin\Content\Sismosvgwcounter\Field;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Field\SpacerField;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;

// phpcs:disable PSR1.Files.SideEffects
\defined('JPATH_PLATFORM') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * The Field to load additional info and check results for the plugin
 *
 * @since  1.0.0
 */
class VgwinfoField extends SpacerField
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $type = 'Vgwinfo';

	/**
	 * The referrer policy set by plugin or default.
	 *
	 * @var    string
	 *
	 * @since  1.0.0
	 */
	protected $referrerpolicy = 'strict-origin-when-cross-origin';

	/**
	 * Method to attach a Form object to the field.
	 *
	 * @param   \SimpleXMLElement  $element  The SimpleXMLElement object representing the `<field>` tag for the form field object.
	 * @param   mixed              $value    The form field value to validate.
	 * @param   string             $group    The field name group control value. This acts as an array container for the field.
	 *                                      For example if the field has name="foo" and the group value is set to "bar" then the
	 *                                      full field name would end up being "bar[foo]".
	 *
	 * @return  boolean  True on success.
	 *
	 * @since 1.0.0
	 */
	public function setup(\SimpleXMLElement $element, $value, $group = null)
	{
		$return = parent::setup($element, $value, $group);

		if ($return) {
			$this->referrerpolicy = (string) $this->element['referrerpolicy'] ? (string) $this->element['referrerpolicy'] : 'strict-origin-when-cross-origin';
		}

		return $return;
	}

	/**
	 * Method to get the field label markup for a spacer.
	 * Use the label text or name from the XML element as the spacer or
	 * Use a hr="true" to automatically generate plain hr markup
	 *
	 * @return  string  The field label markup.
	 *
	 * @since   1.7.0
	 */
	protected function getLabel()
	{
		$lang = Factory::getApplication()->getLanguage();
		$lang->load('plg_system_httpheaders');

		$html = [];
		$class = !empty($this->class) ? ' class="' . $this->class . '"' : '';
		$html[] = '<span class="spacer">';
		$html[] = '<span class="before"></span>';
		$html[] = '<span' . $class . '>';

		if ((string) $this->element['hr'] === 'true') {
			$html[] = '<hr' . $class . '>';
		} else {
			$label = '';

			// Get the label text from the XML element, defaulting to the element name.
			$text = $this->element['label'] ? (string) $this->element['label'] : (string) $this->element['name'];
			$text = $this->translateLabel ? Text::_($text) : $text;

			// Add Referrer Policy info
			$text .= Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_INFO_REFERRERHEADING_SETTING_FIELD_LABEL');
			$text .= '<b>' . Text::_('PLG_SYSTEM_HTTPHEADERS_REFERRERPOLICY') . ': "' . $this->referrerpolicy . '"</b>';
			$text .= '<span class="ms-2 ' .  ((!in_array($this->referrerpolicy, ['unsafe-url','no-referrer-when-downgrade'])) ? 'text-danger icon-times' : 'text-success icon-check') . '"></span>';
			$text .= Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_INFO_REFERRER_SETTING_FIELD_LABEL');
			
			// Build the class for the label.
			$class = !empty($this->description) ? 'hasPopover' : '';
			$class = $this->required == true ? $class . ' required' : $class;

			// Add the opening label tag and main attributes attributes.
			$label .= '<label id="' . $this->id . '-lbl" class="' . $class . '"';

			// If a description is specified, use it to build a tooltip.
			if (!empty($this->description)) {
				HTMLHelper::_('bootstrap.popover', '.hasPopover');
				$label .= ' title="' . htmlspecialchars(trim($text, ':'), ENT_COMPAT, 'UTF-8') . '"';
				$label .= ' data-bs-content="' . htmlspecialchars(
					$this->translateDescription ? Text::_($this->description) : $this->description,
					ENT_COMPAT,
					'UTF-8'
				) . '"';

				if (Factory::getLanguage()->isRtl()) {
					$label .= ' data-bs-placement="left"';
				}
			}

			// Add the label text and closing tag.
			$label .= '>' . $text . '</label>';
			$html[] = $label;
		}

		$html[] = '</span>';
		$html[] = '<span class="after"></span>';
		$html[] = '</span>';

		return implode('', $html);
	}
}
