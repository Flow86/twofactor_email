<?php
declare(strict_types=1);
/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Roeland Jago Douma <roeland@famdouma.nl>
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\TwoFactorEmail\Controller;

use OCA\TwoFactorEmail\Service\SetupService;

use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\JSONResponse;
use OCP\IRequest;
use OCP\IUserSession;

class SettingsController extends Controller {

	/** @var IUserSession */
	private $userSession;

	/** @var SetupService */
	private $setupService;
    
	public function __construct(IRequest $request,
								IUserSession $userSession,
								SetupService $setupService) {
		parent::__construct('twofactor_email', $request);

		$this->userSession = $userSession;
		$this->setupService = $setupService;
	}

	/**
	 * @NoAdminRequired
	 */
	public function getVerificationState(): JSONResponse {
		$user = $this->userSession->getUser();

		if (is_null($user)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}
        
        if (is_null($user->getEMailAddress())) {
			return new JSONResponse(null, Http::STATUS_SERVICE_UNAVAILABLE);
        }
        
        return new JSONResponse($this->setupService->getState($user));
	}

	/**
	 * @NoAdminRequired
	 */
	public function startVerification(): JSONResponse {
		$user = $this->userSession->getUser();

		if (is_null($user)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		$state = $this->setupService->startSetup($user);

        return new JSONResponse($this->setupService->getState($user));
	}

	/**
	 * @NoAdminRequired
     *
     * @param string $verificationCode
     *
	 */
	public function finishVerification(string $verificationCode): JSONResponse {
		$user = $this->userSession->getUser();

		if (is_null($user)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		try {
			$this->setupService->finishSetup($user, $verificationCode);
		} catch (VerificationException $ex) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse([]);
	}

	/**
	 * @NoAdminRequired
	 */
	public function revokeVerification(): JSONResponse {
		$user = $this->userSession->getUser();

		if (is_null($user)) {
			return new JSONResponse(null, Http::STATUS_BAD_REQUEST);
		}

		return new JSONResponse($this->setupService->disable($user));
	}

}
