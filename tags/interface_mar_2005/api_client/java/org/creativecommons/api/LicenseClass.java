/*
 * LicenseClass.java
 * 
 * copyright 2005, Creative Commons, Nathan R. Yergler
 * licensed under the GNU GPL 2.0
 * 
 * Created on Feb 7, 2005
 *
 */

package org.creativecommons.api;

/**
 * @author Nathan R. Yergler
 *
 */

/**
 * Wrapper class which represents a license class declaration.
 * A license class represents a type of license which the REST interface
 * can generate, such as standard CC, Sampling, Public Domain, etc.
 */
public class LicenseClass {

	
	private String identifier = "";
	private String label = "";
	
	/**
	 * Constructs a new instance of LicenseClass.
	 * 
	 * @param identifier The unique identifier for this class.
	 * 			Note the identifier is unique only within the 
	 * 			scope of the web service root.
	 * @param label	The label used to represent the class in 
	 * 			generated user interfaces.
	 */
	public LicenseClass(String identifier, String label) {
		this.identifier = identifier;
		this.label = label;
	}
	
	/**
	 * 
	 * @return Returns the identifier for this License Class.
	 */
	public String getIdentifier() {
		return this.identifier;
	}
	
	/**
	 * 
	 * @return Returns the label for this License Class.
	 */
	public String getLabel() {
		return this.label;
	}

}
