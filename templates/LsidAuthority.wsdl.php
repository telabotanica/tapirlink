<wsdl:definitions targetNamespace="http://rs.tdwg.org/tapir/lsid/Authority"
                  xmlns:wsdl="http://schemas.xmlsoap.org/wsdl/"	
                  xmlns:httpsns="http://www.omg.org/LSID/2003/AuthorityServiceHTTPBindings">
	
  <wsdl:import namespace="http://www.omg.org/LSID/2003/AuthorityServiceHTTPBindings" location="LSIDAuthorityServiceHTTPBindings.wsdl" />

  <wsdl:service name="MyAuthorityHTTPService">
    <wsdl:port name="MyAuthorityHTTPPort" binding="httpsns:LSIDAuthorityHTTPBinding">
      <httpsns:address location="<?php print($LSIDAuthorityAddress); ?>" /> 
    </wsdl:port>
  </wsdl:service>

</wsdl:definitions>
