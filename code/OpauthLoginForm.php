<?php

/**
 * OpauthLoginForm
 * The form presented to users for signing in with an Opauth strategy.
 * Not a form, rather a gateway that works by taking enabled strategies and
 * displaying a button to start the OAuth process with that strategy provider.
 *
 * @author Will Morgan <will@betterbrief>
 */
class OpauthLoginForm extends LoginForm {

	private
		$_strategiesDefined = false;

	protected
		/**
		 * @var array config
		 */
		$authenticator_class = 'OpauthAuthenticator';

	public function __construct($controller, $name) {
		parent::__construct($controller, $name, $this->getFields(), $this->getActions());
	}

	public function httpSubmission($request) {
		$this->defineStrategyHandlers();
		parent::httpSubmission($request);
	}

	/**
	 * Channel several unknown strategies in to one handler
	 */
	protected function defineStrategyHandlers() {
		if(!$this->_strategiesDefined) {
			foreach($this->getStrategies() as $strategyClass) {
				$strategyMethod = 'handleStrategy' . $strategyClass;
				$this->addWrapperMethod($strategyMethod, 'handleStrategy');
			}
		}
		$this->_strategiesDefined = true;
	}

	/**
	 * Ensure AuthenticationMethod is set to tell Security which form to process
	 * Very important for multi authenticator form setups.
	 * @return FieldList
	 */
	protected function getFields() {
		return new FieldList(
			new HiddenField('AuthenticationMethod', null, $this->authenticator_class)
		);
	}

	/**
	 * Provide an action button to be clicked per strategy
	 * @return FieldList
	 */
	protected function getActions() {
		$actions = new FieldList();
		foreach($this->getStrategies() as $strategyClass) {
			$strategyMethod = 'handleStrategy' . $strategyClass;
			$fa = new FormAction($strategyMethod, $strategyClass);
			$fa->setUseButtonTag(true);
			$actions->push($fa);
		}
		return $actions;
	}

	/**
	 * @return array All enabled strategies from config
	 */
	public function getStrategies() {
		return OpauthAuthenticator::get_enabled_strategies();
	}

	/**
	 * Global endpoint for handleStrategy - all strategy actions point here
	 * @return ViewableData
	 */
	public function handleStrategy($data, $form) {
		Debug::dump($data);
		return 'yeh';
	}

}
