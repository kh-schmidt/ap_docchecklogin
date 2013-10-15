<?php

namespace Antwerpes\ApDocchecklogin\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 antwerpes ag <opensource@antwerpes.de>
 *  All rights reserved
 *
 *  The TYPO3 Extension ap_docchecklogin is licensed under the MIT License
 *
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal
 *  in the Software without restriction, including without limitation the rights
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 *  copies of the Software, and to permit persons to whom the Software is
 *  furnished to do so, subject to the following conditions:
 *
 *  The above copyright notice and this permission notice shall be included in
 *  all copies or substantial portions of the Software.
 *
 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 *  IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 *  FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 *  AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 *  LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 *  OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 *  THE SOFTWARE.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * Plugin 'DocCheck Authentication' for the 'ap_docchecklogin' extension.
 *
 * @author	Lukas Domnick <lukas.domnick@antwerpes.de>
 */
class DocCheckAuthenticationController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Repository\FrontendUserRepository
	 * @inject
	 */
	protected $frontendUserRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Domain\Model\FrontendUser
	 */
	protected $feUser;

	public function initializeObject() {
		$this->initializeFeUser();
	}

	protected function initializeFeUser() {
		$userId = $GLOBALS['TSFE']->fe_user->user['uid'];
		$this->feUser = $this->frontendUserRepository->findByUid( $userId );
	}


	function mainAction() {
		// is logged in?
		if( $this->feUser ) {
			$this->forward('loggedIn');
		} else {
			// not logged in, do redirect the user
			$this->forward('loginForm');
		}
	}

	function loggedInAction() {
		// if the settings tell us to redirect on a successful login, do so now.
		if( $GLOBALS['ap_docchecklogin_do_redirect'] === true ) {

			// user configuration takes precedence
			$redirectToPid = $this->getUserRedirectPid();
			// only bother fetching the group redirect config if no user user-level config was found
			if( !$redirectToPid ) {
				$redirectToPid = $this->getGroupRedirectPid();
			}

			// reset the do_redirect flag
			$GLOBALS['ap_docchecklogin_do_redirect'] = false;

			// aight, so did we find a page id to redirect to?
			if( $redirectToPid ) {
				// this way works better than $this->redirect(), which will always add some bullshit params
				$redirectUri = $this->uriBuilder->reset()->setTargetPageUid($redirectToPid)->setCreateAbsoluteUri(TRUE)->build();
				$this->redirectToUri($redirectUri);
			}

			return;

		}
	}


	/**
	 * Tries to get a redirect configuration (Page ID) for the current user.
	 *
	 * @return int|null Page ID
	 */
	function getUserRedirectPid() {
		$redirectToPid = $GLOBALS['TSFE']->fe_user->user['felogin_redirectPid'];
		if( !$redirectToPid ){
			return null;
		}
		return $redirectToPid;
	}

	/**
	 * Tries to get a redirect configuration (Page ID) for the current user's primary group.
	 *
	 * @return int|null Page ID
	 */
	function getGroupRedirectPid() {
		$groupData = $GLOBALS['TSFE']->fe_user->groupData;
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'felogin_redirectPid',
			$GLOBALS['TSFE']->fe_user->usergroup_table,
			'felogin_redirectPid<>\'\' AND uid IN (' . implode(',', $groupData['uid']) . ')'
		);

		if ($row = $GLOBALS['TYPO3_DB']->sql_fetch_row($res)) {
			// take the first group with a redirect page
			return $row[0];
		}

		return null;
	}

	function loginFormAction() {
		// most settings are injected implicitly, but a custom login template must be checked briefly
		if( $this->settings['loginLayout'] === 'custom' ) {
			$templateKey = $this->settings['customLayout'];
		} else {
			$templateKey = $this->settings['loginLayout'] . '_red';
		}

		$this->view->assign('templateKey', $templateKey);
	}
}
