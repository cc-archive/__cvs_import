/* Copyright (c) 2004 The Nutch Organization.  All rights reserved.   */
/* Use subject to the conditions in http://www.nutch.org/LICENSE.txt. */

package org.creativecommons.nutch;

import net.nutch.parse.*;
import net.nutch.protocol.Content;
import net.nutch.util.NutchConf;

import java.util.*;
import java.io.*;
import java.net.*;
import javax.xml.parsers.*;
import org.xml.sax.InputSource;
import org.w3c.dom.*;

import java.util.logging.Logger;
import net.nutch.util.LogFormatter;

/** Adds metadata identifying the Creative Commons license used, if any. */
public class CCParseFilter implements HtmlParseFilter {
  public static final Logger LOG
    = LogFormatter.getLogger(CCParseFilter.class.getName());

  private static final boolean EXCLUDE_UNLICENSED =
    NutchConf.getBoolean("creativecommons.exclude.unlicensed", false);

  /** Walks DOM tree, looking for RDF in comments and licenses in anchors.*/
  public static class Walker {
    private URL base;                             // base url of page
    private String rdfLicense;                    // subject url found, if any
    private URL relLicense;                       // license url found, if any
    //private URL anchorLicense;                    // anchor url found, if any
    private String workType;                      // work type URI

    private Walker(URL base) {
      this.base = base;
    }

    /** Scan the document adding attributes to metadata.*/
    public static void walk(Node doc, URL base, Properties metadata)
      throws ParseException {

      // walk the DOM tree, scanning for license data
      Walker walker = new Walker(base);
      walker.walk(doc);

      // interpret results of walk
      String licenseUrl = null;
      String licenseLocation = null;
      if (walker.rdfLicense != null) {            // 1st choice: subject in RDF
        licenseLocation = "rdf";
        licenseUrl = walker.rdfLicense;
      } else if (walker.relLicense != null) {     // 2nd: anchor w/ rel=license
        licenseLocation = "rel";
        licenseUrl = walker.relLicense.toString();
      //}  else if (walker.anchorLicense != null) {  // 3rd: anchor w/ CC license
      //  licenseLocation = "a";
      //  licenseUrl = walker.anchorLicense.toString();
      } else if (EXCLUDE_UNLICENSED) {
        throw new ParseException("No CC license.  Excluding.");
      }

      // add license to metadata
      if (licenseUrl != null) {
        LOG.info("CC: found "+licenseUrl+" in "+licenseLocation+" of "+base);
        metadata.put("License-Url", licenseUrl);
        metadata.put("License-Location", licenseLocation);
      }

      if (walker.workType != null) {
        LOG.info("CC: found "+walker.workType+" in "+base);
        metadata.put("Work-Type", walker.workType);
      }

    }

    /** Scan the document looking for RDF in comments and license elements.*/
    private void walk(Node node) {
      
      // check element nodes for license URL
      if (node instanceof Element) {
        findLicenseUrl((Element)node);
      }

      // check comment nodes for license RDF
      if (node instanceof Comment) {
        findRdf(((Comment)node).getData());
      }

      // recursively walk child nodes
      NodeList children = node.getChildNodes();
      for (int i = 0; children != null && i < children.getLength(); i++ ) {
        walk(children.item(i));
      }
    }

    /** Extract license url from element, if any.  Thse are the href attribute
     * of anchor elements with rel="license".  These must also point to
     * http://creativecommons.org/licenses/. */
    private void findLicenseUrl(Element element) {
      // only look in Anchor elements
      if (!"a".equalsIgnoreCase(element.getTagName()))
        return;

      // require an href
      String href = element.getAttribute("href");
      if (href == null)
        return;
      
      try {
        URL url = new URL(base, href);            // resolve the url

        // check that it's a CC license URL
        if ("http".equalsIgnoreCase(url.getProtocol()) &&
            "creativecommons.org".equalsIgnoreCase(url.getHost()) &&
            url.getPath() != null &&
            url.getPath().startsWith("/licenses/") &&
            url.getPath().length() > "/licenses/".length()) {

          // check rel="license"
          String rel = element.getAttribute("rel");
          if (rel != null && "license".equals(rel) && this.relLicense == null) {
            this.relLicense = url;                   // found rel license
          //} else if (this.anchorLicense == null) {
          //  this.anchorLicense = url;             // found anchor license
          }
        }
      } catch (MalformedURLException e) {         // ignore malformed urls
      }
    }

   /** Configure a namespace aware XML parser. */
    private static final DocumentBuilderFactory FACTORY
      = DocumentBuilderFactory.newInstance();
    static {
      FACTORY.setNamespaceAware(true);
    }

    /** Creative Commons' namespace URI. */
    private static final String CC_NS = "http://web.resource.org/cc/";
    
    /** Dublin Core namespace URI. */
    private static final String DC_NS = "http://purl.org/dc/elements/1.1/";
    
    /** RDF syntax namespace URI. */
    private static final String RDF_NS
      = "http://www.w3.org/1999/02/22-rdf-syntax-ns#";

    private void findRdf(String comment) {
      // first check for likely RDF in comment
      int rdfPosition = comment.indexOf("RDF");
      if (rdfPosition < 0)
        return;                                   // no RDF, abort
      int nsPosition = comment.indexOf(CC_NS);
      if (nsPosition < 0)
        return;                                   // no RDF, abort

      // try to parse the XML
      Document doc;
      try {
        DocumentBuilder parser = FACTORY.newDocumentBuilder();
        doc = parser.parse(new InputSource(new StringReader(comment)));
      } catch (Exception e) {
        LOG.warning("CC: Failed to parse RDF in "+base+": "+e);
        //e.printStackTrace();
        return;
      }

      // check that root is rdf:RDF
      NodeList roots = doc.getElementsByTagNameNS(RDF_NS, "RDF");
      if (roots.getLength() != 1) {
        LOG.warning("CC: No RDF root in "+base);
        return;
      }
      Element rdf = (Element)roots.item(0);

      // get cc:License nodes inside rdf:RDF
      NodeList licenses = rdf.getElementsByTagNameNS(CC_NS, "License");
      for (int i = 0; i < licenses.getLength(); i++) {

        Element l = (Element)licenses.item(i);

        // license is rdf:about= attribute from cc:License
        this.rdfLicense = l.getAttributeNodeNS(RDF_NS,"about").getValue();

        // walk predicates of cc:License
        NodeList predicates = l.getChildNodes();
        for (int j = 0; j < predicates.getLength(); j++) {
          Node predicateNode = predicates.item(j);
          if (!(predicateNode instanceof Element))
            continue;
          Element predicateElement = (Element)predicateNode;

          // extract predicates of cc:xxx predicates
          if (!CC_NS.equals(predicateElement.getNamespaceURI())) {
            continue;
          }
          String predicate = predicateElement.getLocalName();

          // object is rdf:resource from cc:xxx predicates
          String object =
            predicateElement.getAttributeNodeNS(RDF_NS, "resource").getValue();
        
          // add object and predicate to metadata
          // metadata.put(object, predicate);
          // LOG.info("CC: found: "+predicate+"="+object);
        }
      }

      // get cc:Work nodes from rdf:RDF
      NodeList works = rdf.getElementsByTagNameNS(CC_NS, "Work");
      for (int i = 0; i < works.getLength(); i++) {
        Element l = (Element)works.item(i);
        
        // get dc:type nodes from cc:Work
        NodeList types = rdf.getElementsByTagNameNS(DC_NS, "type");
        for (int j = 0; j < types.getLength(); j++) {
          Element type = (Element)types.item(j);
          String workUri = 
            type.getAttributeNodeNS(RDF_NS, "resource").getValue();
          this.workType = (String)WORK_TYPE_NAMES.get(workUri);
          break;
        }
      }
    }
  }

  private static final HashMap WORK_TYPE_NAMES = new HashMap();
  static {
    WORK_TYPE_NAMES.put("http://purl.org/dc/dcmitype/MovingImage", "video");
    WORK_TYPE_NAMES.put("http://purl.org/dc/dcmitype/StillImage", "image");
    WORK_TYPE_NAMES.put("http://purl.org/dc/dcmitype/Sound", "audio");
    WORK_TYPE_NAMES.put("http://purl.org/dc/dcmitype/Text", "text");
    WORK_TYPE_NAMES.put("http://purl.org/dc/dcmitype/Interactive", "interactive");
    WORK_TYPE_NAMES.put("http://purl.org/dc/dcmitype/Software", "software");
    WORK_TYPE_NAMES.put("http://purl.org/dc/dcmitype/Image", "image");
  }

  /** Adds metadata or otherwise modifies a parse of an HTML document, given
   * the DOM tree of a page. */
  public Parse filter(Content content, Parse parse, DocumentFragment doc)
    throws ParseException {

    // construct base url
    URL base;
    try {
      base = new URL(content.getBaseUrl());
    } catch (MalformedURLException e) {
      throw new ParseException(e);
    }

    // extract license metadata
    Walker.walk(doc, base, parse.getData().getMetadata());

    return parse;
  }

}
