<?php
/**
 * @package   panopticon
 * @copyright Copyright (c)2023-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Panopticon\Model;

defined('AKEEBA') || die;

use Akeeba\Panopticon\Factory;
use Awf\Registry\Registry;

trait UserAvatarTrait
{
	public function getAvatar(int $size = 32): string
	{
		$defaultGravatar = sprintf(
			'https://www.gravatar.com/avatar/%s?d=mp&s=%d',
			md5(strtolower(trim($this->email))),
			max(1, min(2048, $size))
		);

		$params = $this->parameters instanceof Registry ? $this->parameters : new Registry($this->parameters);

		$results = Factory::getContainer()
			->eventDispatcher
			->trigger('onUserAvatar', [$this->id, $this->email, $params]);

		$avatarFromPlugin = array_reduce(
			$results,
			fn($carry, $x) => $carry ?? ((!empty(trim($x)) && is_string($x)) ? trim($x) : null),
			null
		);

		return $avatarFromPlugin ?? $defaultGravatar;
	}

	public function getAvatarEditUrl(): ?string
	{
		$defaultGravatar = sprintf(
			'https://www.gravatar.com/%s',
			md5(strtolower(trim($this->email))),
		);

		$params = $this->parameters instanceof Registry ? $this->parameters : new Registry($this->parameters);

		$results = Factory::getContainer()
			->eventDispatcher
			->trigger('onUserAvatarEditURL', [$this->id, $this->email, $params]);

		$avatarFromPlugin = array_reduce(
			$results,
			fn($carry, $x) => $carry ?? (is_string($x) ? trim($x) : null),
			null
		);

		return $avatarFromPlugin ?? $defaultGravatar;
	}
}