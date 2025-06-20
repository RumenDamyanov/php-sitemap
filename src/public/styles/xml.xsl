<?xml version="1.0" encoding="UTF-8"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform" xmlns:s="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1" exclude-result-prefixes="s">
<xsl:template match="/">
<html lang="en">
<head>
<meta charset="utf-8"/>
<title>XML Sitemap</title>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/1.4.2/jquery.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/npm/tablesorter@2.31.3/dist/js/jquery.tablesorter.min.js"></script>
<script	type="text/javascript"><![CDATA[
$(document).ready(function() {
$("#sitemap").tablesorter({sortList:[[6,1],[4,1]],widgets:['zebra']});
$('.url').click(function(){
  window.open($(this).html());
  return false;
});
});]]></script>
<style type="text/css">
body
{
	font-family: Helvetica, Arial, sans-serif;
	font-size: 13px;
	color: #545353;
}
table
{
	border: none;
	border-collapse: collapse;
}
#sitemap tr.odd
{
	background-color: #eee;
}
#sitemap tbody tr:hover
{
	background-color: #ccc;
}
#sitemap tbody tr:hover td, #sitemap tbody tr:hover td a
{
	color: #000;
}
#content
{
	margin: 10px auto;
	max-width: 960px;
}
.expl
{
	margin: 10px 3px;
	line-height: 1.3em;
}
.expl a, .expl a:visited, footer a, footer a:visited
{
	color: #da3114;
	font-weight: bold;
}
a
{
	color: #000;
	text-decoration: none;
}
a:visited
{
	color: #777;
}
a:hover
{
	text-decoration: underline;
}
td
{
	font-size:11px;
}
th
{
	text-align:left;
	padding: 5px 20px 5px 5px;
    font-size: 12px;
}
thead th
{
	border-bottom: 1px solid #dedede;
	cursor: pointer;
}
footer
{
	margin:20px auto;
	text-align:left;
	max-width:100%;
}
.url:hover
{
	cursor:pointer;
}
</style>
</head>
  <body>
	<div id="content">
	  	<h2>XML Sitemap</h2>
		<p class="expl">Generated by <a href="https://gitlab.com/Laravelium/Sitemap" target="_blank" title="Sitemap generator for Laravel">laravelium-sitemap</a>. This is styled xml sitemap, sorted by update date.</p>
		<p class="expl">This sitemap contains <xsl:value-of select="count(s:urlset/s:url)"/> URLs.</p>
	    <table id="sitemap" class="tablesorter" border="1" cellpadding="3">
		<thead>
	      <tr bgcolor="#9acd32">
	        <th style="text-align:left">URL</th>
	        <th style="text-align:left">Alternates</th>
			<th style="text-align:left">Images</th>
			<th style="text-align:left">Videos</th>
	        <th style="text-align:left">Priority</th>
	        <th style="text-align:left">Update freq</th>
	        <th style="text-align:left">Updated at</th>
	      </tr>
		</thead>
		<tbody>
	      <xsl:for-each select="s:urlset/s:url">
	      <tr>
	        <td class="url"><xsl:value-of select="s:loc"/></td>
	        <td><xsl:value-of select="count(xhtml:link)"/></td>
	        <td><xsl:value-of select="count(image:image)"/></td>
	        <td><xsl:value-of select="count(video:video)"/></td>
	        <td><xsl:value-of select="concat(s:priority*100,'%')"/></td>
	        <td><xsl:value-of select="s:changefreq"/></td>
	        <td><xsl:value-of select="concat(substring(s:lastmod,0,11),concat(' ', substring(s:lastmod,12,5)))"/></td>
	      </tr>
	      </xsl:for-each>
		</tbody>
	    </table>
	    <footer></footer>
	</div>
  </body>
  </html>
</xsl:template>
</xsl:stylesheet>
