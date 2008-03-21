<?php
/**
 * OWASP Enterprise Security API (ESAPI)
 * 
 * This file is part of the Open Web Application Security Project (OWASP)
 * Enterprise Security API (ESAPI) project. For details, please see
 * http://www.owasp.org/esapi.
 *
 * Copyright (c) 2007 - The OWASP Foundation
 * 
 * The ESAPI is published by OWASP under the LGPL. You should read and accept the
 * LICENSE before you use, modify, and/or redistribute this software.
 * 
 * @author Jeff Williams <a href="http://www.aspectsecurity.com">Aspect Security</a>
 * @package org.owasp.esapi.errors;
 * @created 2007
 */


/**
 * An AuthenticationException should be thrown when anything goes wrong during
 * login or logout. They are also appropriate for any problems related to
 * identity management.
 * 
 * @author Jeff Williams (jeff.williams@aspectsecurity.com)
 */
class AuthenticationCredentialsException extends AuthenticationException {

	/** The Constant serialVersionUID. */
	private static $serialVersionUID = 1;

	/**
	 * Instantiates a new authentication exception.
	 */
	protected function AuthenticationCredentialsException() {
		// hidden
	}

	/**
	 * Creates a new instance of EnterpriseSecurityException.
	 * 
	 * @param message
	 *            the message
	 */
	public function AuthenticationCredentialsException($userMessage, $logMessage, $cause = null) {
		super($userMessage, $logMessage, $cause);
	}
}
?>