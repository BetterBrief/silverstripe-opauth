<?php

/**
 * OpauthPageController
 * Wraps {@link Page_Controller} to add a simple extension point to canView(). Fixing the issue of
 * {@link OpauthController} failing permissions checks when trying to log in.
 * @author Stephen McMahon <@stephenmcm>
 */
class OpauthPageController extends Page_Controller {

    public function canView($member = null) {
        $canView = parent::canView($member);
        $this->extend('canView', $canView);

        return $canView;
    }
}