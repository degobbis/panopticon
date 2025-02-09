<?php
/**
 * @package   panopticon
 * @copyright Copyright (c)2023-2023 Nicholas K. Dionysopoulos / Akeeba Ltd
 * @license   https://www.gnu.org/licenses/agpl-3.0.txt GNU Affero General Public License, version 3 or later
 */

namespace Akeeba\Panopticon\Controller;

defined('AKEEBA') || die;

use Akeeba\Panopticon\Controller\Trait\ACLTrait;
use Awf\Mvc\DataController;
use Awf\Text\Text;
use Awf\Utils\ArrayHelper;
use RuntimeException;

class Users extends DataController
{
	use ACLTrait;

	public function execute($task)
	{
		$this->aclCheck($task);

		return parent::execute($task);
	}

	protected function onBeforeRead()
	{
		// If I am a superuser I can see any user account
		$mySelf = $this->container->userManager->getUser();

		$model = $this->getModel();

		// If there is no record loaded, try loading a record based on the id passed in the input object
		if (!$model->getId())
		{
			$ids = $this->getIDsFromRequest($model, true);

			// No ID in the request? Force it to the current user, to make things simpler.
			if (empty($ids))
			{
				$this->input->set('id', $mySelf->getId());
				$ids = [$mySelf->getId()];
				$model->find($mySelf->getId());
			}

			if ($model->getId() != reset($ids))
			{
				return false;
			}
		}

		if ($mySelf->getPrivilege('panopticon.super'))
		{
			return true;
		}

		// If I am not a superuser I can only read my own record
		return $model->getId() == $mySelf->getId();
	}

	protected function applySave()
	{
		$id = $this->input->getInt('id', 0);

		$this->layout ??= 'form';

		// Get the user objects. Myself doing the saving, and the user being saved
		$myself        = $this->container->userManager->getUser();
		$savedUser     = $this->container->userManager->getUser($id);
		$isNewUser     = empty($id) || ($savedUser->getId() != $id);
		$editingMyself = $savedUser->getId() == $myself->getId();

		// Get the applicable data
		$data = [
			'id'          => $id, 'username' => trim($this->input->post->getUsername('username', '')),
			'name'        => trim($this->input->post->getString('name', '')),
			'email'       => trim($this->input->post->get('email', '', 'raw')),
			'password'    => $this->input->post->get('password', '', 'raw'),
			'password2'   => $this->input->post->get('password2', '', 'raw'),
			'groups'      => array_filter(ArrayHelper::toInteger($this->input->post->get('groups', [], 'raw'))),
			'permissions' => array_keys($this->input->post->get('permissions', [], 'raw')),
		];

		if (!$myself->getPrivilege('panopticon.super'))
		{
			$data['username']    = $savedUser->getUsername();
			$data['groups']      = $savedUser->getParameters()->get('usergroups', []);
			$data['permissions'] = [];
		}

		try
		{
			if (method_exists($this, 'onBeforeApplySave'))
			{
				$this->onBeforeApplySave($data);
			}

			// If I am a superuser I can change the username.
			if ($myself->getPrivilege('panopticon.super'))
			{
				// Do I even have a username?
				$username = $data['username'];

				if (empty($username))
				{
					throw new RuntimeException(Text::_('PANOPTICON_SETUP_ERR_USER_EMPTYUSERNAME'), 403);
				}

				// Is there another user by the same username?
				if ($savedUser->getUsername() !== $username && $this->container->userManager->getUserByUsername($username) !== null)
				{
					throw new RuntimeException(Text::sprintf('PANOPTICON_USERS_ERR_USERNAME_EXISTS', htmlentities($username)), 403);
				}

				$savedUser->setUsername($username);
			}

			// Change or set the password, if necessary
			$password  = $data['password'];
			$password2 = $data['password2'];

			if ($isNewUser || !empty($password) || !empty($password2))
			{
				$emptyPassword  = empty($password);
				$emptyPassword2 = empty($password2);
				$passwordsMatch = $password === $password2;

				if ($isNewUser && ($emptyPassword || $emptyPassword2))
				{
					throw new RuntimeException(Text::_('PANOPTICON_USERS_ERR_NEEDS_PASSWORD'), 403);
				}
				elseif (!$passwordsMatch)
				{
					throw new RuntimeException(Text::_('PANOPTICON_USERS_ERR_PASSWORD_MISMATCH'), 403);
				}

				$savedUser->setPassword($password);
			}

			// Set the Full Name
			$name = $data['name'];

			if (empty($name))
			{
				throw new RuntimeException(Text::_('PANOPTICON_USERS_ERR_EMPTY_NAME'), 403);
			}

			$savedUser->setName($name);

			// Set the Email address
			$email         = $data['email'];
			$validishEmail = filter_var($email, FILTER_VALIDATE_EMAIL) !== false;

			if (!$validishEmail)
			{
				$this->container->application->enqueueMessage(Text::sprintf('PANOPTICON_USERS_ERR_INVALID_EMAIL', htmlentities($email)), 'warning');
			}

			$savedUser->setEmail($email);

			// If I am a superuser I can change the groups
			if ($myself->getPrivilege('panopticon.super'))
			{
				$savedUser->getParameters()->set('usergroups', $data['groups']);
			}

			// If I am a superuser I can change privileges
			if ($myself->getPrivilege('panopticon.super'))
			{
				$permissions = $data['permissions'];

				if ($editingMyself && $myself->getPrivilege('panopticon.super') && !in_array('panopticon.super', $permissions))
				{
					throw new RuntimeException(Text::_('PANOPTICON_USERS_ERR_CANT_REMOVE_SELF_SUPER'), 403);
				}

				foreach (['super', 'admin', 'view', 'run', 'addown', 'editown'] as $k)
				{
					$savedUser->setPrivilege('panopticon.' . $k, false);
				}

				foreach ($permissions as $k)
				{
					$savedUser->setPrivilege($k, true);
				}
			}

			$this->container->userManager->saveUser($savedUser);

			$status = true;

			if (method_exists($this, 'onAfterApplySave'))
			{
				$this->onAfterApplySave($data);
			}
		}
		catch (RuntimeException $e)
		{
			$status = false;
			$error  = $e->getMessage();
		}

		if (!$status)
		{
			// Cache the item data in the session. We may need to reuse them if the save fails.
			$sessionKey = $this->container->application_name . '_' . $this->viewName;
			$this->container->segment->setFlash($sessionKey, $data);

			// Redirect on error
			if ($customURL = $this->input->getBase64('returnurl', ''))
			{
				$customURL = base64_decode($customURL);
			}

			$router = $this->container->router;

			if (!empty($customURL))
			{
				$url = $customURL;
			}
			elseif ($id != 0)
			{
				$url = $router->route('index.php?view=' . $this->view . '&task=edit&id=' . $id);
			}
			else
			{
				$url = $router->route('index.php?view=' . $this->view . '&task=add');
			}

			$this->setRedirect($url, $error, 'error');
		}

		return $status;
	}

}