<?xml version="1.0" encoding="ISO-8859-1"?>
<xsl:stylesheet version="1.0" 
     xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
     xmlns:tapir="http://rs.tdwg.org/tapir/1.0"
     xmlns:skin="http://rs.tdwg.org/tapir/1.0/skin">
<xsl:output method="html" encoding="us-ascii"/>
<xsl:template match="/">
  <xsl:variable name="accesspoint" select="tapir:response/tapir:header/tapir:source/@accesspoint" />
  <xsl:variable name="base-url" select="substring-before($accesspoint, 'tapir.php')" />
  <xsl:variable name="skin" select="tapir:response/tapir:header/tapir:custom/skin:skin" />
  <html>
  <head>
  <title>TAPIR capabilities</title>
  <link rel="StyleSheet" href="{$base-url}skins/{$skin}/styles.css" type="text/css"/>
  </head>
  <body>
    <span class="Title">Capabilities</span><br/>
    <br/>

    <span class="Label">Operations</span><br/>
    <span class="RegularText">ping, metadata, capabilities<xsl:if test="tapir:response/tapir:capabilities/tapir:operations/tapir:inventory">, inventory</xsl:if><xsl:if test="tapir:response/tapir:capabilities/tapir:operations/tapir:search">, search</xsl:if></span><br/>
    <br/>

    <span class="Label">Request encoding</span><br/>
    <span class="RegularText">KVP<xsl:if test="tapir:response/tapir:capabilities/tapir:requests/tapir:encoding/tapir:xml">, XML</xsl:if></span><br/>
    <br/>

    <span class="Label">Inventory capabilities</span><br/>
    <span class="RegularText">any concepts</span><br/>
    <br/>

    <span class="Label">Search capabilities</span><br/>
    <span class="RegularText">any output models (basic schema language)</span><br/>
    <br/>

    <span class="Label">Filters</span><br/>
    <span class="RegularText">
    <xsl:choose>
    <xsl:when test="tapir:response/tapir:capabilities/tapir:requests/tapir:filter/tapir:encoding">supported ("like" operators case-<xsl:if test='tapir:response/tapir:capabilities/tapir:requests/tapir:filter/tapir:encoding/tapir:booleanOperators/tapir:comparative/tapir:like/@caseSensitive="false"'>in</xsl:if>sensitive / "equals" operators case-<xsl:if test='tapir:response/tapir:capabilities/tapir:requests/tapir:filter/tapir:encoding/tapir:booleanOperators/tapir:comparative/tapir:equals/@caseSensitive="false"'>in</xsl:if>sensitive)</xsl:when>
    <xsl:otherwise>not supported</xsl:otherwise>
    </xsl:choose>
    </span><br/>
    <br/>

    <span class="Label">Environment variables</span><br/>
    <span class="RegularText">
    <xsl:for-each select="tapir:response/tapir:capabilities/tapir:variables/tapir:environment/*">
    <xsl:value-of select="name()"/><xsl:if test="not(position()=last())">, </xsl:if>
    </xsl:for-each>
    </span><br/>
    <br/>

    <span class="Label">Log-only requests</span><br/>
    <span class="RegularText"><xsl:value-of select="tapir:response/tapir:capabilities/tapir:requests/tapir:globalParameters/tapir:logOnly"/></span><br/>
    <br/>

    <span class="Label">XSLT on server side</span><br/>
    <span class="RegularText">not supported</span><br/>
    <br/>

    <span class="Label">Response limitations</span><br/>
    <span class="RegularText">Maximum element repetitions: <xsl:value-of select="tapir:response/tapir:capabilities/tapir:settings/tapir:maxElementRepetitions"/></span><br/>
    <span class="RegularText">Maximum element levels: <xsl:value-of select="tapir:response/tapir:capabilities/tapir:settings/tapir:maxElementLevels"/></span><br/>
    <br/>

    <span class="Label">Mapped schemas</span><br/>
    <xsl:for-each select="tapir:response/tapir:capabilities/tapir:concepts/tapir:schema">
    <br/>
    <table border="0" cellspacing="0" cellpadding="2">
      <tr bgcolor="#FFFFFF">
        <th bgcolor="#FFFFFF" align="left" class="SubLabel"><xsl:value-of select="@namespace"/></th>
      </tr>
      <xsl:for-each select="tapir:mappedConcept">
      <tr bgcolor="#FFFFFF" align="left">
        <td><a href="{$accesspoint}?op=i&amp;xslt={$base-url}skins/{$skin}/inventory.xsl&amp;s=0&amp;l=20&amp;cnt=1&amp;c={@id}"><xsl:value-of select="@id"/></a></td>
      </tr>
      </xsl:for-each>
    </table>
    </xsl:for-each>
  </body>
  </html>
</xsl:template>
</xsl:stylesheet>
