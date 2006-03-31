/*
 * RestDemo.java
 * Primary controller for CC Rest web services demo.
 * 
 * copyright 2005, Creative Commons, Nathan R. Yergler
 * licensed under the GNU GPL 2.0
 * 
 * Created on Feb 7, 2005
 *
 */

package org.creativecommons.api.demo;

import org.creativecommons.api.CcRest;
import org.eclipse.swt.widgets.Display;
import org.eclipse.swt.widgets.Shell;

/**
 * @author Nathan R. Yergler
 *
 */
public class RestDemo {

	public static void main(String[] args) {
		
		// Create an instance of the web services wrapper
		CcRest ccr = new CcRest();
		
		// create the new UI and connect it to the web services wrapper
		Display display = new Display();
		DemoUi ui = new DemoUi();
		ui.setRest(ccr);
		
		// display the interface
		Shell shell = ui.getShell();
		shell.open();
		
		// main event loop
		while( !shell.isDisposed())
		   {
		     if(!display.readAndDispatch()) 
		     display.sleep();
		   } // while not disposed
		
		// clean up the inteface object
		display.dispose();
		
	} // main
	
}  // RestDemo
