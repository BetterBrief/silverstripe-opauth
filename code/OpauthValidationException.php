<?php

/**
 * OpauthValidationException
 * Exception padded with some data so its handler can do its job intelligently.
 * @author Will Morgan <@willmorgan>
 * @copyright Copyright (c) 2013, Better Brief LLP
 */
class OpauthValidationException extends Exception {

	protected $data;

	public function __construct($message, $code, $data = null) {
		parent::__construct($message, $code);
		$this->setData($data);
	}

	public function setData($data) {
		$this->data = $data;
	}

	public function getData() {
		return $this->data;
	}

}
