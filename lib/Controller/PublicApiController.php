<?php
/**
 * Nextcloud - cospend
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the COPYING file.
 *
 * @author Julien Veyssier <julien-nc@posteo.net>
 * @copyright Julien Veyssier 2023
 */

namespace OCA\Cospend\Controller;

use DateTime;
use OCA\Cospend\Activity\ActivityManager;
use OCA\Cospend\AppInfo\Application;
use OCA\Cospend\Attribute\CospendPublicAuth;
use OCA\Cospend\Db\BillMapper;
use OCA\Cospend\ResponseDefinitions;
use OCA\Cospend\Service\ProjectService;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\BruteForceProtection;
use OCP\AppFramework\Http\Attribute\CORS;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;

use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Attribute\PublicPage;
use OCP\AppFramework\Http\DataResponse;

use OCP\AppFramework\OCSController;
use OCP\DB\Exception;
use OCP\IL10N;
use OCP\IRequest;

/**
 * @psalm-import-type CospendBill from ResponseDefinitions
 * @psalm-import-type CospendFullPublicProjectInfo from ResponseDefinitions
 * @psalm-import-type CospendProjectSettlement from ResponseDefinitions
 * @psalm-import-type CospendProjectStatistics from ResponseDefinitions
 * @psalm-import-type CospendMember from ResponseDefinitions
 * @psalm-import-type CospendCurrency from ResponseDefinitions
 * @psalm-import-type CospendPaymentMode from ResponseDefinitions
 * @psalm-import-type CospendCategory from ResponseDefinitions
 */
class PublicApiController extends OCSController {

	public function __construct(
		string $appName,
		IRequest $request,
		private IL10N $trans,
		private BillMapper $billMapper,
		private ProjectService $projectService,
		private ActivityManager $activityManager,
	) {
		parent::__construct($appName, $request, 'PUT, POST, GET, DELETE, PATCH, OPTIONS');
	}

	/**
	 * Delete a project
	 *
	 * @param string $token
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_ADMIN)]
	#[BruteForceProtection(action: 'CospendPublicDeleteProject')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Projects'])]
	public function publicDeleteProject(string $token): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->deleteProject($publicShareInfo['projectid']);
		if (!isset($result['error'])) {
			return new DataResponse($result);
		} else {
			return new DataResponse(['message' => $result['error']], Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Clear the trashbin
	 *
	 * @param string $token
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST, '', array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicClearTrashbin')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Projects'])]
	public function publicClearTrashbin(string $token): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		try {
			$this->billMapper->deleteDeletedBills($publicShareInfo['projectid']);
			return new DataResponse('');
		} catch (\Exception | \Throwable $e) {
			return new DataResponse('', Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Delete a bill
	 *
	 * @param string $token
	 * @param int $billId
	 * @param bool $moveToTrash
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND|Http::STATUS_BAD_REQUEST, '', array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicDeleteBill')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicDeleteBill(string $token, int $billId, bool $moveToTrash = true): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$billObj = null;
		if ($this->billMapper->getBill($publicShareInfo['projectid'], $billId) !== null) {
			$billObj = $this->billMapper->find($billId);
		}

		$result = $this->projectService->deleteBill($publicShareInfo['projectid'], $billId, false, $moveToTrash);
		if (isset($result['success'])) {
			if (!is_null($billObj)) {
				if (is_null($publicShareInfo)) {
					$authorFullText = $this->trans->t('Guest access');
				} elseif ($publicShareInfo['label']) {
					$authorName = $publicShareInfo['label'];
					$authorFullText = $this->trans->t('Share link (%s)', [$authorName]);
				} else {
					$authorFullText = $this->trans->t('Share link');
				}
				$this->activityManager->triggerEvent(
					ActivityManager::COSPEND_OBJECT_BILL, $billObj,
					ActivityManager::SUBJECT_BILL_DELETE,
					['author' => $authorFullText]
				);
			}
			return new DataResponse('');
		} elseif (isset($result['message'])) {
			if ($result['message'] === 'forbidden') {
				return new DataResponse('', Http::STATUS_FORBIDDEN);
			} elseif ($result['message'] === 'not found') {
				return new DataResponse('', Http::STATUS_NOT_FOUND);
			}
		}
		return new DataResponse('', Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Delete multiple bills
	 *
	 * @param string $token
	 * @param array<int> $billIds
	 * @param bool $moveToTrash
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_BAD_REQUEST|Http::STATUS_FORBIDDEN|Http::STATUS_NOT_FOUND, '', array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicDeleteBills')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicDeleteBills(string $token, array $billIds, bool $moveToTrash = true): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		if (is_null($publicShareInfo)) {
			$authorFullText = $this->trans->t('Guest access');
		} elseif ($publicShareInfo['label']) {
			$authorName = $publicShareInfo['label'];
			$authorFullText = $this->trans->t('Share link (%s)', [$authorName]);
		} else {
			$authorFullText = $this->trans->t('Share link');
		}
		foreach ($billIds as $billId) {
			if ($this->billMapper->getBill($publicShareInfo['projectid'], $billId) === null) {
				return new DataResponse('', Http::STATUS_NOT_FOUND);
			}
		}

		foreach ($billIds as $billId) {
			$billObj = $this->billMapper->find($billId);
			$result = $this->projectService->deleteBill($publicShareInfo['projectid'], $billId, false, $moveToTrash);
			if (!isset($result['success'])) {
				if (isset($result['message'])) {
					if ($result['message'] === 'forbidden') {
						return new DataResponse('', Http::STATUS_FORBIDDEN);
					} elseif ($result['message'] === 'not found') {
						return new DataResponse('', Http::STATUS_NOT_FOUND);
					}
				}
				return new DataResponse('', Http::STATUS_BAD_REQUEST);
			} else {
				$this->activityManager->triggerEvent(
					ActivityManager::COSPEND_OBJECT_BILL, $billObj,
					ActivityManager::SUBJECT_BILL_DELETE,
					['author' => $authorFullText]
				);
			}
		}
		return new DataResponse('');
	}

	/**
	 * Get project information
	 *
	 * @param string $token
	 * @return DataResponse<Http::STATUS_OK, CospendFullPublicProjectInfo, array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{message: string}, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_VIEWER)]
	#[BruteForceProtection(action: 'CospendPublicProjectInfo')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Projects'])]
	public function publicGetProjectInfo(string $token): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$projectInfo = $this->projectService->getProjectInfo($publicShareInfo['projectid']);
		if ($projectInfo !== null) {
			unset($projectInfo['userid']);
			// set the visible access level for frontend
			$projectInfo['myaccesslevel'] = $publicShareInfo['accesslevel'];
			return new DataResponse($projectInfo);
		}
		return new DataResponse(
			['message' => $this->trans->t('Project not found')],
			Http::STATUS_NOT_FOUND
		);
	}

	/**
	 * Get statistics data
	 *
	 * @param string $token
	 * @param int|null $tsMin
	 * @param int|null $tsMax
	 * @param int|null $paymentModeId
	 * @param int|null $categoryId
	 * @param float|null $amountMin
	 * @param float|null $amountMax
	 * @param string $showDisabled
	 * @param int|null $currencyId
	 * @param int|null $payerId
	 * @return DataResponse<Http::STATUS_OK, CospendProjectStatistics, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_VIEWER)]
	#[BruteForceProtection(action: 'CospendPublicGetStats')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Projects'])]
	public function publicGetProjectStatistics(
		string $token, ?int $tsMin = null, ?int $tsMax = null,
		?int   $paymentModeId = null, ?int $categoryId = null,
		?float $amountMin = null, ?float $amountMax = null,
		string $showDisabled = '1', ?int $currencyId = null, ?int $payerId = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->getProjectStatistics(
			$publicShareInfo['projectid'], 'lowername', $tsMin, $tsMax,
			$paymentModeId, $categoryId, $amountMin, $amountMax, $showDisabled === '1', $currencyId,
			$payerId
		);
		return new DataResponse($result);
	}

	/**
	 * Get project settlement info
	 *
	 * @param string $token
	 * @param int|null $centeredOn
	 * @param int|null $maxTimestamp
	 * @return DataResponse<Http::STATUS_OK, CospendProjectSettlement, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_VIEWER)]
	#[BruteForceProtection(action: 'CospendPublicGetSettlement')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Projects'])]
	public function publicGetProjectSettlement(string $token, ?int $centeredOn = null, ?int $maxTimestamp = null): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->getProjectSettlement(
			$publicShareInfo['projectid'], $centeredOn, $maxTimestamp
		);
		return new DataResponse($result);
	}

	/**
	 * Get automatic settlement plan
	 *
	 * @param string $token
	 * @param int|null $centeredOn
	 * @param int $precision
	 * @param int|null $maxTimestamp
	 * @return DataResponse<Http::STATUS_OK, '', array{}>|DataResponse<Http::STATUS_FORBIDDEN, array{message: string}, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicAutoSettlement')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Projects'])]
	public function publicAutoSettlement(
		string $token, ?int $centeredOn = null, int $precision = 2, ?int $maxTimestamp = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->autoSettlement(
			$publicShareInfo['projectid'], $centeredOn, $precision, $maxTimestamp
		);
		if (isset($result['success'])) {
			return new DataResponse('');
		} else {
			return new DataResponse(['message' => $result['message']], Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Edit a bill
	 *
	 * @param string $token
	 * @param int $billId
	 * @param string|null $date
	 * @param string|null $what
	 * @param int|null $payer
	 * @param string|null $payed_for
	 * @param float|null $amount
	 * @param string $repeat
	 * @param string|null $paymentmode
	 * @param int|null $paymentmodeid
	 * @param int|null $categoryid
	 * @param int|null $repeatallactive
	 * @param string|null $repeatuntil
	 * @param int|null $timestamp
	 * @param string|null $comment
	 * @param int|null $repeatfreq
	 * @param int|null $deleted
	 * @return DataResponse<Http::STATUS_OK, int, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicEditBill')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicEditBill(
		string $token, int $billId, ?string $date = null, ?string $what = null,
		?int $payer = null, ?string $payed_for = null, ?float $amount = null, string $repeat = 'n',
		?string $paymentmode = null, ?int $paymentmodeid = null,
		?int $categoryid = null, ?int $repeatallactive = null,
		?string $repeatuntil = null, ?int $timestamp = null, ?string $comment = null,
		?int $repeatfreq = null, ?int $deleted = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->editBill(
			$publicShareInfo['projectid'], $billId, $date, $what, $payer, $payed_for,
			$amount, $repeat, $paymentmode, $paymentmodeid, $categoryid,
			$repeatallactive, $repeatuntil, $timestamp, $comment, $repeatfreq, null, $deleted
		);
		if (isset($result['edited_bill_id'])) {
			$billObj = $this->billMapper->find($billId);
			if (is_null($publicShareInfo)) {
				$authorFullText = $this->trans->t('Guest access');
			} elseif ($publicShareInfo['label']) {
				$authorName = $publicShareInfo['label'];
				$authorFullText = $this->trans->t('Share link (%s)', [$authorName]);
			} else {
				$authorFullText = $this->trans->t('Share link');
			}
			$this->activityManager->triggerEvent(
				ActivityManager::COSPEND_OBJECT_BILL, $billObj,
				ActivityManager::SUBJECT_BILL_UPDATE,
				['author' => $authorFullText]
			);

			return new DataResponse($result['edited_bill_id']);
		} else {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Edit multiple bills
	 *
	 * @param string $token
	 * @param array<int> $billIds
	 * @param int|null $categoryid
	 * @param string|null $date
	 * @param string|null $what
	 * @param int|null $payer
	 * @param string|null $payed_for
	 * @param float|null $amount
	 * @param string|null $repeat
	 * @param string|null $paymentmode
	 * @param int|null $paymentmodeid
	 * @param int|null $repeatallactive
	 * @param string|null $repeatuntil
	 * @param int|null $timestamp
	 * @param string|null $comment
	 * @param int|null $repeatfreq
	 * @param int|null $deleted
	 * @return DataResponse<Http::STATUS_OK, int[], array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicEditBills')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicEditBills(
		string $token, array $billIds, ?int $categoryid = null, ?string $date = null,
		?string $what = null, ?int $payer = null, ?string $payed_for = null, ?float $amount = null,
		?string $repeat = 'n', ?string $paymentmode = null, ?int $paymentmodeid = null,
		?int $repeatallactive = null,
		?string $repeatuntil = null, ?int $timestamp = null, ?string $comment = null,
		?int $repeatfreq = null, ?int $deleted = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		if (is_null($publicShareInfo)) {
			$authorFullText = $this->trans->t('Guest access');
		} elseif ($publicShareInfo['label']) {
			$authorName = $publicShareInfo['label'];
			$authorFullText = $this->trans->t('Share link (%s)', [$authorName]);
		} else {
			$authorFullText = $this->trans->t('Share link');
		}
		$paymentModes = $this->projectService->getCategoriesOrPaymentModes($publicShareInfo['projectid'], false);
		foreach ($billIds as $billid) {
			$result = $this->projectService->editBill(
				$publicShareInfo['projectid'], $billid, $date, $what, $payer, $payed_for,
				$amount, $repeat, $paymentmode, $paymentmodeid, $categoryid,
				$repeatallactive, $repeatuntil, $timestamp, $comment, $repeatfreq, $paymentModes, $deleted
			);
			if (isset($result['edited_bill_id'])) {
				$billObj = $this->billMapper->find($billid);
				$this->activityManager->triggerEvent(
					ActivityManager::COSPEND_OBJECT_BILL, $billObj,
					ActivityManager::SUBJECT_BILL_UPDATE,
					['author' => $authorFullText]
				);
			} else {
				return new DataResponse($result, Http::STATUS_BAD_REQUEST);
			}
		}
		return new DataResponse($billIds);
	}

	/**
	 * Trigger bill repetition for a specific bill
	 *
	 * @param string $token
	 * @param int $billId
	 * @return DataResponse<Http::STATUS_OK, array<array{new_bill_id: int, date_orig: string, date_repeat: string, what: string, project_name: string}>, array{}>|DataResponse<Http::STATUS_NOT_FOUND, '', array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicRepeatBill')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicRepeatBill(string $token, int $billId): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$bill = $this->billMapper->getBill($publicShareInfo['projectid'], $billId);
		if ($bill === null) {
			return new DataResponse('', Http::STATUS_NOT_FOUND);
		}
		$result = $this->projectService->cronRepeatBills($billId);
		return new DataResponse($result);
	}

	/**
	 * Edit a project
	 *
	 * @param string $token
	 * @param string|null $name
	 * @param string|null $contact_email
	 * @param string|null $autoexport
	 * @param string|null $currencyname
	 * @param bool|null $deletion_disabled
	 * @param string|null $categorysort
	 * @param string|null $paymentmodesort
	 * @return DataResponse<Http::STATUS_OK, '', array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_ADMIN)]
	#[BruteForceProtection(action: 'CospendPublicEditProject')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Projects'])]
	public function publicEditProject(
		string $token, ?string $name = null, ?string $contact_email = null,
		?string $autoexport = null, ?string $currencyname = null,
		?bool $deletion_disabled = null, ?string $categorysort = null, ?string $paymentmodesort = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->editProject(
			$publicShareInfo['projectid'], $name, $contact_email, $autoexport,
			$currencyname, $deletion_disabled, $categorysort, $paymentmodesort
		);
		if (isset($result['success'])) {
			return new DataResponse('');
		} else {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Create a bill
	 *
	 * @param string $token
	 * @param string|null $date
	 * @param string|null $what
	 * @param int|null $payer
	 * @param string|null $payed_for
	 * @param float|null $amount
	 * @param string $repeat
	 * @param string|null $paymentmode
	 * @param int|null $paymentmodeid
	 * @param int|null $categoryid
	 * @param int $repeatallactive
	 * @param string|null $repeatuntil
	 * @param int|null $timestamp
	 * @param string|null $comment
	 * @param int|null $repeatfreq
	 * @return DataResponse<Http::STATUS_OK, int, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array{error: array<string, string>}, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_PARTICIPANT)]
	#[BruteForceProtection(action: 'CospendPublicCreateBill')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicCreateBill(
		string $token, ?string $date = null, ?string $what = null, ?int $payer = null,
		?string $payed_for = null, ?float $amount = null, string $repeat = 'n',
		?string $paymentmode = null, ?int $paymentmodeid = null,
		?int $categoryid = null, int $repeatallactive = 0, ?string $repeatuntil = null, ?int $timestamp = null,
		?string $comment = null, ?int $repeatfreq = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->createBill(
			$publicShareInfo['projectid'], $date, $what, $payer, $payed_for, $amount,
			$repeat, $paymentmode, $paymentmodeid, $categoryid, $repeatallactive,
			$repeatuntil, $timestamp, $comment, $repeatfreq
		);
		if (isset($result['inserted_id'])) {
			$billObj = $this->billMapper->find($result['inserted_id']);
			if (is_null($publicShareInfo)) {
				$authorFullText = $this->trans->t('Guest access');
			} elseif ($publicShareInfo['label']) {
				$authorName = $publicShareInfo['label'];
				$authorFullText = $this->trans->t('Share link (%s)', [$authorName]);
			} else {
				$authorFullText = $this->trans->t('Share link');
			}
			$this->activityManager->triggerEvent(
				ActivityManager::COSPEND_OBJECT_BILL, $billObj,
				ActivityManager::SUBJECT_BILL_CREATE,
				['author' => $authorFullText]
			);
			return new DataResponse($result['inserted_id']);
		} else {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Get a project's bill list
	 *
	 * @param string $token
	 * @param int|null $lastchanged
	 * @param int|null $offset
	 * @param int|null $limit
	 * @param bool $reverse
	 * @param int|null $payerId
	 * @param int|null $categoryId
	 * @param int|null $paymentModeId
	 * @param int|null $includeBillId
	 * @param string|null $searchTerm
	 * @param int|null $deleted
	 * @return DataResponse<Http::STATUS_OK, array{nb_bills: int, allBillIds: int[], timestamp: int, bills: CospendBill[]}, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_VIEWER)]
	#[BruteForceProtection(action: 'CospendPublicGetBills')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicGetBills(
		string $token, ?int $lastchanged = null, ?int $offset = 0, ?int $limit = null, bool $reverse = false,
		?int $payerId = null, ?int $categoryId = null, ?int $paymentModeId = null, ?int $includeBillId = null,
		?string $searchTerm = null, ?int $deleted = 0
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		if ($limit) {
			$bills = $this->billMapper->getBillsWithLimit(
				$publicShareInfo['projectid'], null, null,
				null, $paymentModeId, $categoryId, null, null,
				$lastchanged, $limit, $reverse, $offset, $payerId, $includeBillId, $searchTerm, $deleted
			);
		} else {
			$bills = $this->billMapper->getBills(
				$publicShareInfo['projectid'], null, null,
				null, $paymentModeId, $categoryId, null, null,
				$lastchanged, null, $reverse, $payerId, $deleted
			);
		}
		$billIds = $this->billMapper->getAllBillIds($publicShareInfo['projectid'], $deleted);
		$ts = (new DateTime())->getTimestamp();
		$result = [
			'nb_bills' => $this->billMapper->countBills(
				$publicShareInfo['projectid'], $payerId, $categoryId, $paymentModeId, $deleted
			),
			'bills' => $bills,
			'allBillIds' => $billIds,
			'timestamp' => $ts,
		];
		return new DataResponse($result);
	}

	/**
	 * @param string $token
	 * @param int $billId
	 * @return DataResponse<Http::STATUS_OK, CospendBill, array{}>|DataResponse<Http::STATUS_NOT_FOUND, '', array{}>
	 *
	 * 200: The bill was successfully obtained
	 * 404: The bill was not found
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_VIEWER)]
	#[BruteForceProtection(action: 'CospendPublicGetBills')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Bills'])]
	public function publicGetBill(string $token, int $billId): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$dbBillArray = $this->billMapper->getBill($publicShareInfo['projectid'], $billId);
		if ($dbBillArray === null) {
			return new DataResponse('', Http::STATUS_NOT_FOUND);
		}
		return new DataResponse($dbBillArray);
	}

	/**
	 * Get a project's member list
	 *
	 * @param string $token
	 * @param int|null $lastChanged
	 * @return DataResponse<Http::STATUS_OK, CospendMember[], array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_VIEWER)]
	#[BruteForceProtection(action: 'CospendPublicGetMembers')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Members'])]
	public function publicGetMembers(string $token, ?int $lastChanged = null): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$members = $this->projectService->getMembers($publicShareInfo['projectid'], null, $lastChanged);
		return new DataResponse($members);
	}

	/**
	 * Delete or disable a member
	 *
	 * @param string $token
	 * @param int $memberId
	 * @return DataResponse<Http::STATUS_OK, '', array{}>|DataResponse<Http::STATUS_NOT_FOUND, array{error: string}, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicDeleteMember')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Members'])]
	public function publicDeleteMember(string $token, int $memberId): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->deleteMember($publicShareInfo['projectid'], $memberId);
		if (isset($result['success'])) {
			return new DataResponse('');
		} else {
			return new DataResponse($result, Http::STATUS_NOT_FOUND);
		}
	}

	/**
	 * Edit a project member
	 *
	 * @param string $token
	 * @param int $memberId
	 * @param string|null $name
	 * @param float|null $weight
	 * @param null $activated
	 * @param string|null $color
	 * @param string|null $userid
	 * @return DataResponse<Http::STATUS_OK, null, array{}>|DataResponse<Http::STATUS_OK, CospendMember, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array<string, string>, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicEditMember')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Members'])]
	public function publicEditMember(
		string $token, int $memberId, ?string $name = null, ?float $weight = null,
		$activated = null, ?string $color = null, ?string $userid = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		if ($activated === 'true') {
			$activated = true;
		} elseif ($activated === 'false') {
			$activated = false;
		}
		$result = $this->projectService->editMember(
			$publicShareInfo['projectid'], $memberId, $name, $userid, $weight, $activated, $color
		);
		if (count($result) === 0) {
			return new DataResponse(null);
		} elseif (isset($result['activated'])) {
			return new DataResponse($result);
		} else {
			return new DataResponse($result, Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Create a project member
	 *
	 * @param string $token
	 * @param string $name
	 * @param float $weight
	 * @param int $active
	 * @param string|null $color
	 * @param string|null $userid
	 * @return DataResponse<Http::STATUS_OK, CospendMember, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, string, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicCreateMember')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Members'])]
	public function publicCreateMember(
		string  $token, string $name, float $weight = 1, int $active = 1,
		?string $color = null, ?string $userid = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->createMember(
			$publicShareInfo['projectid'], $name, $weight, $active !== 0, $color, $userid
		);
		if (!isset($result['error'])) {
			/** @var CospendMember $newMember */
			$newMember = $result;
			return new DataResponse($newMember);
		}
		return new DataResponse($result['error'], Http::STATUS_BAD_REQUEST);
	}

	/**
	 * Create a payment mode
	 *
	 * @param string $token
	 * @param string $name
	 * @param string|null $icon
	 * @param string $color
	 * @param int|null $order
	 * @return DataResponse<Http::STATUS_OK, int, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicCreatePaymentMode')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Payment modes'])]
	public function publicCreatePaymentMode(string $token, string $name, ?string $icon, string $color, ?int $order = 0): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->createPaymentMode(
			$publicShareInfo['projectid'], $name, $icon, $color, $order
		);
		return new DataResponse($result);
	}

	/**
	 * Edit a payment mode
	 *
	 * @param string $token
	 * @param int $pmId
	 * @param string|null $name
	 * @param string|null $icon
	 * @param string|null $color
	 * @return DataResponse<Http::STATUS_OK, CospendPaymentMode, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array<string, string>, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicEditPaymentMode')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Payment modes'])]
	public function publicEditPaymentMode(
		string $token, int $pmId, ?string $name = null, ?string $icon = null, ?string $color = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->editPaymentMode(
			$publicShareInfo['projectid'], $pmId, $name, $icon, $color
		);
		if (isset($result['name'])) {
			/** @var CospendPaymentMode $pm */
			$pm = $result;
			return new DataResponse($pm);
		} else {
			return new DataResponse($result, Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Save payment modes order
	 *
	 * @param string $token
	 * @param array<array{order: int, id: int}> $order
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_FORBIDDEN, '', array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicSavePMOrder')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Payment modes'])]
	public function publicSavePaymentModeOrder(string $token, array $order): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		if ($this->projectService->savePaymentModeOrder($publicShareInfo['projectid'], $order)) {
			return new DataResponse('');
		} else {
			return new DataResponse('', Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Delete a payment mode
	 *
	 * @param string $token
	 * @param int $pmId
	 * @return DataResponse<Http::STATUS_OK, int, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicDeletePM')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Payment modes'])]
	public function publicDeletePaymentMode(string $token, int $pmId): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->deletePaymentMode($publicShareInfo['projectid'], $pmId);
		if (isset($result['success'])) {
			return new DataResponse($pmId);
		} else {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Create a category
	 *
	 * @param string $token
	 * @param string $name
	 * @param string|null $icon
	 * @param string $color
	 * @param int|null $order
	 * @return DataResponse<Http::STATUS_OK, int, array{}>
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicCreateCat')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Categories'])]
	public function publicCreateCategory(string $token, string $name, ?string $icon, string $color, ?int $order = 0): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->createCategory(
			$publicShareInfo['projectid'], $name, $icon, $color, $order
		);
		return new DataResponse($result);
	}

	/**
	 * Edit a category
	 *
	 * @param string $token
	 * @param int $categoryId
	 * @param string|null $name
	 * @param string|null $icon
	 * @param string|null $color
	 * @return DataResponse<Http::STATUS_OK, CospendCategory, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicEditCat')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Categories'])]
	public function publicEditCategory(
		string  $token, int $categoryId,
		?string $name = null, ?string $icon = null, ?string $color = null
	): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->editCategory(
			$publicShareInfo['projectid'], $categoryId, $name, $icon, $color
		);
		if (isset($result['name'])) {
			return new DataResponse($result);
		} else {
			return new DataResponse($result, Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Save categories order
	 *
	 * @param string $token
	 * @param array<array{order: int, id: int}> $order
	 * @return DataResponse<Http::STATUS_OK|Http::STATUS_FORBIDDEN, '', array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicSaveCatOrder')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Categories'])]
	public function publicSaveCategoryOrder(string $token, array $order): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		if ($this->projectService->saveCategoryOrder($publicShareInfo['projectid'], $order)) {
			return new DataResponse('');
		} else {
			return new DataResponse('', Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Delete a category
	 *
	 * @param string $token
	 * @param int $categoryId
	 * @return DataResponse<Http::STATUS_OK, int, array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicDeleteCat')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Categories'])]
	public function publicDeleteCategory(string $token, int $categoryId): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->deleteCategory($publicShareInfo['projectid'], $categoryId);
		if (isset($result['success'])) {
			return new DataResponse($categoryId);
		} else {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
	}

	/**
	 * Create a currency
	 *
	 * @param string $token
	 * @param string $name
	 * @param float $rate
	 * @return DataResponse<Http::STATUS_OK, int, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicCreateCur')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Currencies'])]
	public function publicCreateCurrency(string $token, string $name, float $rate): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->createCurrency($publicShareInfo['projectid'], $name, $rate);
		return new DataResponse($result);
	}

	/**
	 * Edit a currency
	 *
	 * @param string $token
	 * @param int $currencyId
	 * @param string $name
	 * @param float $rate
	 * @return DataResponse<Http::STATUS_OK, CospendCurrency, array{}>|DataResponse<Http::STATUS_FORBIDDEN, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicEditCur')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Currencies'])]
	public function publicEditCurrency(string $token, int $currencyId, string $name, float $rate): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->editCurrency(
			$publicShareInfo['projectid'], $currencyId, $name, $rate
		);
		if (!isset($result['message'])) {
			return new DataResponse($result);
		} else {
			return new DataResponse($result, Http::STATUS_FORBIDDEN);
		}
	}

	/**
	 * Delete a currency
	 *
	 * @param string $token
	 * @param int $currencyId
	 * @return DataResponse<Http::STATUS_OK, '', array{}>|DataResponse<Http::STATUS_BAD_REQUEST, array<string, string>, array{}>
	 * @throws Exception
	 */
	#[NoAdminRequired]
	#[PublicPage]
	#[CORS]
	#[CospendPublicAuth(minimumLevel: Application::ACCESS_LEVEL_MAINTAINER)]
	#[BruteForceProtection(action: 'CospendPublicDeleteCur')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT, tags: ['[Public API] Currencies'])]
	public function publicDeleteCurrency(string $token, int $currencyId): DataResponse {
		$publicShareInfo = $this->projectService->getShareInfoFromShareToken($token);
		$result = $this->projectService->deleteCurrency($publicShareInfo['projectid'], $currencyId);
		if (isset($result['success'])) {
			return new DataResponse('');
		} else {
			return new DataResponse($result, Http::STATUS_BAD_REQUEST);
		}
	}
}
