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
		$member,
		$fields,
		$requiredFields;

	protected static
		$field_source;

	/**
	 * @param Controller $controller
	 * @param string $name
	 * @param Member $member
	 * @param array $requiredFields
	 */
	public function __construct($controller, $name, Member $member, array $requiredFields) {
		$this->member = $member;
		$this->requiredFields = $requiredFields;
		parent::__construct($controller, $name, $this->getFields(), $this->getActions(), $this->getValidator());
	}

	/**
	 * getFields
	 * Picks only the required fields from the field source
	 * and then presents them in a field set.
	 * @return FieldList
	 */
	public function getFields() {
		if(!$this->fields) {
			$memberFields = $this->getFieldSource();
			$fields = new FieldList();
			foreach($this->requiredFields as $field) {
				$fields->push($memberFields->fieldByName($field));
			}
			$this->fields = $fields;
		}
		return $this->fields;
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
		}
		return $this->member->getCMSFields()->dataFields();
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
	 * @return Member
	 */
	public function getMember() {
		return $this->member;
	}

	/**
	 * @return RequiredFields
	 */
	public function getValidator() {
		return new RequiredFields($this->requiredFields);
	}

}


