/* Copyright (c) 2003 The Nutch Organization.  All rights reserved.   */
/* Use subject to the conditions in http://www.nutch.org/LICENSE.txt. */

package org.creativecommons.nutch;

import net.nutch.searcher.RawFieldQueryFilter;

/** Handles "cc:" query clauses, causing them to search the "cc" field
 * indexed by CCIndexingFilter. */
public class CCQueryFilter extends RawFieldQueryFilter {
  public CCQueryFilter() {
    super(CCIndexingFilter.FIELD);
  }
}
