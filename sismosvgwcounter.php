<?php
/**
* @package     Joomla.Plugins
* @subpackage  Content.Sismosvgwcounter
*
* @author      Martina Scholz <martina@simplysmart-it.de>
* @copyright   (C) 2023 Martina Scholz, SimplySmart-IT <https://simplysmart-it.de>
* @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt
* @link        https://simplysmart-it.de
*/

use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Plugin\CMSPlugin;
use Joomla\CMS\Plugin\PluginHelper;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\Database\DatabaseAwareTrait;
use Joomla\Database\ParameterType;
use Joomla\Event\EventInterface;
use Joomla\Registry\Registry;
use Joomla\String\StringHelper;
use Joomla\Utilities\ArrayHelper;

// phpcs:disable PSR1.Files.SideEffects
\defined('_JEXEC') or die;
// phpcs:enable PSR1.Files.SideEffects

/**
 * Email cloak plugin class.
 *
 * @since  1.0.0
 */
class PlgContentSismosvgwcounter extends CMSPlugin
{
	use DatabaseAwareTrait;

	/**
	 * @var    \Joomla\CMS\Application\SiteApplication
	 *
	 * @since  1.0.0
	 */
	protected $app;

	/**
	 * Load the language file on instantiation.
	 *
	 * @var    boolean
	 *
	 * @since  1.0.0
	 */
	protected $autoloadLanguage = true;

	/**
	 * @var    Array
	 *
	 * @since  1.0.0
	 */
	private $allowedContexts =
				[
					'com_content.article',
					'com_content.featured',
					'com_content.category'
				];

	/**
	 * @var    Array
	 *
	 * @since  1.0.0
	 */
	private $allowedContextsEdit =
				[
					'com_content.article',
					'com_content.featured'
				];

	/**
	 * @var string
	 */
	protected $csvdelimiter = ';';

	/**
	 * @var string
	 */
	protected $csvenclosure = '"';

	/**
	 * @var string
	 */
	protected $csvescape = '"';

	/**
	 * Add additional fields to the supported forms
	 *
	 * @param   Form   $form  The form to be altered.
	 * @param   mixed  $data  The associated data for the form.
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onContentPrepareForm(Form $form, $data)
	{
		if ($form->getName() === 'com_plugins.plugin' && $this->_name === 'sismosvgwcounter') {
			$policyReferrer = $this->checkReferrerPolicy();
			$form->setFieldAttribute('vgwinfo', 'referrerpolicy', $policyReferrer, 'params');
		}

		if (!in_array($form->getName(), $this->allowedContextsEdit)) {
			return true;
		}

		$lang = $this->app->getLanguage();
		$lang->load('plg_content_sismosvgwcounter');

		
		$vgw_publicIdCode = "";
		$vgw_textlength="";

		if (!empty($data)) {
			$dataContent = (is_object($data)) ? ArrayHelper::fromObject($data) : $data;
			$content_id = $dataContent['id'];
			
			if (array_key_exists('vgw_contactId', $dataContent['attribs']) && $dataContent['attribs']['vgw_contactId']) {
				$contact_id = $dataContent['attribs']['vgw_contactId'];
				$vgw_publicIdCode = (!($vgwpidc = $this->checkIDexists($content_id, $contact_id))) ? "" : $vgwpidc->public_idc;
			}

			$vgw_textlength = ($strippedText = $this->getCleanTextOnly($dataContent['articletext'])) ? mb_strlen($strippedText, "UTF-8") : "";
		}

		$form->load('
			<form>
				<fields name="attribs">
				<fieldset name="attribs" addfieldprefix="Joomla\\Component\\Contact\\Administrator\\Field">
					<fieldset name="author">
						<field
							name="vgw_contactId"
							type="modal_contact"
							label="PLG_CONTENT_SISMOSVGWCOUNTER_SELECT_CONTACT_LABEL"
							select="true"
							new="true"
							edit="true"
							clear="true"
							/>
						<field
							name="vgw_id"
							type="text"
							label="PLG_CONTENT_SISMOSVGWCOUNTER_PUBLICID_LABEL"
							readonly="true"
							disabled="true"
							default="' . $vgw_publicIdCode . '"
							/>
						<field
							name="vgw_textlength"
							type="text"
							label="PLG_CONTENT_SISMOSVGWCOUNTER_TEXTLENGTH_LABEL"
							readonly="true"
							disabled="true"
							default="' . $vgw_textlength . '"
							description="PLG_CONTENT_SISMOSVGWCOUNTER_TEXTLENGTH_DESC"
							hint="PLG_CONTENT_SISMOSVGWCOUNTER_TEXTLENGTH_NOTAVAILABEL_HINT"
							/>
						
					</fieldset>
				</fieldset>
				</fields>
			</form>');

		return true;
	}
	/**
	 * Plugin that adds the VGWort Zählerpixel in content.
	 *
	 * @param   string   $context  The context of the content being passed to the plugin.
	 * @param   mixed    &$row     An object with a "text" property or the string to be cloaked.
	 * @param   mixed    &$params  Additional parameters.
	 * @param   integer  $page     Optional page number. Unused. Defaults to zero.
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 *
	 */
	public function onContentPrepare($context, &$row, &$params, $page = 0)
	{
		// Don't run if in the API Application
		// Don't run this plugin when the content is being indexed
		if ($this->app->isClient('api') || $context === 'com_finder.indexer') {
			return;
		}

		// Run this plugin only when the content is in allowed context
		if (!in_array($context, $this->allowedContexts)) {
			return;
		}

		// If the row is not an object or does not have a text property there is nothing to do
		if (!is_object($row) || !property_exists($row, 'text')) {
			return;
		}

		if (!($pluginParams = $this->getParams())) {
			return;
		}

		$strippedText = $this->getCleanTextOnly($row->text);

		$multisite = (int) $page !== 0 || strpos($row->fulltext, 'system-pagebreak') !== false;
		
		if (property_exists($row, 'readmore') && $row->readmore) {
			if (mb_strlen($strippedText = $this->getCleanTextOnly($row->introtext), "UTF-8")  < (int) $pluginParams->minlength) {
				return;
			}
		} elseif($multisite) {
			if (mb_strlen($strippedText = $this->getCleanTextOnly($row->introtext . $row->fulltext), "UTF-8")  < (int) $pluginParams->minlength) {
				return;
			}
		}

		if (!$row->params->get('access-view')) {
			return;
		}

		$attribs = json_decode($row->attribs, true);
		if (!array_key_exists('vgw_contactId', $attribs)) {
			return;
		}

		if (mb_strlen($strippedText, "UTF-8") >= (int) $pluginParams->minlength) {
			$activeMenuItem = $this->app->getMenu()->getActive();
			if ($vgwId = $this->checkIDexists($row->id, $attribs['vgw_contactId'])) {
				$serverAddress = ($pluginParams->sslswitch) ? (str_replace('https', 'http', $pluginParams->serveraddress)) : $pluginParams->serveraddress;
				if (property_exists($row, 'readmore') && $row->readmore) {
					$splitpos = strpos($row->introtext, '>', ((int) $pluginParams->minlength - 1));
					$splitRow[] = substr($row->introtext, 0, $splitpos + 1);
					$splitRow[] = substr($row->introtext, $splitpos + 1);
					$row->text = $splitRow[0] . '<img loading="lazy" src="' . rtrim($serverAddress, '/') . '/' . $vgwId->public_idc . '" width="0.01" height="0.01" alt="" style="float:left;" />'. $splitRow[1];
				} elseif ($context !== 'com_content.article' ||
					($activeMenuItem === $this->app->getMenu()->getDefault($this->app->getLanguage()->getTag()) && !$multisite)) {
					$length = (int) strlen($row->text);
					$splitpos = strpos($row->text, '>', ((int) (strlen($row->text) / 2) - 1));
					$splitRow[] = substr($row->text, 0, $splitpos + 1);
					$splitRow[] = substr($row->text, $splitpos + 1);
					$row->text = $splitRow[0] . '<img loading="lazy" src="' . rtrim($serverAddress, '/') . '/' . $vgwId->public_idc . '" width="0.01" height="0.01" alt="" style="float:left;" />'. $splitRow[1];
				} else {
					$row->text = '<img src="' . rtrim($serverAddress, '/') . '/' . $vgwId->public_idc . '" width="0.01" height="0.01" alt="" style="float:left;" />'. $row->text;
				}
			}
		}
	}

	/**
	 * The after save event.
	 *
	 * @param   string   $context  The context
	 * @param   object   $table    The item
	 * @param   boolean  $isNew    Is new item
	 * @param   array    $data     The validated data
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	public function onContentAfterSave($context, $table, $isNew, $data = [])
	{
		// Run this plugin only when the content is in allowed context
		if (!in_array($context, $this->allowedContexts)) {
			return true;
		};

		if (!is_object($table)) {
			return true;
		}

		if (!($pluginParams = $this->getParams())) {
			return true;
		}

		$strippedText = (array_key_exists('articletext', $data)) ? $this->getCleanTextOnly($data['articletext']) : '';

		if (mb_strlen($strippedText, "UTF-8") >= (int) $pluginParams->minlength) {
			if (!property_exists($table, 'id')) {
				return true;
			}
			$content_id = $table->id;
			if (!array_key_exists('vgw_contactId', $data['attribs']) || !$data['attribs']['vgw_contactId']) {
				return true;
			}
			$contact_id = $data['attribs']['vgw_contactId'];

			if (!$this->checkIDexists($content_id, $contact_id)) {
				// ID Code vergeben
				$inUse = Factory::getDate()->toSql();

				$db = Factory::getDbo();
				$query = $db->getQuery(true);

				$query->select('*')
					->from($db->quoteName('#__sismos_vgwcounter'))
					->where($db->quoteName('contact_id') . ' = :contactId')
					->where($db->quoteName('content_id') . ' =0')
					->bind(':contactId', $contact_id, ParameterType::INTEGER);
				
				$db->setQuery($query);

				try {
					$results = $db->loadObject();
				} catch (\RuntimeException $e) {
					$this->app->enqueueMessage($e->getMessage(), 'error');
					return true;
				}

				$query->clear();

				$query->update($db->quoteName('#__sismos_vgwcounter'))
					->set($db->quoteName('content_id') . ' = :contentId')
					->set($db->quoteName('in_use_since') . ' = :inuse')
					->where($db->quoteName('id') . ' = :pk')
					->bind(':contentId', $table->id, ParameterType::INTEGER)
					->bind(':inuse', $inUse, ParameterType::STRING)
					->bind(':pk', $results->id, ParameterType::INTEGER);

				$db->setQuery($query);

				try {
					$db->execute();
					$this->app->enqueueMessage(Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_VGWID_SUCCESSFULLY_ASSOCIATED_MSG'), 'success');
				} catch (\RuntimeException $e) {
					$this->app->enqueueMessage($e->getMessage(), 'error');
				}
			}
		}

		return true;
	}

	/**
	 * Remove contactid from upload from
	 * Method is called when an extension is being saved
	 *
	 * @param   string   $context  The extension
	 * @param   Table    $table    DataBase Table object
	 * @param   boolean  $isNew    If the extension is new or not
	 *
	 * @return  void
	 *
	 * @since   1.0.0
	 */
	public function onExtensionBeforeSave($context, $table, $isNew): void
	{
		$params = $table->get('params');
		$params = new Registry($params);

		$params->remove('vgwimport');

		$table->set('params', json_encode($params->jsonSerialize()));
		
		return;
	}

	/**
	 * Ajax Entry Point - Upload
	 *
	 * @since   1.0.0
	 */
	public function onAjaxSismosvgwcounter()
	{

		$msgReturn = [];
		$error = false;

		if (!Session::checkToken('post')) {
			$this->returnAjax([Text::_('JINVALID_TOKEN')], true);
			return;
		}
		if (!$this->app->input->get('import_sismosvgw')) {
			return;
		}

		// Get the uploaded file information.
		$input    = Factory::getApplication()->input;

		$data = $input->get('jform', '');

		// Do not change the filter type 'raw'. We need this to let files containing PHP code to upload. See \JInputFiles::get.
		$importfile = $input->files->get('jform', null, 'raw');

		$csvFile = $importfile['params']['vgwimport']['import_file'];
		$csvFileTmp = $csvFile['tmp_name'];

		if (!\is_file($csvFileTmp)) {
			// throw new InvalidArgumentException("Could not find csv file: {$csvFileTmp}");
			$error = true;
			$msgReturn[] = Text::sprintf('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_FILENOTFOUND_MSG', $csvFile['name']);
		}

		if (!\is_readable($csvFileTmp)) {
			// throw new InvalidArgumentException("Could not read csv file: {$csvFileTmp}");
			$error = true;
			$msgReturn[] = Text::sprintf('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_FILENOTREADABLE_MSG', $csvFile['name']);
		}

		if (!isset($data['params']['vgwimport']['import_contactId']) || empty($contactId = $data['params']['vgwimport']['import_contactId'])) {
			$error = true;
			$msgReturn[] = Text::_('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_CONTACT_MISSING_MSG');
		}

		if ($error) {
			$this->returnAjax($msgReturn, $error);
			return false;
		}

		$fh           = \fopen($csvFileTmp, 'r');
		$columns      = $this->getCsvRow($fh);
		$columnsCount = \count($columns);

		if ($columns === false) {
			// throw new InvalidArgumentException("Could not determine the headers from the given file {$csvFile}");
			$this->returnAjax([Text::sprintf('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_CSVHEADER_MSG', $csvFile['name'])], true);
			return false;
		}

		foreach ($columns as &$col) {
			$col = trim($col);
		}

		if (!in_array('Private Identification Code', $columns)) {
			$this->returnAjax([Text::sprintf('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_CSVHEADERMISMATCH_MSG', 'Private Identification Code')], true);
			return false;
		}

		if (!in_array('Public Identification Code', $columns)) {
			$this->returnAjax([Text::sprintf('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_CSVHEADERMISMATCH_MSG', 'Public Identification Code')], true);
			return false;
		}

		$rowNumber = 1;

		$newEntries = [];

		while (($row = $this->getCsvRow($fh)) !== false) {
			if ($columnsCount !== \count($row)) {
				// throw new InvalidArgumentException("Row no. {$rowNumber} in csv file {$csvFile} should have an equal number of elements as header column");
				$this->returnAjax([Text::sprintf('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_ERROR_CSVHEADERROWMISMATCH_MSG', $rowNumber, $csvFile['name'])], true);
				return false;
				$this->app->close();
			}
			$newEntries[] = \array_combine($columns, $row);
			$rowNumber++;
		}

		$created = Factory::getDate()->toSql();

		$db = Factory::getDbo();
		$query = $db->getQuery(true);
		$query->insert($db->quoteName('#__sismos_vgwcounter'))
			->columns(
				[
					$db->quoteName('private_idc'),
					$db->quoteName('public_idc'),
					$db->quoteName('contact_id'),
					$db->quoteName('created'),
				]
			);
		foreach ($newEntries as $entry) {
			$query->values(
				implode(
					',',
					$query->bindArray(
						[$entry['Private Identification Code'], $entry['Public Identification Code'],$contactId, $created],
						[ParameterType::STRING, ParameterType::STRING, ParameterType::INTEGER, ParameterType::STRING]
					)
				)
			);
		}

		$db->setQuery($query);

		try {
			$db->execute();
			$msgReturn[] = Text::sprintf('PLG_CONTENT_SISMOSVGWCOUNTER_IMPORT_SUCCESS_MSG', $rowNumber);
			$error = false;
		} catch (\RuntimeException $e) {
			$this->app->enqueueMessage($e->getMessage(), 'error');
			$msgReturn[] = $e->getMessage();
			$error = true;
		}

		$this->returnAjax($msgReturn, $error);

		$this->app->close();
	}

	/**
	 * Sends Ajax Response.
	 *
	 * @param array $msgReturn
	 * @param boolean $error
	 *
	 * @return void
	 *
	 * @since   1.0.0
	 *
	 */
	private function returnAjax($msgReturn, $error)
	{
		$json = new JsonResponse(['messages' => $msgReturn], implode('<br>', $msgReturn), $error);
		echo $json;
	}

	/**
	 * Returns a row from the csv file in an indexed array.
	 *
	 * @param resource $fh
	 *
	 * @return array
	 *
	 * @since   1.0.0
	 *
	 */
	protected function getCsvRow($fh)
	{
		if (\version_compare(PHP_VERSION, '5.3.0', '>')) {
			return \fgetcsv($fh, null, $this->csvdelimiter, $this->csvenclosure, $this->csvescape);
		}

		return \fgetcsv($fh, null, $this->csvdelimiter, $this->csvenclosure);
	}

	/**
	 * The check if a id code for content exists.
	 *
	 * @param   int     $content_id  The content_id
	 * @param   int     $contact_id  The contact_id
	 *
	 * @return  boolean|stdclass
	 *
	 * @since   1.0.0
	 */
	private function checkIDexists($content_id, $contact_id)
	{
		try {
			$db    = Factory::getDbo();
			$query = $db->getQuery(true)
				->select('*')
				->from($db->quoteName('#__sismos_vgwcounter'))
				->where($db->quoteName('content_id') . ' = :content_id')
				->bind(':content_id', $content_id, ParameterType::INTEGER);

			if ($contact_id) {
				$query->where($db->quoteName('contact_id') . ' = :contact_id')
				->bind(':contact_id', $contact_id, ParameterType::INTEGER);
			}

			return $db->setQuery($query)->loadObject();
		} catch (\Exception $e) {
			return null;
		}
	}

	/**
	 * Check if Plugin is enbaled
	 *
	 * @return  boolean
	 *
	 * @since   1.0.0
	 */
	private function isEnabled()
	{

		if (!PluginHelper::isEnabled($this->_type, $this->_name)) {
			return false;
		}

		return true;
	}

	/**
	 * Get the Plugin Params as Object
	 *
	 * @return  stdClass|Boolean   return params as object or false
	 *
	 * @since   1.0.0
	 */
	private function getParams()
	{

		if (!$this->isEnabled() || !property_exists($this, 'params')) {
			return false;
		}
		
		if ($params = $this->params) {
			$params->def('minlength', "1800");
			return $params->toObject();
		}

		return false;
	}

	/**
	 * Get clean text without html tags
	 *
	 * @param  string  $str_text
	 *
	 * @return  string  return string
	 *
	 * @since   1.0.0
	 */
	private function getCleanTextOnly($str_text)
	{
		$strippedText = ($str_text) ? trim($str_text) : $str_text;

		if (!$strippedText) {
			return $strippedText;
		}

		// TODO Table swith setting if table is often used for styling purpose
		foreach (['figure','img','a', 'hr', 'table'] as $tag) {
			$strippedText = ($strippedText && $stripped = $this->removeElementsByTag($strippedText, $tag)) ? $stripped : $strippedText;
		}
		$strippedText = strip_tags($strippedText);
		$strippedText = str_replace(["\r\n", "\r", "\n"], "", $strippedText);

		return ($strippedText) ? trim($strippedText) : $strippedText;
	}

	/**
	 * Remove Elemnts from html text by tagname
	 *
	 * @param  string  $html_str
	 * @param  string  $tag
	 *
	 * @return  string  return string without the tags
	 *
	 * @since   1.0.0
	 */
	private function removeElementsByTag($html_str, $tag)
	{
		$dom = new \DOMDocument();
		@$dom->loadHtml($html_str);
		$dom->preserveWhiteSpace = false;
		$elements = $dom->getElementsByTagName($tag);
		$elems = [];
		foreach ($elements as $element) {
			$elems[] = $element;
		}
		foreach ($elems as $elem) {
			$elem->parentNode->removeChild($elem);
		}

		return $dom->saveHTML();
	}

	/**
	 * Checks the referrer policy set by system plugin
	 *
	 * @return  string  return selected option as string
	 *
	 * @since   1.0.0
	 */
	private function checkReferrerPolicy()
	{
		if (PluginHelper::isEnabled('system', 'httpheaders')) {
			$plugin = PluginHelper::getPlugin('system', 'httpheaders');
			$pluginParams = json_decode($plugin->params, true);
			if (empty($pluginParams)) {
				// Default value
				return 'strict-origin-when-cross-origin';
			} else {
				return (array_key_exists('referrerpolicy', $pluginParams)) ? $pluginParams['referrerpolicy'] : '';
			}
		}
		return '';
	}
}
