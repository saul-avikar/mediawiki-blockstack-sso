<?php
/**
 * This is the form used for specifying the wiki account to link to
 */

namespace BlockstackSso\Auth;

use MediaWiki\Auth\AuthenticationRequest;

class BlockstackServerAuthenticationRequest extends AuthenticationRequest {

	public function getFieldInfo() {
		global $wgRequest;
		return [
			'username' => [
				'type' => 'string',
				'label' => wfMessage( 'userlogin-yourname' ),
				'help' => wfMessage( 'blockstacklogin-username-help' ),
				'optional' => false,
			],
			'password' => [
				'type' => 'password',
				'label' => wfMessage( 'userlogin-yourpassword' ),
				'help' => wfMessage( 'blockstacklogin-password-help' ),
				'optional' => false,
			],
			'bsDid' => [
				'type' => 'hidden',
				'value' => $wgRequest->getText( 'bsDid' )
			],
		];
	}
}
