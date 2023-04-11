<?php
/**
 * @package     Joomla.Plugin
 * @subpackage	Content.Sismosvgwcounter
 *
 * @author     Martina Scholz <martina@simplysmart-it.de>
 * @copyright  (C) 2023 Martina Scholz, SimplySmart-IT <https://simplysmart-it.de>
 * @license    GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html; see LICENSE.txt
 * @link       https://simplysmart-it.de
 */

defined('_JEXEC') or die();

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\InstallerScript;

class plgContentSismosvgwcounterInstallerScript extends InstallerScript
{
	protected $minimumPhp    = '7.4.0';
	protected $minimumJoomla = '4.0.0';

	public function postflight($type, $parent)
	{
		if ($type != 'install' && $type != 'discover_install') {
			return;
		}

		$db = Factory::getDbo();
		$db->setQuery("update #__extensions set enabled=1 where type = 'plugin' and element = 'sismosvgwcounter'");
		$db->execute();
	}
}
