<xsl:stylesheet version="1.0"
                xmlns:xsl="http://www.w3.org/1999/XSL/Transform"
                xmlns="http://www.w3.org/TR/xhtml1/strict">
<!-- author: Nigel Swinson nigelswinson@users.sourceforge.net -->
<!-- version: 1.0.1 -->

<xsl:template match="/">

	<table border="0" width="100%" cellspacing="0">
		<tr bgcolor="#000000">
			<th>
				<font size="5" color="#ffffff" face="Arial">
				<xsl:if test="//FileInfo/Version">
					<small style="float:right; margin-right:10">version <xsl:value-of select="//FileInfo/Version"/></small>
				</xsl:if>
				<xsl:value-of select="//FileInfo/Name"/> documentation
				</font>
			</th>
		</tr>
	</table>

	<p>Documentation for the <b><xsl:value-of select="//FileInfo/FileName"/></b> file</p>

	<xsl:variable name="TopClassName" select="substring-before(//FileInfo/FileName, '.class')"/>

	<h1>Contents</h1>
	
	<ul>
		<xsl:if test="//FileInfo">
				<li><a href="#Introduction">Introduction</a></li>
		</xsl:if>
		<xsl:if test="$TopClassName">
			<li><a href="#CompletePublicInterface">The complete public interface</a></li>
		</xsl:if>
		<xsl:for-each select="//Class">
			<xsl:call-template name="ClassListEntry"/>
		</xsl:for-each>
	</ul>

	<hr/>

	<xsl:apply-templates select="//FileInfo"/>

	<xsl:if test="$TopClassName">
		<h1 id="CompletePublicInterface">The complete public interface</h1>

		<p>Public functions of <b><xsl:value-of select="$TopClassName"/></b></p>
		<xsl:call-template name="RecursiveFunctionList">
			<xsl:with-param name="ClassName" select="$TopClassName"/>
		</xsl:call-template>

		<hr/>
	</xsl:if>

	<xsl:apply-templates select="//Class"/>
</xsl:template>

<xsl:template match="FileInfo">
	<h1 id="Introduction">Introduction</h1>

	<xsl:if test="Name and Author">
		<p><xsl:value-of select="Name"/> by <xsl:value-of select="Author"/>.</p>
	</xsl:if>

	<pre><xsl:value-of select="Comment"/></pre>	

</xsl:template>

<xsl:template name="RecursiveFunctionList">
  <xsl:param name="ClassName"/>
	<ul>
		<xsl:for-each select="//Function[(../ClassName = $ClassName) and not(starts-with(FunctionName, '_')) and not(Deprecate)]">
			<xsl:call-template name="FunctionListEntry"/>
		</xsl:for-each>
	</ul>
	<!-- If this class has a base class, then go get all it's inherited members. -->
	<xsl:variable name="BaseClassName" select="//Class[ClassName = $ClassName]/BaseClassName"/>
	<xsl:if test="$BaseClassName">
		<p>Public base class members inherited from <b><xsl:value-of select="$BaseClassName"/></b></p>	
		<xsl:call-template name="RecursiveFunctionList">
			<xsl:with-param name="ClassName" select="$BaseClassName"/>
		</xsl:call-template>
	</xsl:if>
</xsl:template>

<xsl:template match="Class">
	<h1 id='{ClassName}'>Class <xsl:value-of select="ClassName"/></h1>

	<xsl:if test="BaseClassName">
		<p>The <b><xsl:value-of select="ClassName"/></b> class extends the 
		<xsl:variable name="BaseClassName" select="BaseClassName"/>
		<xsl:choose>
			<xsl:when test="//Class[ClassName = $BaseClassName]">
				<a href="#{BaseClassName}"><xsl:value-of select="BaseClassName"/></a>
			</xsl:when>
			<xsl:otherwise>
				<xsl:value-of select="BaseClassName"/>
			</xsl:otherwise>
		</xsl:choose>
		class.</p>
	</xsl:if>

	<ul>
		<li><a href="#{ClassName}-PublicList">Public Methods</a></li>
		<li><a href="#{ClassName}-PrivateList">Private Methods</a></li>
		<li><a href="#{ClassName}-PublicMethods">Public Methods Detail</a></li>
		<li><a href="#{ClassName}-PrivateMethods">Private Methods Detail</a></li>
	</ul>
	
	<h2 id="{ClassName}-PublicList">Public Methods</h2>

	<ul>
		<xsl:for-each select="Function[not(starts-with(FunctionName, '_')) and not(Deprecate)]">
			<xsl:call-template name="FunctionListEntry"/>
		</xsl:for-each>
	</ul>

	<xsl:if test="count(Function[not(starts-with(FunctionName, '_')) and Deprecate])">
		<h2>Depreciated public methods</h2>
		<ul>
		<xsl:for-each select="Function[not(starts-with(FunctionName, '_')) and Deprecate]">
			<li><nobr>
				<a href="#{FunctionName}">
					<span class="label" style="width:200">
						<xsl:value-of select="FunctionName"/>
					</span>
				</a> : 
				<span style="margin-top:0;">
					<xsl:value-of select="Deprecate"/>
				</span>
				</nobr>
			</li>
		</xsl:for-each>
		</ul>
	</xsl:if>
	
	<h2 id="{ClassName}-PrivateList">Private Methods</h2>

	<p>You really shouldn't be raking about in these functions, as you should only be
	using the public interface.  But if you need more control, then these are the
	internal functions that you can use if you want to get your hands really dirty.</p>

	<ul>
		<xsl:for-each select="Function[starts-with(FunctionName, '_')]">
			<xsl:call-template name="FunctionListEntry"/>
		</xsl:for-each>
	</ul>

	<h2 id="{ClassName}-PublicMethods">Public Method Detail</h2>
	<blockquote class="methods">
		<xsl:apply-templates select="Function[not(starts-with(FunctionName, '_'))]"/>
	</blockquote>
	
	<h2 id="{ClassName}-PrivateMethods">Private Method Detail</h2>
	<blockquote class="methods">
		<xsl:apply-templates select="Function[starts-with(FunctionName, '_')]"/>
	</blockquote>
</xsl:template>

<xsl:template name="ClassListEntry">
	<li><nobr>
		class - <a href="#{ClassName}">
			<xsl:value-of select="ClassName"/>
		</a>
		</nobr>
	</li>
</xsl:template>

<xsl:template name="FunctionListEntry">
	<xsl:text>
		<li>
			<nobr>
				<span class="label" style="width:330">
					<a href="#{FunctionName}">
							<xsl:value-of select="FunctionName"/>
					</a> : 
				</span>
				<span style="margin-top:0;">
					<xsl:value-of select="ShortComment"/>
				</span>
			</nobr>
		</li>
	</xsl:text>
</xsl:template>

<xsl:template match="//Function">
	<h3 id='{FunctionName}'>
	 Method Details: <xsl:value-of select="FunctionName"/>
	</h3>

	<hr/><br/>

	<span class="content">
		<p class="prototype"><xsl:value-of select="Prototype"/></p>
		<p class="description"><xsl:value-of select="ShortComment"/></p>

		<pre class="description"><xsl:value-of select="Comment"/></pre>

		<xsl:if test="./Deprecate">
			<p class="description"><xsl:value-of select="Deprecate"/></p>
		</xsl:if>

		<xsl:if test="./Parameters">
			<p class="label">Parameter:</p>
			<dl>
			<xsl:apply-templates select="Parameters"/>
			</dl>
		</xsl:if>

		<xsl:if test="./Return">
			<p class="label">Return Value:</p>
			<span class="parameter" style="float:left"><i><xsl:value-of select="Return/@Type"/></i></span>
			<span class="parameter"><p class="description"><xsl:value-of select="Return"/></p></span>
		</xsl:if>

		<xsl:if test="./Author">
			<p class="label">Author:</p>
			<p class="description"><xsl:value-of select="Author"/></p>
		</xsl:if>

		<xsl:if test="./See">
			<p class="label">Similar Functions:</p>
			<p class="description"><xsl:value-of select="See"/></p>
		</xsl:if>

		<p class="label" style="float:left">Line:</p>
		<p class="description">Defined on line <xsl:value-of select="LineNumber"/></p>
	</span>
</xsl:template>

<xsl:template match="Param">
	<dt class="parameter"><i><xsl:value-of select="@Type"/></i><xsl:text> </xsl:text><b><xsl:value-of select="@Name"/></b></dt>
	<dd class="parameter"><p class="description"><xsl:value-of select="."/></p></dd>
</xsl:template>

</xsl:stylesheet>