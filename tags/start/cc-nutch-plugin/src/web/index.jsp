<%@ page 
  contentType="text/html; charset=UTF-8"
  pageEncoding="UTF-8"

  import="javax.servlet.*"
  import="javax.servlet.http.*"
  import="java.io.*"
  import="java.util.*"
  import="java.net.*"

  import="net.nutch.html.Entities"
  import="net.nutch.searcher.*"
%><%
  NutchBean bean = NutchBean.get(application);
  // set the character encoding to use when interpreting request values 
  request.setCharacterEncoding("UTF-8");

  bean.LOG.info("query request from " + request.getRemoteAddr());

  // get query from request
  String queryString = request.getParameter("q");
  if (queryString == null)			  
    queryString = "";

  boolean commercial = false;
  boolean derivatives = false;
  boolean hasQuery = false;

    
    String commercialString = request.getParameter("commercial");
    if (commercialString != null)
      commercial = "true".equals(commercialString) || "on".equals(commercialString);
    String derivativesString = request.getParameter("derivatives");
    if (derivativesString != null)
      derivatives = "true".equals(derivativesString) || "on".equals(derivativesString);

    String engine = request.getParameter("engine");
    if (engine != null) {
      if ("yahoo".equals(engine)) {
        String yurl = "http://search.yahoo.com/search?p="+queryString+"&fr=cap-cc&cc=1";
        if (commercial)
          yurl += "&ccs=c";
        if (derivatives)
          yurl += "&ccs=e";
        response.sendRedirect(yurl);
      } else if ("google".equals(engine)) {
        String gurl = "http://www.google.com/search?q="+queryString+"&as_rights=";
        if (commercial && !derivatives) {
          gurl += "(cc_publicdomain|cc_attribute|cc_sharealike|cc_nonderived).-(cc_noncommercial)";
        } else if (!commercial && derivatives) {
          gurl += "(cc_publicdomain|cc_attribute|cc_sharealike|cc_noncommercial).-(cc_nonderived)";
        } else if (commercial && derivatives) {
          gurl += "(cc_publicdomain|cc_attribute|cc_sharealike).-(cc_noncommercial|cc_nonderived)";
        } else {
        //if (!commercial && !derivatives) {
          gurl += "(cc_publicdomain|cc_attribute|cc_sharealike|cc_noncommercial|cc_nonderived)";
        }
        response.sendRedirect(gurl);
      }
    }

  String htmlQueryString = "";

  int hitsPerSite = 2;                            // max hits per site
  int hitsPerPage = 10;				  // number of hits to display
  int start = 0;				  // first hit to display

  String format = "";

  Query query = null;

  if (!queryString.equals("")) {
    hasQuery = true;

    htmlQueryString = Entities.encode(queryString);
      
    String startString = request.getParameter("start");
    if (startString != null)
      start = Integer.parseInt(startString);
  
    String hitsString = request.getParameter("hitsPerPage");
    if (hitsString != null)
      hitsPerPage = Integer.parseInt(hitsString);
  
    String hitsPerSiteString = request.getParameter("hitsPerSite");
    if (hitsPerSiteString != null)
      hitsPerSite = Integer.parseInt(hitsPerSiteString);
      
    query = Query.parse(queryString);
  
    if (commercial)
      query.addProhibitedTerm("nc", "cc");
    if (derivatives)
      query.addProhibitedTerm("nd", "cc");
  

    format = request.getParameter("format");
    if (format == null) {
      format = "";
    } else if (
      !"Audio".equals(format)
      && !"Image".equals(format)
      && !"Interactive".equals(format)
      && !"Text".equals(format)
      && !"Video".equals(format)) {
      format = "";
    }
  
    if (!"".equals(format))
      query.addRequiredTerm(format.toLowerCase(), "cc");
  
    bean.LOG.info("query: " + query);
  }
 
  String language = "en";
  //  ResourceBundle.getBundle("org.nutch.jsp.search", request.getLocale())
  //  .getLocale().getLanguage();

  String requestURI = HttpUtils.getRequestURL(request).toString();
%> <!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<%
  // To prevent the character encoding declared with 'contentType' page
  // directive from being overriden by JSTL (apache i18n), we freeze it
  // by flushing the output buffer. 
  // see http://java.sun.com/developer/technicalArticles/Intl/MultilingualJSP/
  out.flush();
%>
<%@ taglib uri="http://jakarta.apache.org/taglibs/i18n" prefix="i18n" %>
<i18n:bundle baseName="org.nutch.jsp.search"/>
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<%= language %>" lang="<%= language %>">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Creative Commons: <i18n:message key="title"/></title>
<link rel="icon" href="http://creativecommons.org/includes/cc.ico" type="image/ico" />
<link rel="SHORTCUT ICON" href="http://creativecommons.org/includes/cc.ico" />
<jsp:include page="/include/style.html"/>
</head>

<body>

<jsp:include page="/include/header.html"/>

<div class="searchbox">
<h1><a href="http://creativecommons.org/">Creative Commons</a> Search</h1>

<div class="search-info">Full copyright applies to most stuff on the web. But this search helps you find photos, music, text, and other works whose authors want you to re-use it for some uses -- without having to pay or ask permission. (<strong><a href="what.html">More Info</a></strong>)
</div>

<div align="center">
<form action="/index.jsp" style="margin-left: 20px; margin-bottom: 20px;" method="get" name="search">

<input name="q" value="<%=htmlQueryString%>" style="width: 350px; margin-bottom: 4px;" type="text" /><br />

<table><tr valign="top" align="center">
<td>Search<br/>with:</td>
<td><input value="Google" type="submit" onclick="javascript:this.form.engine.value='google'">
<br/></td>
<td><input title="Yahoo! Search for Creative Commons" value="Yahoo!" type="submit" onclick="javascript:this.form.engine.value='yahoo'">
<br/><span style="font-size: 10px;">[<a href="http://search.yahoo.com/cc/faq">More Info</a>] [<a href="http://messages.search.yahoo.com/ccommons/forumview?bn=ccommons">Forum</a>]</span></td>
<td><input value="Nutch" type="submit" onclick="javascript:this.form.engine.value='nutch'">
<br/><span style="font-size: 10px;">[<a href="/help.html" onclick="window.open('http://search.creativecommons.org/help.html', 'help', 'width=500,height=300,scrollbars=yes,resizable=yes,toolbar=no,directories=no,location=no,menubar=no,status=yes');return false;">Help</a>]</span></td>
</tr></table>

<div style="border: 1px solid #ccc; padding: 10px 5px 5px; position: relative; color: rgb(65, 65, 65); margin-top: 14px; width: 350px;" align="left">
<p style="border-width: 0pt; margin: 0pt; padding: 0pt 4px 0pt 0pt; background: white none repeat scroll 0%; position: absolute; top: -12px; font-size: 120% ! important; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; color: #333; white-space: nowrap;">optional</p>
<input name="commercial" type="checkbox" <%=commercial?"checked":""%> value="true" /> Find me works I can use even for commercial purposes.
<br /><input name="derivatives" type="checkbox" value="true" <%=derivatives?"checked":""%> /> Find me works I can modify, adapt, or build upon.
</div>
<div style="border: 1px solid #ccc; padding: 10px 5px 5px; position: relative; color: rgb(65, 65, 65); margin-top: 14px; width: 350px;" align="left">
<p style="border-width: 0pt; margin: 0pt; padding: 0pt 4px 0pt 0pt; background: white none repeat scroll 0%; position: absolute; top: -12px; font-size: 120% ! important; -moz-background-clip: initial; -moz-background-origin: initial; -moz-background-inline-policy: initial; color: #333; white-space: nowrap;">optional (nutch only)</p>
Format: <select name="format">
<option <%="".equals(format)?"selected":""%> value="">Any</option>
<option <%="Audio".equals(format)?"selected":""%> value="Audio">Audio</option>
<option <%="Image".equals(format)?"selected":""%> value="Image">Image</option>
<option <%="Interactive".equals(format)?"selected":""%> value="Interactive">Interactive</option>
<option <%="Text".equals(format)?"selected":""%> value="Text">Text</option>
<option <%="Video".equals(format)?"selected":""%> value="Video" >Video</option>
</select>

<input type="hidden" name="hitsPerPage" value="<%=hitsPerPage%>" />
<input type="hidden" name="hitsPerSite" value="<%=hitsPerSite%>" />
<input type="hidden" name="engine" value="" />
</form>
</div>

</div>
</div>
<div class="resultsbox">

<% if (hasQuery) { %>

<div class="legend">
<p><strong>Key:</strong</p>
<p><img src="/img/pd.gif" alt="this file licensed under the public domain" align="absmiddle" /> - work is in the public domain</p>
<p><img src="/img/by.gif" title="this work requires author attribution" align="absmiddle" alt="this work requires author attribution" /> - must give attribution</p>
<p><img src="/img/nc.gif" alt="this work can only be used non-commercially" align="absmiddle" title="this work can only be used non-commercially" /> - can't use commercially</p>
<p><img src="/img/nd.gif" alt="you may not make derivative works from this work" align="absmiddle" title="you may not make derivative works from this work" /> - can't make derivatives</p>
<p><img src="/img/sa.gif" alt="this work must be licensed under an identical license if used" align="absmiddle" title="this work must be licensed under an identical license if used" /> - must sharealike (use same license)</p>
<p><img src="/img/sampling.gif" alt="this work can be used under the sampling license conditions" align="absmiddle" title="this work can be used under the sampling license conditions"  /> - sampling license</p>
<p><img src="/img/sampling+.gif" alt="this work can be used under the sampling+ license conditions" align="absmiddle" title="this work can be used under the sampling+ license conditions"  /> - sampling+ license</p>
</div>
<!-- start results -->
<%
   // perform query
   Hits hits = bean.search(query, start + hitsPerPage, hitsPerSite);
   int end = (int)Math.min(hits.getLength(), start + hitsPerPage);
   int length = end-start;
   Hit[] show = hits.getHits(start, length);
   HitDetails[] details = bean.getDetails(show);
   String[] summaries = bean.getSummary(details, query);

   bean.LOG.info("total hits: " + hits.getTotal());
%>

<i18n:message key="hits">
  <i18n:messageArg value="<%=new Long(start+1)%>"/>
  <i18n:messageArg value="<%=new Long(end)%>"/>
  <i18n:messageArg value="<%=new Long(hits.getTotal())%>"/>
</i18n:message>

<%
  for (int i = 0; i < length; i++) {		  // display the hits
    Hit hit = show[i];
    HitDetails detail = details[i];
    String title = detail.getValue("title");
    String url = detail.getValue("url");
    String summary = summaries[i];
    String id = "idx=" + hit.getIndexNo() + "&id=" + hit.getIndexDocNo();

    String licenseUrl = null;
    String mediaType = null;

    for (int j = detail.getLength(); --j >= 0;) {
      if (!detail.getField(j).equals("cc"))
        continue;
      String value = detail.getValue(j);
      if (value.startsWith("license="))
        licenseUrl = value;
      else if (value.equals("audio"))
        mediaType = "Audio";
      else if (value.equals("text"))
        mediaType = "Text";
      else if (value.equals("interactive"))
        mediaType = "Interactive";
      else if (value.equals("video"))
        mediaType = "MovingImage";
      else if (value.equals("image"))
        mediaType = "StillImage";
    }

    if (title == null || title.equals(""))        // use url for docs w/o title
      title = url;
    try {
    %>

<p class="resultitem"><strong><a href="<%=url%>"><%=Entities.encode(title)%></a></strong>

    <% if (licenseUrl == null) { %>
      <b>NL</b>
    <% } else if (licenseUrl.indexOf("publicdomain") != -1 || licenseUrl.indexOf("PublicDomain") != -1) { %>
    <img src="/img/pd.gif" alt="this file licensed under the public domain" />
    <% } else {
         if (licenseUrl.indexOf("by") != -1 || licenseUrl.indexOf("sampling") != -1) {
    %>
    <img src="/img/by.gif" title="this work requires author attribution" alt="this work requires author attribution" />
    <%   }
         if (licenseUrl.indexOf("nc") != -1) {
    %>
    <img src="/img/nc.gif" alt="this work can only be used non-commercially" title="this work can only be used non-commercially" />
    <%   }
         if (licenseUrl.indexOf("nd") != -1) {
    %>
    <img src="/img/nd.gif" alt="you may not make derivative works from this work" title="you may not make derivative works from this work" />
    <%   }
         if (licenseUrl.indexOf("sampling+") != -1) {
    %>
    <img src="/img/sampling+.gif" alt="this work can be used under the sampling plus license conditions" title="this work can be used under the sampling plus license conditions"  />
    <%   }
         else if (licenseUrl.indexOf("sampling") != -1) {
    %>
    <img src="/img/sampling.gif" alt="this work can be used under the sampling license conditions" title="this work can be used under the sampling license conditions"  />
    <%   }
         else if (licenseUrl.indexOf("sa") != -1) {
    %>
    <img src="/img/sa.gif" alt="this work must be licensed under an identical license if used" title="this work must be licensed under an identical license if used" />
    <%   }
       }
     } catch (Throwable t) { out.println(t); t.printStackTrace(); }
    %>

    <% if (!"".equals(summary)) { %>
    <br /><%=summary%>
    <% } %>
    <br />
    <span class="notes">
    <% if (mediaType != null) { %>
    (<%=mediaType%>)
    <% } %>
    (<a href="http://validator.creativecommons.org/validate.py?url=<%=URLEncoder.encode(url)%>">v</a>)
    <span class="url"><%=Entities.encode(url)%></span>
    <!--(<a href="/explain.jsp?<%=id%>&query=<%=URLEncoder.encode(queryString)%>"><i18n:message key="explain"/></a>)
    (<a href="/anchors.jsp?<%=id%>"><i18n:message key="anchors"/></a>)
    -->
    <% if (hit.moreFromSiteExcluded()) {
    String more =
    "q="+URLEncoder.encode("site:"+hit.getSite()+" "+queryString)
    +"&start="+start+"&hitsPerPage="+hitsPerPage+"&hitsPerSite="+0;%>
    (<a href="/?<%=more%>"><i18n:message key="moreFrom"/>
     <%=hit.getSite()%></a>)
    <% } %>
    </span></p>
<% } %>
<!-- end results -->
<%
if ((hits.totalIsExact() && end < hits.getTotal()) // more hits to show
    || (!hits.totalIsExact() && (hits.getLength() >= start+hitsPerPage))) {
%>
    <br />
    <form name="search" action="/" method="get">
    <input type="hidden" name="q" value="<%=htmlQueryString%>" />
    <input type="hidden" name="start" value="<%=end%>" />
    <input type="hidden" name="hitsPerPage" value="<%=hitsPerPage%>" />
    <input type="hidden" name="hitsPerSite" value="<%=hitsPerSite%>" />
    <input type="hidden" name="commercial" value="<%=commercial%>" />
    <input type="hidden" name="derivatives" value="<%=derivatives%>" />
    <input type="hidden" name="format" value="<%=format%>" />
    <input type="submit" value="<i18n:message key="next"/>" />
    </form>
<%
    }

if ((!hits.totalIsExact() && (hits.getLength() < start+hitsPerPage))) {
%>
    <form name="search" action="/" method="get">
    <input type="hidden" name="q" value="<%=htmlQueryString%>" />
    <input type="hidden" name="hitsPerPage" value="<%=hitsPerPage%>" />
    <input type="hidden" name="hitsPerSite" value="0" />
    <input type="hidden" name="commercial" value="<%=commercial%>" />
    <input type="hidden" name="derivatives" value="<%=derivatives%>" />
    <input type="hidden" name="format" value="<%=format%>" />
    <input type="submit" value="<i18n:message key="showAllHits"/>" />
    </form>
<%
    }

} // end if (hasQuery)
%>

</div>
<br />
<a href="http://www.nutch.org/">
<img border="0" style="padding-right: 10px" src="/img/poweredbynutch_01.gif" alt="powered by nutch" align="left" />
</a>
The search results are subject to this <a href="/what.html#disclaimer">Disclaimer</a>. Please read it to understand the nature of your search results.

</a>

<div style="clear:both">&nbsp;</div>
<jsp:include page="/include/footer.html"/>

</body>
</html>
