<?php

/**
 * OpauthRegisterForm
 * Presented to users whose OpauthIdentity object does not provide enough info.
 * This is triggered by the Member failing validation; you can modify this by
 * hooking in to the Member::validate() method via a DataExtension.
 * @author Will Morgan <@willmorgan>
 * @copyright Copyright (c) 2013, Better Brief LLP
 */
class OpauthRegisterForm extends Form {

	protected
		$fields,
		$requiredFields;

	protected static
		$field_source;

	/**
	 * @param Controller $controller
	 * @param string $name
	 * @param array $requiredFields
	 */
	public function __construct($controller, $name, array $requiredFields = null) {
		if(isset($requiredFields)) {
			$this->requiredFields = $requiredFields;
		}
		parent::__construct($controller, $name, $this->getFields(), $this->getActions(), $this->getValidator());
	}

	/**
	 * setRequiredFields
	 * Resets everything if the fields change
	 */
	public function setRequiredFields($fields) {
		$this->requiredFields = $fields;
		$this->setValidator($this->getValidator());
		return $this;
	}

	/**
	 * getFields
	 * Picks only the required fields from the field source
	 * and then presents them in a field set.
	 * @return FieldList
	 */
	public function getFields() {
		return $this->getFieldSource();
	}

	/**
	 * Uses the field_source defined, or falls back to the Member's getCMSFields
	 * @return FieldList
	 */
	public function getFieldSource() {
		if(is_callable(self::$field_source)) {
			$fields = call_user_func(self::$field_source, $this);
			if(!$fields instanceof FieldList) {
				throw new InvalidArgumentException('Field source must be callable and return a FieldList');
			}
			return $fields;
		}
		return new FieldList(singleton('Member')->getCMSFields()->dataFields());
	}

	/**
	 * Set a callable as a data provider for the field source. Field names must
	 * match those found on @see Member so they can be filtered accordingly.
	 *
	 * Callable docs: http://php.net/manual/en/language.types.callable.php
	 * @param callable $sourceFn Source closure to use, accepts $this as param
	 */
	public static function set_field_source($sourceFn) {
		if(!is_callable($sourceFn)) {
			throw new InvalidArgumentException('$sourceFn must be callable and return a FieldList');
		}
		self::$field_source = $sourceFn;
	}

	/**
	 * Get actions
	 * Points to a controller action
	 * @return FieldList
	 */
	public function getActions() {
		return new FieldList(array(
			new FormAction('doCompleteRegister', 'Complete'),
		));
	}

	/**
	 * @return RequiredFields
	 */
	public function getValidator() {
		return new RequiredFields($this->requiredFields);
	}

	/**
	 * Populates the form somewhat intelligently
	 * @param SS_HTTPRequest $request Any request
	 * @param Member $member Any member
	 * @param array $required Any validation messages
	 * @return $this
	 */
	public function populateFromSources(SS_HTTPRequest $request = null, Member $member = null, array $required = null) {
		$dataPath = "FormInfo.{$this->FormName()}.data";
		// Hacky again :(
		if(Session::get($dataPath)) {
			$this->loadDataFrom(Session::get($dataPath));
		}
		else if($member) {
			$this->loadDataFrom($member);
		}
		else if($request) {
			$this->loadDataFrom($request->postVars());
		}
		if(!empty($required)) {
			$this->setRequiredFields($required);
		}
		return $this;
	}

}


