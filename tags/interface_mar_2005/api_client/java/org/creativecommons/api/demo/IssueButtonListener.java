/*
 * IssueButtonListener.java
 * Responsible for dispatching from "Select License" button to CcRest instance.
 * 
 * copyright 2005, Creative Commons, Nathan R. Yergler
 * licensed under the GNU GPL 2.0
 * 
 * Created on Feb 8, 2005
 *
 */
package org.creativecommons.api.demo;

import java.io.IOException;

import org.creativecommons.api.CcRest;
import org.eclipse.swt.events.MouseEvent;
import org.eclipse.swt.events.MouseListener;
import org.eclipse.swt.widgets.Label;

/**
 * @author Nathan R. Yergler
 *
 */
public class IssueButtonListener implements MouseListener {

	/**
	 * 
	 */
	
	CcRest ccr = null;
	DemoUi shell = null;
	
	public IssueButtonListener(CcRest ccr, DemoUi shell) {
		super();
		
		// store references to the shell and CcRest instance
		this.ccr = ccr;
		this.shell = shell;
	}

	/* (non-Javadoc)
	 * @see org.eclipse.swt.events.MouseListener#mouseDoubleClick(org.eclipse.swt.events.MouseEvent)
	 */
	public void mouseDoubleClick(MouseEvent e) {

	}

	/* (non-Javadoc)
	 * @see org.eclipse.swt.events.MouseListener#mouseDown(org.eclipse.swt.events.MouseEvent)
	 */
	public void mouseDown(MouseEvent e) {

	}

	/* (non-Javadoc)
	 * @see org.eclipse.swt.events.MouseListener#mouseUp(org.eclipse.swt.events.MouseEvent)
	 */
	public void mouseUp(MouseEvent e) {

		Label licName;
		
    	try {
    		// retrieve the Document for the issued license
    		ccr.issue(shell.currentId, shell.getAnswers(), "en");
    	} catch (IOException err) {
    		
    	}

    	// update the user interface
    	shell.setLicenseDetails(ccr.getLicenseName(), ccr.getLicenseUrl());
		shell.getShell().layout();
	} // mouseUp

}
