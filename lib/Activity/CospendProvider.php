<?php
/**
 * @copyright Copyright (c) 2019 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Cospend\Activity;

use Exception;
use InvalidArgumentException;

use OCA\Cospend\AppInfo\Application;
use OCA\Cospend\Service\ProjectService;
use OCP\Activity\IEvent;
use OCP\Activity\IProvider;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\IGroupManager;
use OCP\IL10N;
use OCP\App\IAppManager;

class CospendProvider implements IProvider {

	/** @var string */
	private $userId;
	/** @var IURLGenerator */
	private $urlGenerator;
	/** @var ActivityManager */
	private $activityManager;
	/** @var IUserManager */
	private $userManager;
	/** @var IL10N */
	private $l10n;
	/** @var IConfig */
	private $config;
	/**
	 * @var IGroupManager
	 */
	private $groupManager;
	/**
	 * @var IAppManager
	 */
	private $appManager;
	/**
	 * @var array
	 */
	private $projectNames;

	public function __construct(IURLGenerator $urlGenerator,
								ActivityManager $activityManager,
								IUserManager $userManager,
								IGroupManager $groupManager,
								IAppManager $appManager,
								IL10N $l10n,
								IConfig $config,
								ProjectService $projectService,
								?string $userId) {
		$this->userId = $userId;
		$this->urlGenerator = $urlGenerator;
		$this->activityManager = $activityManager;
		$this->userManager = $userManager;
		$this->l10n = $l10n;
		$this->config = $config;
		$this->groupManager = $groupManager;
		$this->appManager = $appManager;
		$this->projectNames = [];
		if (!is_null($userId)) {
			$this->projectNames = $projectService->getProjectNames($userId);
		}
	}

	/**
	 * @param string $language The language which should be used for translating, e.g. "en"
	 * @param IEvent $event The current event which should be parsed
	 * @param IEvent|null $previousEvent A potential previous event which you can combine with the current one.
	 *                                   To do so, simply use setChildEvent($previousEvent) after setting the
	 *                                   combined subject on the current event.
	 * @return IEvent
	 * @throws InvalidArgumentException Should be thrown if your provider does not know this event
	 * @since 11.0.0
	 */
	public function parse($language, IEvent $event, IEvent $previousEvent = null): IEvent {
		if ($event->getApp() !== 'cospend') {
			throw new InvalidArgumentException();
		}

		$event = $this->getIcon($event);

		$subjectIdentifier = $event->getSubject();
		$subjectParams = $event->getSubjectParameters();
		$ownActivity = ($event->getAuthor() === $this->userId);

		/**
		 * Map stored parameter objects to rich string types
		 */
		$params = [];

		$author = $event->getAuthor();
		// get author if
		if ($author === '' && array_key_exists('author', $subjectParams)) {
			$author = $subjectParams['author'];
			$params = [
				'user' => [
					'type' => 'user',
					'id' => 0,
					'name' => $subjectParams['author']
				],
			];
			unset($subjectParams['author']);
		}
		$user = $this->userManager->get($author);
		if ($user !== null) {
			$params = [
				'user' => [
					'type' => 'user',
					'id' => $author,
					'name' => $user->getDisplayName()
				],
			];
			$event->setAuthor($author);
		}
		if ($event->getObjectType() === ActivityManager::COSPEND_OBJECT_PROJECT) {
			if (isset($subjectParams['project']) && $event->getObjectName() === '') {
				$event->setObject($event->getObjectType(), $event->getObjectId(), $subjectParams['project']['name']);
			}
			$project = [
				'type' => 'highlight',
				'id' => $event->getObjectId(),
				'name' => $event->getObjectName(),
				'link' => $this->cospendUrl('/project/' . $event->getObjectId()),
			];
			$params['project'] = $project;
		}

		if (isset($subjectParams['bill']) && $event->getObjectType() === ActivityManager::COSPEND_OBJECT_BILL) {
			if ($event->getObjectName() === '') {
				$event->setObject($event->getObjectType(), $event->getObjectId(), $subjectParams['bill']['name']);
			}
			$bill = [
				'type' => 'highlight',
				'id' => $event->getObjectId(),
				'name' => $event->getObjectName(),
			];

			if (array_key_exists('project', $subjectParams)) {
				$bill['link'] = $this->cospendUrl('/project/' . $subjectParams['project']['id']);
			}
			$params['bill'] = $bill;
		}

		$params = $this->parseParamForProject('project', $subjectParams, $params);
		$params = $this->parseParamForBill('bill', $subjectParams, $params);
		$params = $this->parseParamForWho($subjectParams, $params);

		$event->setLink($subjectIdentifier);

		try {
			$subject = $this->activityManager->getActivityFormat($subjectIdentifier, $subjectParams, $ownActivity);
			$this->setSubjects($event, $subject, $params);
		} catch (Exception $e) {
		}
		return $event;
	}

	/**
	 * @param IEvent $event
	 * @param string $subject
	 * @param array $parameters
	 */
	protected function setSubjects(IEvent $event, string $subject, array $parameters) {
		$placeholders = $replacements = $richParameters = [];
		foreach ($parameters as $placeholder => $parameter) {
			$placeholders[] = '{' . $placeholder . '}';
			if (is_array($parameter) && array_key_exists('name', $parameter)) {
				$replacements[] = $parameter['name'];
				$richParameters[$placeholder] = $parameter;
			} else {
				$replacements[] = '';
			}
		}

		$event->setParsedSubject(str_replace($placeholders, $replacements, $subject))
			->setRichSubject($subject, $richParameters);
		$event->setSubject($subject, $parameters);
	}

	private function getIcon(IEvent $event): IEvent {
		$theme = $this->config->getUserValue($this->userId, 'accessibility', 'theme');
		$green = ($theme === 'dark')
			? 'E9322D'
			: '46BA61';
		$red = ($theme === 'dark')
			? '46BA61'
			: 'E9322D';
		$event->setIcon(
			$this->urlGenerator->getAbsoluteURL(
				$this->urlGenerator->imagePath('cospend', 'app_black.svg')
			)
		);
		if (strpos($event->getSubject(), '_update') !== false) {
			$event->setIcon(
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->imagePath('core', 'actions/rename.svg')
				)
			);
		} elseif (strpos($event->getSubject(), '_create') !== false) {
			$event->setIcon(
				$this->urlGenerator->getAbsoluteURL('/index.php/svg/core/actions/add?color=' . $green)
			);
		} elseif (strpos($event->getSubject(), '_delete') !== false) {
			$event->setIcon(
				$this->urlGenerator->getAbsoluteURL('/index.php/svg/core/actions/delete?color=' . $red)
			);
		} elseif ($event->getSubject() === 'project_share' || $event->getSubject() === 'project_unshare') {
			$event->setIcon(
				$this->urlGenerator->getAbsoluteURL(
					$this->urlGenerator->imagePath('core', 'actions/share.svg')
				)
			);
		}
		return $event;
	}

	private function parseParamForProject($paramName, $subjectParams, $params) {
		if (array_key_exists($paramName, $subjectParams)) {
			$params[$paramName] = [
				'type' => 'highlight',
				'id' => $subjectParams[$paramName]['id'],
				'name' => $this->projectNames[$subjectParams[$paramName]['id']] ?? $subjectParams[$paramName]['name'],
				'link' => $this->cospendUrl('?project=' . $subjectParams[$paramName]['id']),
			];
		}
		return $params;
	}
	private function parseParamForBill($paramName, $subjectParams, $params) {
		if (array_key_exists($paramName, $subjectParams)) {
			$params[$paramName] = [
				'type' => 'highlight',
				'id' => $subjectParams[$paramName]['id'],
				'name' => $subjectParams[$paramName]['name'].' ('.$subjectParams[$paramName]['amount'].')',
				'link' => $this->cospendUrl('?project=' . $subjectParams['project']['id']) . '&bill='.$subjectParams[$paramName]['id'],
			];
		}
		return $params;
	}

	private function parseParamForWho($subjectParams, $params) {
		if (array_key_exists('who', $subjectParams)) {
			if ($subjectParams['type'] === Application::SHARE_TYPE_USER) {
				$user = $this->userManager->get($subjectParams['who']);
				if ($user === null) {
					throw new InvalidArgumentException();
				}
				$params['who'] = [
					'type' => 'user',
					'id' => $subjectParams['who'],
					'name' => $user !== null ? $user->getDisplayName() : $subjectParams['who']
				];
			} elseif ($subjectParams['type'] === Application::SHARE_TYPE_GROUP) {
				$group = $this->groupManager->get($subjectParams['who']);
				if ($group === null) {
					throw new InvalidArgumentException();
				}
				$params['who'] = [
					'type' => 'highlight',
					'id' => $subjectParams['who'],
					'name' => $group !== null ? $group->getDisplayName() : $subjectParams['who']
				];
			} elseif ($subjectParams['type'] === Application::SHARE_TYPE_CIRCLE) {
				$displayName = $this->l10n->t('circle %1$s', [$subjectParams['who']]);
				$circlesEnabled = $this->appManager->isEnabledForUser('circles');
				if ($circlesEnabled) {
					$circlesManager = \OC::$server->get(\OCA\Circles\CirclesManager::class);
					$circlesManager->startSuperSession();
					try {
						$circle = $circlesManager->getCircle($subjectParams['who']);
						$circleName = $circle->getDisplayName();
						$displayName = $this->l10n->t('circle %1$s', [$circleName]);
					} catch (\OCA\Circles\Exceptions\CircleNotFoundException $e) {
						throw new InvalidArgumentException();
					}
					$circlesManager->stopSession();
				}
				$params['who'] = [
					'type' => 'highlight',
					'id' => $subjectParams['who'],
					'name' => $displayName,
					// 'link' => \OCA\Circles\Api\v1\Circles::generateAbsoluteLink($subjectParams['who'])
				];
			}
		}
		return $params;
	}

	public function cospendUrl($endpoint): string {
		return $this->urlGenerator->linkToRouteAbsolute('cospend.page.index') . $endpoint;
	}
}
