<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" 
     xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
     xmlns:tapir="http://rs.tdwg.org/tapir/1.0"
     xmlns:x="http://tapirlink/model/search/default"
     xmlns:skin="http://rs.tdwg.org/tapir/1.0/skin">
<xsl:output method="html" encoding="us-ascii"/>
<xsl:template match="/">
  <xsl:variable name="accesspoint" select="tapir:response/tapir:header/tapir:source/@accesspoint" />
  <xsl:variable name="base-url" select="substring-before($accesspoint, 'tapir.php')" />
  <xsl:variable name="skin" select="tapir:response/tapir:header/tapir:custom/skin:skin" />
  <html>
  <head>
  <title>TAPIR search result</title>
  <link rel="StyleSheet" href="{$base-url}skins/{$skin}/styles.css" type="text/css"/>
  </head>
  <body>
    <span class="Title">Search result</span><br/>
    <br/>
    <span class="RegularText">Total matched: <xsl:value-of select="tapir:response/tapir:search/tapir:summary/@totalMatched"/></span><br/>
    <br/>
    <table border="0" cellspacing="1" cellpadding="3" bgcolor="#999999">
      <tr bgcolor="#FFFFFF">
        <xsl:for-each select="tapir:response/tapir:search/x:records/x:record[1]/*/x:name">
        <th align="left" bgcolor="#FFFFFF"><b><xsl:value-of select="text()"/></b></th>
        </xsl:for-each>
      </tr>
      <xsl:for-each select="tapir:response/tapir:search/x:records/x:record">
      <tr bgcolor="#FFFFFF">
        <xsl:for-each select="*/x:value">
        <xsl:variable name="value" select="text()" />
        <td align="left" nowrap="nowrap">
        <xsl:choose>
          <xsl:when test="*|text()">
            <xsl:value-of select="$value"/>
          </xsl:when>
          <xsl:otherwise>
            <xsl:text> </xsl:text>
          </xsl:otherwise>
        </xsl:choose>
        </td>
        </xsl:for-each>
      </tr>
      </xsl:for-each>
    </table>
    <xsl:variable name="next" select="tapir:response/tapir:search/tapir:summary/@next" />
    <xsl:variable name="concept-id" select="tapir:response/tapir:search/x:records/@concept" />
    <xsl:variable name="value" select="tapir:response/tapir:search/x:records/@value" />
    <xsl:if test="$next >= 0">
    <br/><a href="{$accesspoint}?op=s&amp;xslt={$base-url}skins/{$skin}/search.xsl&amp;s={$next}&amp;l=20&amp;cnt=1&amp;e=1&amp;f={$concept-id}%20equals%20%22{$value}%22&amp;m={$base-url}skins/default/model.xml.php%3Fc%3D{$concept-id}%26v%3D{$value}%26a%3D{$accesspoint}">next</a>
    </xsl:if>
  </body>
  </html>
</xsl:template>
</xsl:stylesheet>
