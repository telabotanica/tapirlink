<definitions targetNamespace="http://rs.tdwg.org/tapir/lsid/Authority"
             xmlns="http://schemas.xmlsoap.org/wsdl/"
             xmlns:http="http://schemas.xmlsoap.org/wsdl/http/"
             xmlns:httpsns="http://www.omg.org/LSID/2003/DataServiceHTTPBindings">
	
  <import namespace="http://www.omg.org/LSID/2003/DataServiceHTTPBindings" location="LSIDDataServiceHTTPBindings.wsdl" />
	
  <!-- Example HTTP GET Services (urlEncoding) -->

  <service name="MyDataHTTPService">
    <port name="MyDataServiceHTTPPort" binding="httpsns:LSIDDataHTTPBinding">
      <http:address location="<?php print($LSIDDataAddress); ?>" /> 
    </port>
  </service>

  <service name="MyMetadataHTTPService">
    <port name="MyMetadataServiceHTTPPort" binding="httpsns:LSIDMetadataHTTPBinding">
      <http:address location="<?php print($LSIDMetadataAddress); ?>" /> 
    </port>
  </service>
	
</definitions>
