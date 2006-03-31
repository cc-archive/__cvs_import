/* Copyright (c) 2004 The Nutch Organization.  All rights reserved.   */
/* Use subject to the conditions in http://www.nutch.org/LICENSE.txt. */

package org.creativecommons.nutch;

import org.apache.lucene.document.Document;
import org.apache.lucene.document.Field;

import net.nutch.parse.Parse;

import net.nutch.indexer.IndexingFilter;
import net.nutch.indexer.IndexingException;

import net.nutch.fetcher.FetcherOutput;
import net.nutch.pagedb.FetchListEntry;

import java.util.logging.Logger;
import net.nutch.util.LogFormatter;

import java.util.*;
import java.net.URL;
import java.net.MalformedURLException;

/** Adds basic searchable fields to a document. */
public class CCIndexingFilter implements IndexingFilter {
  public static final Logger LOG
    = LogFormatter.getLogger(CCIndexingFilter.class.getName());

  /** The name of the document field we use. */
  public static String FIELD = "cc";

  public Document filter(Document doc, Parse parse, FetcherOutput fo)
    throws IndexingException {
    
    // index the license
    String licenseUrl = parse.getData().get("License-Url");
    if (licenseUrl != null) {
      LOG.info("CC: indexing "+licenseUrl+" for: "+fo.getUrl());

      // add the entire license as cc:license=xxx
      addFeature(doc, "license="+licenseUrl);

      // index license attributes extracted of the license url
      addUrlFeatures(doc, licenseUrl);
    }

    // index the license location as cc:meta=xxx
    String licenseLocation = parse.getData().get("License-Location");
    if (licenseLocation != null) {
      addFeature(doc, "meta="+licenseLocation);
    }

    // index the work type cc:type=xxx
    String workType = parse.getData().get("Work-Type");
    if (workType != null) {
      addFeature(doc, workType);
    }

    return doc;
  }

  /** Add the features represented by a license URL.  Urls are of the form
   * "http://creativecommons.org/licenses/xx-xx/xx/xx", where "xx" names a
   * license feature. */
  public void addUrlFeatures(Document doc, String urlString) {
    try {
      URL url = new URL(urlString);

      // tokenize the path of the url, breaking at slashes and dashes
      StringTokenizer names = new StringTokenizer(url.getPath(), "/-");

      if (names.hasMoreTokens())
        names.nextToken();                        // throw away "licenses"

      // add a feature per component after "licenses"
      while (names.hasMoreTokens()) {
        String feature = names.nextToken();
        addFeature(doc, feature);
      }
    } catch (MalformedURLException e) {
      LOG.warning("CC: failed to parse url: "+urlString+" : "+e);
    }
  }
  
  private void addFeature(Document doc, String feature) {
    doc.add(Field.Keyword(FIELD, feature));
  }

}
