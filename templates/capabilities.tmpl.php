<capabilities>
	<operations>
		<ping/>
		<metadata/>
		<capabilities/>
		<inventory>
			<?php print( $r_settings->GetInventoryTemplatesXml() ); ?>
			<anyConcepts/>
		</inventory>
		<search>
			<?php print( $r_settings->GetSearchTemplatesXml() ); ?>
			<?php print( $r_settings->GetOutputModelsXml() ); ?>
		</search>
	</operations>
	<requests>
		<encoding>
			<kvp/>
			<xml/>
		</encoding>
		<globalParameters>
			<logOnly><?php print( $r_settings->GetLogOnly() ); ?></logOnly>
		</globalParameters>
		<filter>
			<encoding>
				<expression>
					<concept/>
					<literal/>
					<parameter/>
					<variable/>
					<arithmetic/>
				</expression>
				<booleanOperators>
					<logical>
						<not/>
						<and/>
						<or/>
					</logical>
					<comparative>
						<equals caseSensitive="<?php print( ($r_settings->GetCaseSensitiveInEquals()) ? 'true' : 'false' ); ?>"/>
						<greaterThan/>
						<greaterThanOrEquals/>
						<lessThan/>
						<lessThanOrEquals/>
						<in/>
						<isNull/>
						<like caseSensitive="<?php print( ($r_settings->GetCaseSensitiveInLike()) ? 'true' : 'false' ); ?>"/>
					</comparative>
				</booleanOperators>
			</encoding>
		</filter>
	</requests>
<?php print( $r_local_mapping->GetCapabilitiesXml() ); ?>
	<variables>
		<environment>
			<date/>
			<timestamp/>
			<dataSourceName/>
			<accessPoint/>
			<lastUpdate/>
			<dateCreated/>
			<dataSourceDescription/>
			<rights/>
			<metadataLanguage/>
			<dataSourceLanguage/>
			<technicalContactName/>
			<technicalContactEmail/>
			<contentContactName/>
			<contentContactEmail/>
		</environment>
	</variables>
	<settings>
		<maxElementRepetitions><?php print( $r_settings->GetMaxElementRepetitions() ); ?></maxElementRepetitions>
		<maxElementLevels><?php print( $r_settings->GetMaxElementLevels() ); ?></maxElementLevels>
	</settings>
</capabilities>