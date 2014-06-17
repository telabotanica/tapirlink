@echo If something goes wrong here, try running "UpdateDocumentation.bat debug".
@echo Generating documentation....
GeneratePhpDocumentation.pl input=..\XPath.class.php output=Php.XPathDocumentation.xml %1
@echo Done.