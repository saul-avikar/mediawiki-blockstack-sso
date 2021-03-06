<?php
use \BlockstackSso\BlockstackUser;

class BlockstackSso {

	public static $instance = null;
	
	/**
	 * Called when the extension is first loaded
	 */
	public static function onRegistration() {
		global $wgExtensionFunctions;
		self::$instance = new self();
		$wgExtensionFunctions[] = array( self::$instance, 'setup' );
	}

	/**
	 * Called at extension setup time, install hooks and module resources
	 */
	public function setup() {
		global $wgRequest, $wgGroupPermissions, $wgOut, $wgExtensionAssetsPath, $wgAutoloadClasses, $IP, $wgResourceModules;

		// Get script path accounting for symlinks
		$path = str_replace( "$IP/extensions", '', dirname( $wgAutoloadClasses[__CLASS__] ) );

		// Not using UnknownAction hook for these since we need to bypass permissions
		if( $wgRequest->getText('action') == 'blockstack-manifest' ) $this->returnManifest();
		if( $wgRequest->getText('action') == 'blockstack-validate' ) $this->returnValidation( $wgExtensionAssetsPath . $path );
		if( $wgRequest->getText('action') == 'blockstack-checkuser' ) $this->returnCheckuser( $wgRequest->getText('did') );

		// Include the common blockstack JS
		$wgResourceModules['ext.blockstackcommon']['localBasePath'] = __DIR__ . '/BlockstackCommon';
		$wgResourceModules['ext.blockstackcommon']['remoteExtPath'] = $path . '/BlockstackCommon';
		$wgOut->addModules( 'ext.blockstackcommon' );

		// Inlcude this extension's JS and CSS
		$wgResourceModules['ext.blockstacksso']['localBasePath'] = __DIR__ . '/modules';
		$wgResourceModules['ext.blockstacksso']['remoteExtPath'] = "$path/modules";
		$wgOut->addModules( 'ext.blockstacksso' );
		$wgOut->addStyle( "$path/styles/blockstacksso.css" );
		$wgOut->addJsConfigVars( 'blockstackManifestUrl', $this->manifestUrl() );
	}

	/**
	 * AuthChangeFormFields hook handler. Give the "Login with Blockstack" button a larger
	 * weight so that it shows below that password login button
	 */
	public static function onAuthChangeFormFields( array $requests, array $fieldInfo, array &$formDescriptor, $action ) {
		if ( isset( $formDescriptor['blockstacksso'] ) ) {
			$formDescriptor['blockstacksso'] = array_merge( $formDescriptor['blockstacksso'],
				[
					'weight' => 100,
					'flags' => [],
					'class' => \HTMLButtonField::class
				]
			);
			unset( $formDescriptor['blockstacksso']['type'] );
		}
	}

	private function returnCheckUser( $did ) {
		global $wgOut;
		$wgOut->disable();
		header( 'Content-Type: application/json' );
		$bsUser = BlockstackUser::newFromDid( $did );
		echo '{"id":' . $bsUser->getWikiUserId() . '}';
		self::restInPeace();
	}

	/**
	 * Return a JS page that validates a Blockstack response and POSTs the data to the login page
	 */
	private function returnValidation( $path ) {
		global $wgOut, $wgServer, $wgScript;

		// Supply the URL the final data should be posted to
		$data = 'window.script="' . $wgServer . $wgScript ."\";\n";

		// Add script headers to load our validation script and the blockstack JS
		$blockstack = "<script src=\"$path/BlockstackCommon/blockstack-common.min.js\"></script>\n";
		$validation = "<script src=\"$path/modules/validate.js\"></script>\n";

		// Output as a minimal HTML page and exit
		$wgOut->disable();
		$head = "<head>\n<title>Blockstack validation page</title>\n{$blockstack}{$validation}<script>\n{$data}</script>\n</head>\n";
		echo "<!DOCTYPE html>\n<html>\n$head<body onload=\"window.validate()\"></body>\n</html>\n";
		self::restInPeace();
	}

	/**
	 * Return the JSON manifest with the correct headers and exit
	 */
	private function returnManifest() {
		global $wgOut, $wgSitename, $wgServer, $wgLogo;
		$wgOut->disable();
		header( 'Content-Type: application/json' );
		header("Access-Control-Allow-Origin: *");
		$manifest = [
			"name" => $wgSitename,
			"start_url" => $wgServer,
			"description" => wfMessage( 'sitesubtitle' )->text(),
			"icons" => [
				[
					"src" => $wgServer . $wgLogo,
					"type" => 'image/' . ( preg_match( '|^.+(.\w+)$|', $wgLogo, $m ) ? $m[1] : 'jpg' )
				]
			]
		];
		echo json_encode( $manifest );
		self::restInPeace();
	}

	/**
	 * Return the URL to the manifest
	 */
	private function manifestUrl() {
		global $wgServer, $wgScriptPath;
		return $wgServer . $wgScriptPath . '?action=blockstack-manifest';
	}

	/**
	 * Die nicely
	 */
	public static function restInPeace() {
		global $mediaWiki;
		if( is_object( $mediaWiki ) ) $mediaWiki->restInPeace();
		exit;
	}
}
