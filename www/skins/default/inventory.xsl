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
  <title>TAPIR inventory result</title>
  <link rel="StyleSheet" href="{$base-url}skins/{$skin}/styles.css" type="text/css"/>
  </head>
  <body>
    <span class="Title">Inventory result</span><br/>
    <br/>
    <span class="RegularText">Total matched: <xsl:value-of select="tapir:response/tapir:inventory/tapir:summary/@totalMatched"/></span><br/>
    <br/>
    <xsl:variable name="concept-id" select="tapir:response/tapir:inventory/tapir:concepts/tapir:concept/@id" />
    <table border="0" cellspacing="1" cellpadding="3" bgcolor="#999999">
      <tr bgcolor="#FFFFFF">
        <th align="left" bgcolor="#FFFFFF"><b><xsl:value-of select="tapir:response/tapir:inventory/tapir:concepts/tapir:concept/@id"/></b></th>
        <th align="center" bgcolor="#FFFFFF"><b>count</b></th>
      </tr>
      <xsl:for-each select="tapir:response/tapir:inventory/tapir:record">
      <tr bgcolor="#FFFFFF">
        <td align="left"><a href="{$accesspoint}?op=s&amp;xslt={$base-url}skins/{$skin}/search.xsl&amp;s=0&amp;l=20&amp;cnt=1&amp;e=1&amp;f={$concept-id}%20equals%20%22{tapir:value}%22&amp;m={$base-url}skins/{$skin}/model.xml.php%3Fc%3D{$concept-id}%26v%3D{tapir:value}%26a%3D{$accesspoint}"><xsl:value-of select="tapir:value"/></a></td>
        <td align="center"><xsl:value-of select="@count"/></td>
      </tr>
      </xsl:for-each>
    </table>
    <xsl:variable name="next" select="tapir:response/tapir:inventory/tapir:summary/@next" />
    <xsl:if test="$next >= 0">
    <br/><a href="{$accesspoint}?op=i&amp;xslt={$base-url}skins/{$skin}/inventory.xsl&amp;s={$next}&amp;l=20&amp;cnt=1&amp;c={$concept-id}">next</a>
    </xsl:if>
  </body>
  </html>
</xsl:template>
</xsl:stylesheet>
