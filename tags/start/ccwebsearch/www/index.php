<?php include('head.inc'); ?>

<center>
<h1><a href="http://creativecommons.org/">Creative Commons</a> RDF-enhanced search PROTOTYPE</h1>

<form action="./">
<?php
$q = $_REQUEST[q];
$commercial = $_REQUEST[commercial];
$derivatives = $_REQUEST[derivatives];
$format = $_REQUEST[format];
if ($commercial) { $commercial_checked = "checked"; }
if ($derivatives) { $derivatives_checked = "checked"; }

if ($format) {
  if ($format == "Audio") {
    $format_audio = 'selected';
  } else if ($format == "Image") {
    $format_image = 'selected';
  } else if ($format == "Text") {
    $format_text = 'selected';
  } else if ($format == "Video") {
    $format_video = 'selected';
  }
}

$p = <<<EOD
<input type="text" name="q" value="$q"/>
<input type="submit" value="Search"/> <small>[<a href="#help">Help</a>]</small>
<br/><input type="checkbox" name="commercial" $commercial_checked/> I want to make commercial use.
<br/><input type="checkbox" name="derivatives" $derivatives_checked"/> I want to create derivative works.
<br/>Format: <select name="format">
<option value="">Any</option>
<option $format_audio>Audio</option>
<option $format_image>Image</option>
<option $format_text>Text</option>
<option $format_video>Video</option>
</select>
EOD;
echo($p);
?>
</form>
</center>

<?php

$n = 0;
if ($q) {
  $link=pg_connect('user=ml dbname=cc') or die;
  $where_clause = '';
  if ($commercial) {
    $where_clause .= "properties not like '%nc%'";
  }
  
  if ($derivatives) {
    if ($where_clause) {
      $where_clause .= " and ";
    }
    $where_clause .= "properties not like '%nd%'";
  }
  
  if ($where_clause) {
    $where_clause = "where {$where_clause}";
  }
  
  if ($format) {
    if ($format == "Audio") {
      $format_clause .= "doc_type = 'http://purl.org/dc/dcmitype/Sound'";
    } else if ($format == "Image") {
      $format_clause .= "(doc_type = 'http://purl.org/dc/dcmitype/StillImage' OR doc_type = 'http://purl.org/dc/dcmitype/Image')";
    } else if ($format == "Text") {
      $format_clause .= "doc_type = 'http://purl.org/dc/dcmitype/Text'";
    } else if ($format == "Video") {
      $format_clause .= "(doc_type = 'http://purl.org/dc/dcmitype/MovingImage' OR doc_type = 'http://purl.org/dc/dcmitype/Image')";
    }
  }
  if ($format_clause) {
    $format_clause = " and $format_clause";
  }
  $q = preg_replace('/\s+/',' & ',$q);
  $query = <<<EOD
    select url,title, to_char(download_date, 'MM/DD/YYYY') as pretty_download_date,
    headline(creator || title || stripped_content, to_tsquery('default', '{$q}'),'MinWords=50,MaxWords=100') as summary,
    properties, doc_type, license_url
    from rdfs_documents, rdfs_licenses
    where fti_index @@ to_tsquery('default', '{$q}') and
    rdfs_documents.license_id = rdfs_licenses.license_id {$format_clause}
    and rdfs_licenses.license_id in
    (select license_id from rdfs_licenses {$where_clause})
    order by rank(fti_index, to_tsquery('default','{$q}'))
EOD;

  #echo("<pre>{$query}</pre>");
  $res = pg_query($query) or die(pg_last_error());
  for ($row = 0; $data = @pg_fetch_object($res, $row); $row++) {
    $n++;
    $properties = $data->properties;
    $icons = '';
    if ($properties == "") {
      $icons = '<img src="http://creativecommons.org/images/license/pd.gif"/>';
    } else {
      if (preg_match('/by/',$properties)) {
        $icons = '<img src="http://creativecommons.org/icon/by/standard.gif"/>'; 
      }
      if (preg_match('/nc/',$properties)) {
        $icons .= ' <img src="http://creativecommons.org/icon/nc/standard.gif"/>'; 
      }
      if (preg_match('/nd/',$properties)) {
        $icons .= ' <img src="http://creativecommons.org/icon/nd/standard.gif"/>'; 
      }
      if (preg_match('/sa/',$properties)) {
        $icons .= ' <img src="http://creativecommons.org/icon/sa/standard.gif"/>'; 
      }


      if (preg_match('/sampling+/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/sampling+.gif"/>'; 
      } elseif (preg_match('/sampling/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/sampling.gif"/>'; 
      } elseif (preg_match('/LGPL/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/cclgpl.gif"/>'; 
      } elseif (preg_match('/GPL/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/ccgpl.gif"/>'; 
      }
      
    }
    $summary = preg_replace('/\[.*?\]/','',$data->summary);
    echo("<p><strong><a href=\"{$data->url}\">{$data->title}</a></strong> {$icons}");
    echo("<br/>{$summary}");
    $doc_type = $data->doc_type;
    if ($doc_type) {
      if (preg_match('/\w+$/',$doc_type,$matches)) {
        $doc_type = "[{$matches[0]}]";
      }
    }
    echo("<br/><small>{$doc_type} <em>{$data->url}</em></small></p>");
  }
}
if ($n == 0) {
  if ($q) {
    echo('<p align="center">zero documents match query</p>');
  }
  echo('<p align="center">NB: very few documents currently indexed, this is a proto-prototype.  Sample query: <a href="./?q=lessig">lessig</a></p>.');
}
?>

<br/>

<a name="help"></a>
<blockquote>
<p><strong>Search Help</strong></p>
<dl>
<dt>I want to make commercial use.</dt>
<dd>Will eliminate works with licenses that forbid
<a href="http://creativecommons.org/characteristic/nc" onclick="window.open('http://creativecommons.org/characteristic/nc', 'characteristic_help', 'width=375,height=300,scrollbars=yes,resizable=yes,toolbar=no,directories=no,location=yes,menubar=no,status=yes');return false;">commercial use</a>.<dd>
<dt>I want to create derivative works.</dt>
<dd>Will eliminate works with licenses that forbid
<a href="http://creativecommons.org/characteristic/nd" onclick="window.open('http://creativecommons.org/characteristic/nd', 'characteristic_help', 'width=375,height=300,scrollbars=yes,resizable=yes,toolbar=no,directories=no,location=yes,menubar=no,status=yes');return false;">derivative works</a>.<dd>
<dt>Format</dt>
<dd>Will restrict search to documents with explicit format metadata.  Note that very few documents have such metadata, so your results will be very thin.  To add format metadata to your document, go to the Creative Commons <a href="http://creativecommons.org/license/">license selection tool</a> and select a format type.  Update your document with the metadata provided.<dd>
<dt>Disclaimer</dt>
<dd>This search finds web pages with metadata indicating a Creative Commons license or public domain dedication. User must examine each page to determine what content, if any, has been licensed.</dd>
</dl>
</blockquote>

<?php include('foot.inc'); ?>
