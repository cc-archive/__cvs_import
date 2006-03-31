<?php include('head.inc'); ?>

<div class="searchbox">
<h1><a href="http://creativecommons.org/">Creative Commons</a> RDF-enhanced search</h1>

<form action="./" class="searchform">
<?php
$DEBUG = 0;

$q = $_REQUEST[q];
$q = preg_replace('/\W+/',' ',$q);
$q = preg_replace('/^\s+/','',$q);
$q = preg_replace('/\s+$/','',$q);
$commercial = $_REQUEST[commercial];
$derivatives = $_REQUEST[derivatives];
$format = $_REQUEST[format];
$offset = $_REQUEST[o];
$d = $_REQUEST[d];
if ($commercial) { $commercial_checked = "checked"; }
if ($derivatives) { $derivatives_checked = "checked"; }

if ($format) {
  if ($format == "Audio") {
    $format_audio = 'selected';
  } else if ($format == "Image") {
    $format_image = 'selected';
  } else if ($format == "Interactive") {
    $format_interactive = 'selected';
  } else if ($format == "Text") {
    $format_text = 'selected';
  } else if ($format == "Video") {
    $format_video = 'selected';
  }
}

$p = <<<EOD

<input type="text" name="q" value="$q" class="box" /> <br />
<input type="submit" value="Search"/> <span class="notes">[<a href="help" onclick="window.open('help', 'help', 'width=500,height=300,scrollbars=yes,resizable=yes,toolbar=no,directories=no,location=no,menubar=no,status=yes');return false;">Help</a>]</span><br />
<div class="searchoptions">
<input type="checkbox" name="commercial" $commercial_checked /> I want to make commercial use.
<br /><input type="checkbox" name="derivatives" $derivatives_checked /> I want to create derivative works.
<br />Format: <select name="format">
<option value="">Any</option>
<option $format_audio>Audio</option>
<option $format_image>Image</option>
<option $format_interactive>Interactive</option>
<option $format_text>Text</option>
<option $format_video>Video</option>
</select>
</div>
EOD;
echo($p);
?>
</form>
</div>

<div class="resultsbox">

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
    $where_clause = "and license_id in (select license_id from rdfs_licenses where {$where_clause})";
  }
  
  if ($format) {
    if ($format == "Audio") {
      $format_clause .= "doc_type = 'http://purl.org/dc/dcmitype/Sound'";
    } else if ($format == "Image") {
      $format_clause .= "(doc_type = 'http://purl.org/dc/dcmitype/StillImage' OR doc_type = 'http://purl.org/dc/dcmitype/Image')";
    } else if ($format == "Interactive") {
      $format_clause .= "doc_type = 'http://purl.org/dc/dcmitype/Interactive'";
    } else if ($format == "Text") {
      $format_clause .= "doc_type = 'http://purl.org/dc/dcmitype/Text'";
    } else if ($format == "Video") {
      $format_clause .= "(doc_type = 'http://purl.org/dc/dcmitype/MovingImage' OR doc_type = 'http://purl.org/dc/dcmitype/Image')";
    }
  }
  if ($format_clause) {
    $format_clause = " and $format_clause";
  }
  if ($offset) {
    $newoffset = $offset + 20;
    $limit = "limit $newoffset offset $offset";
  } else if ($d) {
    $limit = 'limit 100';
  } else {
    $newoffset = 20;
    $limit = 'limit 21';
  }
  $qsql = preg_replace('/\s+/',' & ',$q);
  $query = <<<EOD
    select url, title, doc_type, properties, license_url,
    headline(stripped_content, to_tsquery('default', '{$qsql}'),'MinWords=50,MaxWords=100') as summary
    from (
    select rdfs_documents.oid as oid1
    from rdfs_documents
    where fti_index @@ to_tsquery('default', '{$qsql}')
    {$format_clause} {$where_clause}
    order by rank(fti_index, to_tsquery('default','{$qsql}')) desc
    {$limit}) as foo, rdfs_documents, rdfs_licenses
    where oid1 = rdfs_documents.oid
    and rdfs_documents.license_id = rdfs_licenses.license_id
EOD;

  if ($DEBUG) { echo("<pre>{$query}</pre>"); }
  $res = pg_query($query) or die(pg_last_error());
  $nrows = 0;
  for ($row = 0; $data = @pg_fetch_object($res, $row); $row++) {
    if ($newoffset && ++$nrows == 21) {
      break;
    }
    $url = $data->url;
    if (preg_match('/http:\/\/([\w\.]+)/',$url,$domain)) {
        $domain = $domain[1];
    }
    if ($d) {
        if ($d != $domain) {
	    continue;
	}
    } else {
        if ($domains[$domain]) {
            continue;
        }
        $domains[$domain] = 1;
    }
    $n++;
    $properties = $data->properties;
    $icons = '';
    if ($properties == "") {
      $icons = '<img src="http://creativecommons.org/images/license/pd.gif" alt="Public Domain" title="Public Domain" />';
    } else {
      if (preg_match('/by/',$properties)) {
        $icons = '<img src="http://creativecommons.org/icon/by/standard.gif" alt="Attribution" title="Attribution" />'; 
      }
      if (preg_match('/nc/',$properties)) {
        $icons .= ' <img src="http://creativecommons.org/icon/nc/standard.gif" alt="NonCommercial" title="NonCommercial" />'; 
      }
      if (preg_match('/nd/',$properties)) {
        $icons .= ' <img src="http://creativecommons.org/icon/nd/standard.gif" alt="NoDerivatives" title="NoDerivatives" />'; 
      }
      if (preg_match('/sa/',$properties)) {
        $icons .= ' <img src="http://creativecommons.org/icon/sa/standard.gif" alt="ShareAlike" title="ShareAlike" />'; 
      }


      if (preg_match('/sampling+/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/sampling+.gif" alt="Sampling Plus" title="Sampling Plus" />'; 
      } elseif (preg_match('/sampling/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/sampling.gif" alt="Sampling" title="Sampling" />'; 
      } elseif (preg_match('/LGPL/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/cclgpl.gif" alt="GNU LGPL" title="GNU LGPL" />'; 
      } elseif (preg_match('/GPL/',$license_url)) {
        $icons .= ' <img src="http://creativecommons.org/images/license/ccgpl.gif" alt="GNU GPL" title="GNU GPL" />'; 
      }
      
    }
    $summary = preg_replace('/\[.*?\]/','',$data->summary);
    echo("<p><strong><a href=\"{$url}\"  class=\"searchtitle\">{$data->title}</a></strong> {$icons}");
    echo("<br />{$summary}");
    $doc_type = $data->doc_type;
    if ($doc_type) {
      if (preg_match('/\w+$/',$doc_type,$matches)) {
        $doc_type = "[{$matches[0]}]";
      }
    }
    echo("<br /><span class=\"notes\">{$doc_type} [<a href=\"http://www.yergler.net/projects/ccvalidator/validate.py?url={$data->url}\">v</a>] {$data->url}");
    if (!$d) {
        echo(" [<a href=\"./?q={$q}&d={$domain}&commercial={$commercial}&derivatives={$derivatives}&format={$format}\">more from {$domain}</a>]");
    }
    echo ("</span></p>");
  }
}
if ($n == 0) {
  if ($q) {
    echo('<p>zero documents match query</p>');
  }
} else if (!$d && $nrows == 21) {
  echo("<p><a href=\"./?q={$q}&commercial={$commercial}&derivatives={$derivatives}&format={$format}&o={$newoffset}\">more matches</a></p>");
}

if ($q) {
  if ($commercial) {
    $commercial_yn = 'yes';
  }
  if ($derivatives) {
    $derivatives_yn = 'yes';
  }
  echo("<p>Try an approximation of this search via <a href=\"http://creativecommons.org/technology/search-redirect?keywords={$q}&commercial={$commercial_yn}&derivatives={$derivatives_yn}\">AlltheWeb</a>. <span class=\"notes\">[<a href=\"http://creativecommons.org/technology/search\">Info</a>]</span></p>");
}
?>

</div>

<?php include('foot.inc'); ?>
