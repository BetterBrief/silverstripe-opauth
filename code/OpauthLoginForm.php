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
		/*
		 * @var boolean
		 */
		$_strategiesDefined = false;

	protected
		/**
		 * @var array config
		 */
		$authenticator_class = 'OpauthAuthenticator';

	public function __construct($controller, $name) {
		parent::__construct($controller, $name, $this->getFields(), $this->getActions());
	}

	/**
	 * Override httpSubmission so we definitely have strategy handlers.
	 * This is because Form::httpSubmission is directly called.
	 */
	public function httpSubmission($request) {
		$this->defineStrategyHandlers();
		return parent::httpSubmission($request);
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
			$this->_strategiesDefined = true;
		}
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
	 * Global endpoint for handleStrategy - all strategy actions point here.
	 * @throws LogicException This should not be directly called.
	 * @throws InvalidArgumentException The strategy must be valid and existent
	 * @param string $funcName The bound function name from addWrapperMethod
	 * @param array $data Standard data param as part of form submission
	 * @param OpauthLoginForm $form
	 * @param SS_HTTPRequest $request
	 * @return ViewableData
	 */
	public function handleStrategy($funcName, $data, $form, $request) {
		if(func_num_args() < 4) {
			throw new LogicException('Must be called with a strategy handler');
		}
		// Trim handleStrategy from the function name:
		$strategy = substr($funcName, strlen('handleStrategy'));

		// Check the strategy is good
		if(!class_exists($strategy) || $strategy instanceof OpauthStrategy) {
			throw new InvalidArgumentException('Opauth strategy '.$strategy.' was not found or is not a valid strategy');
		}

		return $this->controller->redirect(
			Controller::join_links(
				OpauthController::get_path(),
				OpauthAuthenticator::get_strategy_segment($strategy)
			)
		);
	}

}
