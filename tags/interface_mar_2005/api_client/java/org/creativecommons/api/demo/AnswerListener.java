/*
 * AnswerListener.java
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
import java.util.Map;
import java.util.NoSuchElementException;

import org.eclipse.swt.events.SelectionEvent;
import org.eclipse.swt.events.SelectionListener;
import org.eclipse.swt.widgets.Combo;

/**
 * @author Nathan R. Yergler
 *
 */

public class AnswerListener implements SelectionListener {

	/**
	 * 
	 */
	private Map answerContainer;
	private Map answerMap;
	private String id;
	
	public AnswerListener(String id, Map answerMap, Map answerContainer) {
		super();
		
		this.id = id;
		this.answerMap = new HashMap();
		
		Iterator keys = answerMap.keySet().iterator();
		
		try {
			String current = (String)keys.next();
			
			while (true) {
				this.answerMap.put(answerMap.get(current), current);		
				current = (String)keys.next();
			}
		} catch (NoSuchElementException e) {
			// exception indicates we've iterated through the 
			// entire collection; just swallow and continue
		}
		
		this.answerContainer = answerContainer;
	}

	/* (non-Javadoc)
	 * @see org.eclipse.swt.events.SelectionListener#widgetSelected(org.eclipse.swt.events.SelectionEvent)
	 */
	public void widgetSelected(SelectionEvent e) {

		this.answerContainer.put(this.id, 
				this.answerMap.get(((Combo)e.getSource()).getText()));
		
	}

	/* (non-Javadoc)
	 * @see org.eclipse.swt.events.SelectionListener#widgetDefaultSelected(org.eclipse.swt.events.SelectionEvent)
	 */
	public void widgetDefaultSelected(SelectionEvent e) {

	}

}
