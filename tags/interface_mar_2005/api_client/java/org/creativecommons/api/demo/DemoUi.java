/*
 * DemoUi.java
 * 
 * copyright 2005, Creative Commons, Nathan R. Yergler
 * licensed under the GNU GPL 2.0
 * 
 * Created on Feb 7, 2005
 *
 */

package org.creativecommons.api.demo;

import java.util.HashMap;
import java.util.Iterator;
import java.util.List;
import java.util.Map;
import java.util.NoSuchElementException;

import org.creativecommons.api.CcRest;
import org.creativecommons.api.LicenseClass;
import org.creativecommons.api.LicenseField;
import org.eclipse.swt.SWT;
import org.eclipse.swt.events.SelectionAdapter;
import org.eclipse.swt.events.SelectionEvent;
import org.eclipse.swt.layout.GridData;
import org.eclipse.swt.layout.GridLayout;
import org.eclipse.swt.widgets.Button;
import org.eclipse.swt.widgets.Combo;
import org.eclipse.swt.widgets.Label;
import org.eclipse.swt.widgets.Text;

/**
 * @author Nathan R. Yergler
 * 
 */
public class DemoUi {

	private CcRest ccr = null;
	
	private org.eclipse.swt.widgets.Shell sShell = null;  //  @jve:decl-index=0:visual-constraint="3,10"
	private GridLayout gl;
	
	private Label label = null;
	private Label licName = null;
	private Combo cmbLicenseClasses = null;
	
	private Label lblLicenseName;
	private Label lblLicenseUri;
	
	public String currentId = "";
	
	private HashMap answers;
	
	/**
	 * This method initializes sShell
	 */
	private void createSShell() {
		sShell = new org.eclipse.swt.widgets.Shell();		  
		gl = new GridLayout();
		label = new Label(sShell, SWT.NONE);
		createCombo();
		gl.numColumns = 2;
		sShell.setLayout(gl);
		label.setText("License Class:");
		
		sShell.setSize(new org.eclipse.swt.graphics.Point(400,300));
		sShell.setText("CC Rest Demo");

	}
	
	public org.eclipse.swt.widgets.Shell getShell() {
		if (this.sShell == null) {
			this.createSShell();
			this.resetInterface();
		}
		return this.sShell;
	}
	/**
	 * This method initializes combo	
	 *
	 */    
	private void createCombo() {
		GridData gridData1 = new GridData(GridData.FILL_HORIZONTAL);
		cmbLicenseClasses = new Combo(sShell, SWT.SINGLE|SWT.BORDER);		   
		gridData1.grabExcessHorizontalSpace = true;
		cmbLicenseClasses.setLayoutData(gridData1);
		
		cmbLicenseClasses.addSelectionListener(new SelectionAdapter( ) {
            public void widgetSelected(SelectionEvent e) {
            	selectClass(ccr.getLicenseId(cmbLicenseClasses.getText()));
            }
        });
	}
	
	private Combo comboFactory() {
		GridData gridData1 = new GridData(GridData.FILL_HORIZONTAL);
		Combo combo1 = new Combo(sShell, SWT.SINGLE|SWT.BORDER);		   
		gridData1.grabExcessHorizontalSpace = true;
		combo1.setLayoutData(gridData1);
		
		return combo1;
		
	}
		
	public void setLicenseDetails(String name, String uri) {
		this.lblLicenseName.setText(name);
		this.lblLicenseUri.setText(uri);
	}
	
	public void selectClass(String id) {
		this.answers = new HashMap();
		this.currentId = id;
		
		//this.createSShell();
		
		LicenseField current;
		
		Label label;
		Text entry;
		
		List fields = (List)this.ccr.fields(id);
		
		for (int i = 0; i < fields.size(); i ++) {
			current = (LicenseField)fields.get(i);
			
			// Add the field label
			label = new Label(this.sShell, SWT.NONE);
			label.setText(current.getLabel());

			// determine what type of widget needs to be added
			if (current.getType().equals("enum")) {
				// ccPublisher chooses between drop downs and combo-boxes
				// based on the number of available options.  This code only
				// uses drop downs for simplicity.

				Combo cmbEnum = this.comboFactory();
					
				Iterator enum_items = current.getEnum().values().iterator();
				String current_item = (String)enum_items.next();
				
				try {
					while (true) {
						cmbEnum.add(current_item);
						current_item = (String)enum_items.next();
					}
				} catch (NoSuchElementException e) {
					// exception indicates we've iterated through the 
					// entire collection; just swallow and continue
				}
				
				cmbEnum.addSelectionListener(new AnswerListener(current.getId(), current.getEnum(), this.answers));
					
			} // if type == enum
			else {
				System.out.print(current.getType());
			}
			
		} // for each field
		
		// Add the "issue" button
		GridData gdIssue = new GridData();
		gdIssue.horizontalAlignment = GridData.CENTER;
		gdIssue.horizontalSpan = 2;
		
		Button cmdIssue = new Button(this.sShell, SWT.NONE);
		cmdIssue.setLayoutData(gdIssue);
		cmdIssue.setText("Select License");
		
		cmdIssue.addMouseListener(new IssueButtonListener(ccr, this));
		
		// Add empty labels for use after issuing
		label = new Label(this.sShell, SWT.NONE);
		label.setText("License Name:");
		
		this.lblLicenseName = new Label(this.sShell, SWT.NONE);
		this.lblLicenseName.setLayoutData(new GridData(GridData.FILL_HORIZONTAL));
		
		label = new Label(this.sShell, SWT.NONE);
		label.setText("License URI:");
		
		this.lblLicenseUri = new Label(this.sShell, SWT.NONE);
		this.lblLicenseUri.setLayoutData(new GridData(GridData.FILL_HORIZONTAL));
		
		// Reset the container's layout 
		// (forces new widgets to be displayed)
		this.sShell.layout();
		
	} // selectClass
	
	public Map getAnswers() {
		return this.answers;		
	}
	
	public void resetInterface() {
		List classes = (List)ccr.licenseClasses("en");
		
		for (int i = 0; i < classes.size(); i++) {
			cmbLicenseClasses.add( ((LicenseClass)classes.get(i)).getLabel() );
		}
		
	}
	public void setRest(CcRest ccr) {
		this.ccr = ccr;
	}
 }
