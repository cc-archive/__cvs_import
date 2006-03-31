<?xml version="1.0"?>
<xsl:stylesheet version="1.0" xmlns:xsl="http://www.w3.org/1999/XSL/Transform">
	<xsl:output method="xml" indent="yes"/>

 	<xsl:template match="answers">
		<xsl:apply-templates/>
	</xsl:template>

	<xsl:variable name="license-base" select="'http://creativecommons.org/licenses/'"/>

	<xsl:template match="work-info"/>

 	<xsl:template match="license-standard">
		<xsl:variable name="license-uri">
			<xsl:variable name="jurisdiction">
				<xsl:if test="./jurisdiction != ''"><xsl:value-of select="concat(./jurisdiction,'/')"/></xsl:if>
			</xsl:variable>
			<xsl:variable name="version">
				<xsl:choose>
					<xsl:when test="./jurisdiction='fi' or ./jurisdiction='nl'">1.0</xsl:when>
					<xsl:otherwise>2.0</xsl:otherwise>
				</xsl:choose>
			</xsl:variable>
			<xsl:variable name="noncommercial">
				<xsl:if test="./commercial='n'">-nc</xsl:if>
			</xsl:variable>
			<xsl:variable name="derivatives">
				<xsl:choose>
					<xsl:when test="./derivatives='n'">-nd</xsl:when>
					<xsl:when test="./derivatives='sa'">-sa</xsl:when>
				</xsl:choose>
			</xsl:variable>
			<xsl:value-of select="concat($license-base,'by',$noncommercial,$derivatives,'/',$version,'/',$jurisdiction)"/>
		</xsl:variable>
		<xsl:call-template name="output">
			<xsl:with-param name="license-uri" select="$license-uri"/>
		</xsl:call-template>
 	</xsl:template>

 	<xsl:template match="license-publicdomain">
		<xsl:call-template name="output">
			<xsl:with-param name="license-uri" select="concat($license-base,'publicdomain/')"/>
		</xsl:call-template>
 	</xsl:template>

 	<xsl:template match="license-recombo">
		<xsl:variable name="license-uri">
			<xsl:variable name="jurisdiction">
				<xsl:if test="./jurisdiction != ''"><xsl:value-of select="concat(./jurisdiction,'/')"/></xsl:if>
			</xsl:variable>
			<xsl:variable name="share">
				<xsl:if test="./share='y'">+</xsl:if>
			</xsl:variable>
			<xsl:value-of select="concat($license-base,'sampling',$share,'/1.0/',$jurisdiction)"/>
		</xsl:variable>
		<xsl:call-template name="output">
			<xsl:with-param name="license-uri" select="$license-uri"/>
		</xsl:call-template>
 	</xsl:template>

 	<xsl:template match="license-gpl">
		<xsl:call-template name="output">
			<xsl:with-param name="license-uri" select="concat($license-base,'GPL/2.0/')"/>
		</xsl:call-template>
 	</xsl:template>

 	<xsl:template match="license-lgpl">
		<xsl:call-template name="output">
			<xsl:with-param name="license-uri" select="concat($license-base,'LGPL/2.1/')"/>
		</xsl:call-template>
 	</xsl:template>

	<xsl:template name="rdf">
		<xsl:param name="license-uri"/>
		<xsl:variable name="license-uri-rdf">
			<xsl:choose>
				<xsl:when test="$license-uri = concat($license-base,'publicdomain/')">http://web.resource.org/cc/PublicDomain</xsl:when>
				<xsl:otherwise><xsl:value-of select="$license-uri"/></xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<rdf:RDF xmlns="http://web.resource.org/cc/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">
			<Work rdf:about="">
				<xsl:if test="/answers/work-info/title">
					<dc:title><xsl:value-of select="/answers/work-info/title"/></dc:title>
				</xsl:if>
				<xsl:if test="/answers/work-info/type">
					<dc:type rdf:resource="http://purl.org/dc/dcmitype/{/answers/work-info/type}" />
				</xsl:if>
				<license rdf:resource="{$license-uri-rdf}" />
			</Work>
			<License rdf:about="{$license-uri-rdf}">
				<permits rdf:resource="http://web.resource.org/cc/Reproduction" />
				<xsl:choose>
					<xsl:when test="starts-with($license-uri,concat($license-base,'sampling+/'))">
   						<permits rdf:resource="http://web.resource.org/cc/Sharing" />
					</xsl:when>
					<xsl:when test="not(starts-with($license-uri,concat($license-base,'sampling/')))">
   						<permits rdf:resource="http://web.resource.org/cc/Distribution" />
					</xsl:when>
				</xsl:choose>
				<xsl:if test="not(contains($license-uri,'publicdomain'))">
					<requires rdf:resource="http://web.resource.org/cc/Notice" />
				</xsl:if>
				<xsl:if test="not(contains($license-uri,'publicdomain') or contains($license-uri,'GPL'))">
					<requires rdf:resource="http://web.resource.org/cc/Attribution" />
				</xsl:if>
				<xsl:if test="contains($license-uri,'-nc')">
					<prohibits rdf:resource="http://web.resource.org/cc/CommercialUse" />
				</xsl:if>
				<xsl:if test="not(contains($license-uri,'-nd'))">
					<permits rdf:resource="http://web.resource.org/cc/DerivativeWorks" />
				</xsl:if>
				<xsl:if test="contains($license-uri,'-sa')">
					<requires rdf:resource="http://web.resource.org/cc/ShareAlike" />
				</xsl:if>
			</License>
		</rdf:RDF>
	</xsl:template>

	<xsl:template name="html">
		<xsl:param name="license-uri"/>
		<xsl:param name="rdf"/>
		<xsl:comment>Creative Commons License</xsl:comment>
		<xsl:variable name="license-button">
			<xsl:choose>
				<xsl:when test="contains($license-uri,'publicdomain')">norights.gif</xsl:when>
				<xsl:when test="contains($license-uri,'sampling')">recombo.gif</xsl:when>
				<xsl:when test="contains($license-uri,'LGPL')">cc-LGPL-a.png</xsl:when>
				<xsl:when test="contains($license-uri,'GPL')">cc-GPL-a.png</xsl:when>
				<xsl:otherwise>somerights20.gif</xsl:otherwise>
			</xsl:choose>
		</xsl:variable>
		<a rel="license" href="{$license-uri}"><img alt="Creative Commons License" border="0" src="http://creativecommons.org/images/public/{$license-button}" /></a><br />
		This work is licensed under a <a rel="license" href="{$license-uri}">Creative Commons License</a>.
		<xsl:comment>/Creative Commons License</xsl:comment>

		<xsl:text disable-output-escaping="yes">&lt;!-- </xsl:text>
			<xsl:copy-of select="$rdf"/>
		<xsl:text disable-output-escaping="yes"> --&gt;</xsl:text>

	</xsl:template>

	<xsl:template name="output">
		<xsl:param name="license-uri"/>
		<xsl:variable name="rdf">
			<xsl:call-template name="rdf">
				<xsl:with-param name="license-uri" select="$license-uri"/>
			</xsl:call-template>
		</xsl:variable>
		<xsl:variable name="html">
			<xsl:call-template name="html">
				<xsl:with-param name="license-uri" select="$license-uri"/>
				<xsl:with-param name="rdf" select="$rdf"/>
			</xsl:call-template>
		</xsl:variable>
		<result>
			<license-uri><xsl:value-of select="$license-uri"/></license-uri>
			<rdf><xsl:copy-of select="$rdf"/></rdf>
			<html><xsl:copy-of select="$html"/></html>
		</result>
	</xsl:template>

</xsl:stylesheet>
