<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" 
     xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
     xmlns:tapir="http://rs.tdwg.org/tapir/1.0"
     xmlns:dc="http://purl.org/dc/elements/1.1/"
     xmlns:dct="http://purl.org/dc/terms/"
     xmlns:geo="http://www.w3.org/2003/01/geo/wgs84_pos#"
     xmlns:vcard="http://www.w3.org/2001/vcard-rdf/3.0#"
     xmlns:skin="http://rs.tdwg.org/tapir/1.0/skin">
<xsl:output method="html" encoding="us-ascii"/>
<xsl:template match="/">
  <xsl:variable name="accesspoint" select="tapir:response/tapir:header/tapir:source/@accesspoint" />
  <xsl:variable name="base-url" select="substring-before($accesspoint, 'tapir.php')" />
  <xsl:variable name="skin" select="tapir:response/tapir:header/tapir:custom/skin:skin" />
  <html>
  <head>
  <title>TAPIR metadata</title>
  <link rel="StyleSheet" href="{$base-url}skins/{$skin}/styles.css" type="text/css"/>
  </head>
  <body bgcolor="#FFFFFF">

    <span class="Title"><xsl:value-of select="tapir:response/tapir:metadata/dc:title"/></span><br/>

    <br/>
    <span class="RegularText"><xsl:value-of select="tapir:response/tapir:metadata/dc:description"/></span><br/>
    <br/>

    <xsl:variable name="language" select="tapir:response/tapir:metadata/dc:language" />
    <span class="Label">Main language</span><br/><span class="RegularText">
      <xsl:choose>
        <xsl:when test='$language="en"'>english</xsl:when>
        <xsl:when test='$language="fr"'>french</xsl:when>
        <xsl:when test='$language="de"'>german</xsl:when>
        <xsl:when test='$language="pt"'>portuguese</xsl:when>
        <xsl:when test='$language="es"'>spanish</xsl:when>
        <xsl:when test='$language="it"'>italian</xsl:when>
        <xsl:otherwise>unknown (<xsl:value-of select="$language"/>)</xsl:otherwise>
      </xsl:choose>
    </span><br/>
    <br/>

    <xsl:if test="tapir:response/tapir:metadata/dc:rights">
    <span class="Label">Rights</span><br/><span class="RegularText"><xsl:value-of select="tapir:response/tapir:metadata/dc:rights"/></span><br/>
    <br/>
    </xsl:if>

    <xsl:if test="tapir:response/tapir:metadata/dc:bibliographicCitation">
    <span class="Label">Citation</span><br/><span class="RegularText"><xsl:value-of select="tapir:response/tapir:metadata/dc:bibliographicCitation"/></span><br/>
    <br/>
    </xsl:if>

    <xsl:if test="tapir:response/tapir:metadata/dc:subject">
    <span class="Label">Keywords</span><br/><span class="RegularText"><xsl:value-of select="tapir:response/tapir:metadata/dc:subject"/></span><br/>
    </xsl:if>
    <br/>

    <span class="Label">Related entities</span><br/>
    <br/>
    <xsl:for-each select="tapir:response/tapir:metadata/tapir:relatedEntity">

    <span class="Section"><xsl:value-of select="tapir:entity/tapir:name"/> (<xsl:value-of select="tapir:entity/tapir:acronym"/>)</span><br/>
    <br/>
 
    <xsl:if test="tapir:entity/tapir:description">
    <span class="RegularText"><xsl:value-of select="tapir:entity/tapir:description"/></span><br/>
    </xsl:if>
    <br/>
    <xsl:if test="tapir:entity/tapir:relatedInformation">
    <xsl:variable name="related-info" select="tapir:entity/tapir:relatedInformation" />
    <a href="{$related-info}"><xsl:value-of select="tapir:entity/tapir:relatedInformation"/></a><br/><br/>
    </xsl:if>

    <span class="SubLabel">Roles</span><br/>
    <xsl:for-each select="tapir:role">
    <span class="RegularText"><xsl:value-of select="text()"/><xsl:if test="not(position()=last())">, </xsl:if></span> 
    </xsl:for-each><br/>
    <br/>

    <span class="SubLabel">Address</span><br/><span class="RegularText"><xsl:value-of select="tapir:entity/tapir:address"/><xsl:if test="tapir:entity/tapir:regionCode">, <xsl:value-of select="tapir:entity/tapir:regionCode"/></xsl:if><xsl:if test="tapir:entity/tapir:countryCode">, <xsl:value-of select="tapir:entity/tapir:countryCode"/></xsl:if><xsl:if test="tapir:entity/geo:Point"><br/>(lat: <xsl:value-of select="tapir:entity/geo:Point/geo:lat"/>, long: <xsl:value-of select="tapir:entity/geo:Point/geo:long"/>)</xsl:if></span><br/>
    
    <br/>
    <span class="SubLabel">Contacts</span><br/>
    <xsl:for-each select="tapir:entity/tapir:hasContact">
    <span class="RegularText"><xsl:value-of select="vcard:VCARD/vcard:FN"/></span><br/>
    <span class="RegularText"><xsl:value-of select="vcard:VCARD/vcard:TITLE"/> (<xsl:for-each select="tapir:role"><xsl:value-of select="text()"/><xsl:if test="not(position()=last())">, </xsl:if></xsl:for-each>)</span><br/>    <span class="RegularText"><xsl:value-of select="vcard:VCARD/vcard:EMAIL"/></span><br/>
    <span class="RegularText"><xsl:value-of select="vcard:VCARD/vcard:TEL"/></span><br/>

    </xsl:for-each>
    <br/>
    </xsl:for-each>

    <hr/>
    <span class="RegularText">Created in <xsl:value-of select="tapir:response/tapir:metadata/dct:created"/></span><br/>
    <span class="RegularText">Last modified in <xsl:value-of select="tapir:response/tapir:metadata/dct:modified"/></span><br/>
    <br/>

    <a href="{$accesspoint}?op=c&amp;xslt={$base-url}skins/{$skin}/capabilities.xsl">See TAPIR capabilities</a>

  </body>
  </html>
</xsl:template>
</xsl:stylesheet>
