<?php
/**
 * @package   panopticon
 * @copyright Copyright (c)2023-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   GNU General Public License version 3, or later
 */

namespace Akeeba\Panopticon\View\Sites;

defined('AKEEBA') || die;

use Akeeba\Panopticon\Model\Site;
use Awf\Mvc\DataView\Html as DataViewHtml;
use Awf\Text\Text;

class Html extends DataViewHtml
{
	protected Site $item;

	protected ?string $connectionError = null;

	protected ?string $curlError = null;

	protected ?int $httpCode;

	public function onBeforeBrowse(): bool
	{
		$document = $this->container->application->getDocument();
		$toolbar  = $document->getToolbar();
		$buttons  = [
			[
				'title'   => Text::_('PANOPTICON_BTN_ADD'),
				'class'   => 'btn btn-success',
				'onClick' => 'akeeba.System.submitForm(\'add\')',
				'icon'    => 'fa fa-plus',
			],
			[
				'title'   => Text::_('PANOPTICON_BTN_EDIT'),
				'class'   => 'btn btn-secondary border-light',
				'onClick' => 'akeeba.System.submitForm(\'edit\')',
				'icon'    => 'fa fa-pen-to-square',
			],
			[
				'title'   => Text::_('PANOPTICON_BTN_COPY'),
				'class'   => 'btn btn-secondary border-light',
				'onClick' => 'akeeba.System.submitForm(\'copy\')',
				'icon'    => 'fa fa-clone',
			],
			[
				'title'   => Text::_('PANOPTICON_BTN_DELETE'),
				'class'   => 'btn btn-danger',
				'onClick' => 'akeeba.System.submitForm(\'remove\')',
				'icon'    => 'fa fa-trash-can',
			],
		];

		array_walk($buttons, function (array $button) {
			$this->container->application->getDocument()->getToolbar()->addButtonFromDefinition($button);
		});

		$toolbar->setTitle(Text::_('PANOPTICON_SITES_TITLE'));

		return parent::onBeforeBrowse();
	}

	protected function onBeforeAdd()
	{
		$document = $this->container->application->getDocument();
		$toolbar  = $document->getToolbar();
		$buttons = [
			[
				'title'   => Text::_('PANOPTICON_BTN_SAVE'),
				'class'   => 'btn btn-primary',
				'onClick' => 'akeeba.System.submitForm(\'save\');',
				'icon'    => 'fa fa-save',
			],
			[
				'title'   => Text::_('PANOPTICON_BTN_APPLY'),
				'class'   => 'btn btn-success',
				'onClick' => 'akeeba.System.submitForm(\'apply\');',
				'icon'    => 'fa fa-check',
			],
			[
				'title' => Text::_('PANOPTICON_BTN_CANCEL'),
				'class' => 'btn btn-danger',
				'onClick' => 'akeeba.System.submitForm(\'cancel\');',
				'icon'  => 'fa fa-cancel',
			],
		];

		array_walk($buttons, function (array $button) {
			$this->container->application->getDocument()->getToolbar()->addButtonFromDefinition($button);
		});

		$toolbar->setTitle(Text::_('PANOPTICON_SITES_TITLE_NEW'));

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->item = $this->getModel();

		$this->connectionError = $this->container->segment->getFlash('site_connection_error', null);
		$this->httpCode = $this->container->segment->getFlash('site_connection_http_code', null);
		$this->curlError = $this->container->segment->getFlash('site_connection_curl_error', null);

		return parent::onBeforeAdd();
	}

	protected function onBeforeEdit()
	{
		$document = $this->container->application->getDocument();
		$toolbar  = $document->getToolbar();
		$buttons = [
			[
				'title'   => Text::_('PANOPTICON_BTN_SAVE'),
				'class'   => 'btn btn-primary',
				'onClick' => 'akeeba.System.submitForm(\'save\');',
				'icon'    => 'fa fa-save',
			],
			[
				'title'   => Text::_('PANOPTICON_BTN_APPLY'),
				'class'   => 'btn btn-success',
				'onClick' => 'akeeba.System.submitForm(\'apply\');',
				'icon'    => 'fa fa-check',
			],
			[
				'title' => Text::_('PANOPTICON_BTN_CANCEL'),
				'class' => 'btn btn-danger',
				'onClick' => 'akeeba.System.submitForm(\'cancel\');',
				'icon'  => 'fa fa-cancel',
			],
		];

		array_walk($buttons, function (array $button) {
			$this->container->application->getDocument()->getToolbar()->addButtonFromDefinition($button);
		});

		$toolbar->setTitle(Text::_('PANOPTICON_SITES_TITLE_EDIT'));

		/** @noinspection PhpFieldAssignmentTypeMismatchInspection */
		$this->item = $this->getModel();

		$this->connectionError = $this->container->segment->getFlash('site_connection_error', null);
		$this->httpCode = $this->container->segment->getFlash('site_connection_http_code', null);
		$this->curlError = $this->container->segment->getFlash('site_connection_curl_error', null);

		return parent::onBeforeEdit();
	}
}