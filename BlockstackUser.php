<?php
namespace BlockstackSso;

class BlockstackUser {

	private $did;
	private $name;
	private $secret;
	private $userId;

	private function __construct( $did ) {
		$this->did = $did;
		$user->init();
	}

	/**
	 * Create a new BlockstackUser from a distributed ID
	 */
	public static function newFromDid( $did ) {
		return new self( $did );
	}

	public static function newFromUserId( $id ) {
		$user = new self( $did );
		if( $row = $dbr->selectRow( \BlockstackSso::TABLENAME, 'bs_did', ['bs_user' => $id] ) ) {
			$user = new self( $row->bs_did );
		}
		return false;
	}


	/**
	 * Loads the data of the person represented by the Blockstack User ID.
	 */
	private function init() {
		$dbr = wfGetDB( DB_SLAVE );
		if( $row = $dbr->selectRow( \BlockstackSso::TABLENAME, '*', ['bs_did' => $this->did] ) ) {
			$this->name = $row->bs_name;
			$this->secret = $row->bs_secret;
			$this->userId = $row->bs_user;
			}
		}
	}

	/**
	 * Update the database to match the current user details, create if necessary
	 */
	public function save() {
		$dbw = wfGetDB( DB_MASTER );
		$row = [
			'bs_did'    => $this->did,
			'bs_name'   => $this->name,
			'bs_secret' => $this->secret,
			'bs_user'   => $this->userId
		];

		// Update the row if it exists already
		if( $dbr->selectRow( \BlockstackSso::TABLENAME, '*', ['bs_did' => $this->did] ) ) {
			$dbw->update( BlockstackSso::TABLENAME, $row, ['bs_did' => $this->did] );
		}

		// User doesn't exist, create now
		else {
			$dbw->insert( self::TABLENAME, $row );
		}
	}

	public function getDid() {
		return $this->did;
	}

	public function getName() {
		return $this->name;
	}

	public function setName( $name ) {
		$this->name = $name;
	}

	public function getSecret() {
		return $this->secret;
	}

	public function setSecret( $secret ) {
		$this->secret = $secret;
	}

	public function getWikiUser() {
		return User::newFromId( $this->userId );
	}

	public function setWikiUser( $id ) {
		$this->userId = $id;
	}

	public function isLinked() {
		return (bool)$this->userId;
	}

}
