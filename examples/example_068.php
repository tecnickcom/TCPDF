<?php

/**
 * Example 068 for TCPDF library
 *
 * @description Creates an example PDF/A-3b document using TCPDF
 * @author Nicola Asuni - Tecnick.com LTD <info@tecnick.com>
 * @license LGPL-3.0
 */

/**
 * Creates an example PDF/A-3b with embedded file (Factur-X 1.07 - ZUGFeRD 2.3)
 *
 * @abstract TCPDF - Example: PDF/A-3b with embedded file (Factur-X 1.07 - ZUGFeRD 2.3)
 * @author Nicola Asuni
 * @since 2021-03-26
 * @group A-3b
 * @group pdf
 */

// Include the main TCPDF library (search for installation path).
require_once('tcpdf_include.php');

// create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false, 3);

// set document information
$pdf->setCreator(PDF_CREATOR);
$pdf->setAuthor('Nicola Asuni');
$pdf->setTitle('TCPDF Example 068');
$pdf->setSubject('TCPDF Tutorial');
$pdf->setKeywords('TCPDF, PDF, example, test, guide');

// set default header data
$pdf->setHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE . ' 068', PDF_HEADER_STRING);

// set header and footer fonts
$pdf->setHeaderFont(array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// set default monospaced font
$pdf->setDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// set margins
$pdf->setMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->setHeaderMargin(PDF_MARGIN_HEADER);
$pdf->setFooterMargin(PDF_MARGIN_FOOTER);

// set auto page breaks
$pdf->setAutoPageBreak(true, PDF_MARGIN_BOTTOM);

// set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// set some language-dependent strings (optional)
if (@file_exists(__DIR__ . '/lang/eng.php')) {
    require_once __DIR__ . '/lang/eng.php';

    $pdf->setLanguageArray($l);
}

// ---------------------------------------------------------

// set default font subsetting mode
$pdf->setFontSubsetting(true);

// Set font
$pdf->setFont('helvetica', '', 14, '', true);

// Add a page
// This method has several options, check the source code documentation for more information.
$pdf->AddPage();

// Set some content to print
$html = <<<HTML
<h1>Example of <a href="http://www.tcpdf.org" style="text-decoration:none;background-color:#CC0000;color:black;">&nbsp;<span style="color:black;">TC</span><span style="color:white;">PDF</span>&nbsp;</a> document in <span style="background-color:#99ccff;color:black;"> PDF/A-3b </span> mode.</h1>
<i>This document conforms to the standard <b>Factur-X 1.07 / ZUGFeRD 2.3</b>.</i>
<p>Please check the source code documentation and other examples for further information (<a href="http://www.tcpdf.org">http://www.tcpdf.org</a>).</p>
HTML;

$invoiceXml = <<<XML
<?xml version='1.0' encoding='UTF-8' ?>
<rsm:CrossIndustryInvoice xmlns:rsm="urn:un:unece:uncefact:data:standard:CrossIndustryInvoice:100" xmlns:qdt="urn:un:unece:uncefact:data:standard:QualifiedDataType:100" xmlns:ram="urn:un:unece:uncefact:data:standard:ReusableAggregateBusinessInformationEntity:100" xmlns:xs="http://www.w3.org/2001/XMLSchema" xmlns:udt="urn:un:unece:uncefact:data:standard:UnqualifiedDataType:100">
    <rsm:ExchangedDocumentContext>
        <ram:BusinessProcessSpecifiedDocumentContextParameter>
            <ram:ID>Baurechnung</ram:ID>
        </ram:BusinessProcessSpecifiedDocumentContextParameter>
        <ram:GuidelineSpecifiedDocumentContextParameter>
            <ram:ID>urn:cen.eu:en16931:2017</ram:ID>
        </ram:GuidelineSpecifiedDocumentContextParameter>
    </rsm:ExchangedDocumentContext>
    <rsm:ExchangedDocument>
        <ram:ID>181301674</ram:ID>
        <ram:TypeCode>204</ram:TypeCode>
        <ram:IssueDateTime>
            <udt:DateTimeString format="102">20241115</udt:DateTimeString>
        </ram:IssueDateTime>
        <ram:IncludedNote>
            <ram:Content>Rapport-Nr.: 42389 vom 01.11.2024

Im 2. OG BT1 Besprechungsraum eine Beamerhalterung an die Decke montiert. Dafür eine Deckenplatte ausgesägt. Beamerhalterung zur Montage auseinander gebaut. Ein Stromkabel für den Beamer, ein HDMI Kabel und ein VGA Kabel durch die Halterung gezogen. Beamerhalterung wieder zusammengebaut und Beamer montiert. Beamer verkabelt und ausgerichtet. Decke geschlossen.</ram:Content>
        </ram:IncludedNote>
    </rsm:ExchangedDocument>
    <rsm:SupplyChainTradeTransaction>
        <ram:IncludedSupplyChainTradeLineItem>
            <ram:AssociatedDocumentLineDocument>
                <ram:LineID>01</ram:LineID>
                <ram:IncludedNote>
                    <ram:Content>01 Beamermontage
Für die doppelte Verlegung, falls erforderlich.</ram:Content>
                </ram:IncludedNote>
            </ram:AssociatedDocumentLineDocument>
            <ram:SpecifiedTradeProduct>
                <ram:Name>TGA Obermonteur/Monteur</ram:Name>
            </ram:SpecifiedTradeProduct>
            <ram:SpecifiedLineTradeAgreement>
                <ram:GrossPriceProductTradePrice>
                    <ram:ChargeAmount>43.2</ram:ChargeAmount>
                </ram:GrossPriceProductTradePrice>
                <ram:NetPriceProductTradePrice>
                    <ram:ChargeAmount>43.2</ram:ChargeAmount>
                </ram:NetPriceProductTradePrice>
            </ram:SpecifiedLineTradeAgreement>
            <ram:SpecifiedLineTradeDelivery>
                <ram:BilledQuantity unitCode="HUR">3</ram:BilledQuantity>
            </ram:SpecifiedLineTradeDelivery>
            <ram:SpecifiedLineTradeSettlement>
                <ram:ApplicableTradeTax>
                    <ram:TypeCode>VAT</ram:TypeCode>
                    <ram:CategoryCode>S</ram:CategoryCode>
                    <ram:RateApplicablePercent>19</ram:RateApplicablePercent>
                </ram:ApplicableTradeTax>
                <ram:SpecifiedTradeSettlementLineMonetarySummation>
                    <ram:LineTotalAmount>129.6</ram:LineTotalAmount>
                </ram:SpecifiedTradeSettlementLineMonetarySummation>
            </ram:SpecifiedLineTradeSettlement>
        </ram:IncludedSupplyChainTradeLineItem>
        <ram:IncludedSupplyChainTradeLineItem>
            <ram:AssociatedDocumentLineDocument>
                <ram:LineID>02</ram:LineID>
                <ram:IncludedNote>
                    <ram:Content>02 Außerhalb Angebot</ram:Content>
                </ram:IncludedNote>
            </ram:AssociatedDocumentLineDocument>
            <ram:SpecifiedTradeProduct>
                <ram:Name>Beamer-Deckenhalterung</ram:Name>
            </ram:SpecifiedTradeProduct>
            <ram:SpecifiedLineTradeAgreement>
                <ram:GrossPriceProductTradePrice>
                    <ram:ChargeAmount>122.5</ram:ChargeAmount>
                </ram:GrossPriceProductTradePrice>
                <ram:NetPriceProductTradePrice>
                    <ram:ChargeAmount>122.5</ram:ChargeAmount>
                </ram:NetPriceProductTradePrice>
            </ram:SpecifiedLineTradeAgreement>
            <ram:SpecifiedLineTradeDelivery>
                <ram:BilledQuantity unitCode="H87">1</ram:BilledQuantity>
            </ram:SpecifiedLineTradeDelivery>
            <ram:SpecifiedLineTradeSettlement>
                <ram:ApplicableTradeTax>
                    <ram:TypeCode>VAT</ram:TypeCode>
                    <ram:CategoryCode>S</ram:CategoryCode>
                    <ram:RateApplicablePercent>19</ram:RateApplicablePercent>
                </ram:ApplicableTradeTax>
                <ram:SpecifiedTradeSettlementLineMonetarySummation>
                    <ram:LineTotalAmount>122.5</ram:LineTotalAmount>
                </ram:SpecifiedTradeSettlementLineMonetarySummation>
            </ram:SpecifiedLineTradeSettlement>
        </ram:IncludedSupplyChainTradeLineItem>
        <ram:ApplicableHeaderTradeAgreement>  
          <ram:BuyerReference>Liselotte Müller-Lüdenscheidt</ram:BuyerReference>
            <ram:SellerTradeParty>
                <ram:ID>549910</ram:ID>
                <ram:Name>ELEKTRON Industrieservice GmbH</ram:Name>
                <ram:Description>Geschäftsführer Egon Schrempp Amtsgericht Stuttgart HRB 1234</ram:Description>
                <ram:PostalTradeAddress>
                    <ram:PostcodeCode>74465</ram:PostcodeCode>
                    <ram:LineOne>Erfurter Strasse 13</ram:LineOne>
                    <ram:CityName>Demoort</ram:CityName>
                    <ram:CountryID>DE</ram:CountryID>
                </ram:PostalTradeAddress>
                <ram:SpecifiedTaxRegistration>
                    <ram:ID schemeID="VA">DE136695976</ram:ID>
                </ram:SpecifiedTaxRegistration>
            </ram:SellerTradeParty>
            <ram:BuyerTradeParty>
                <ram:ID>16259</ram:ID>
                <ram:Name>ConsultingService GmbH</ram:Name>
                <ram:PostalTradeAddress>
                    <ram:PostcodeCode>76138</ram:PostcodeCode>
                    <ram:LineOne>Musterstr. 18</ram:LineOne>
                    <ram:CityName>Karlsruhe</ram:CityName>
                    <ram:CountryID>DE</ram:CountryID>
                </ram:PostalTradeAddress>
            </ram:BuyerTradeParty>
            <ram:SellerOrderReferencedDocument>
                <ram:IssuerAssignedID>per Mail vom 01.09.2024</ram:IssuerAssignedID>
            </ram:SellerOrderReferencedDocument>
            <ram:AdditionalReferencedDocument>
                <ram:IssuerAssignedID>13130162</ram:IssuerAssignedID>
                <ram:URIID>#ef=Aufmass.png</ram:URIID>
                <ram:TypeCode>916</ram:TypeCode>
            </ram:AdditionalReferencedDocument>
             <ram:AdditionalReferencedDocument>
                <ram:IssuerAssignedID>42389</ram:IssuerAssignedID>
                <ram:URIID>#ef=ElektronRapport_neu-red.pdf</ram:URIID>
                <ram:TypeCode>916</ram:TypeCode>
            </ram:AdditionalReferencedDocument>
            <ram:SpecifiedProcuringProject>
                <ram:ID>13130162</ram:ID>
                <ram:Name>Projekt</ram:Name>
            </ram:SpecifiedProcuringProject>
        </ram:ApplicableHeaderTradeAgreement>
        <ram:ApplicableHeaderTradeDelivery>
            <ram:ActualDeliverySupplyChainEvent>
                <ram:OccurrenceDateTime>
                    <udt:DateTimeString format="102">20241101</udt:DateTimeString>
                </ram:OccurrenceDateTime>
            </ram:ActualDeliverySupplyChainEvent>
        </ram:ApplicableHeaderTradeDelivery>
        <ram:ApplicableHeaderTradeSettlement>
            <ram:PaymentReference>Rechnung 181301674</ram:PaymentReference>
            <ram:InvoiceCurrencyCode>EUR</ram:InvoiceCurrencyCode>
            <ram:SpecifiedTradeSettlementPaymentMeans>
                <ram:TypeCode>58</ram:TypeCode>
                <ram:PayeePartyCreditorFinancialAccount>
                    <ram:IBANID>DE91100000000123456789</ram:IBANID>
                </ram:PayeePartyCreditorFinancialAccount>
            </ram:SpecifiedTradeSettlementPaymentMeans>
            <ram:ApplicableTradeTax>
                <ram:CalculatedAmount>47.9</ram:CalculatedAmount>
                <ram:TypeCode>VAT</ram:TypeCode>
                <ram:BasisAmount>252.1</ram:BasisAmount>
                <ram:CategoryCode>S</ram:CategoryCode>
                <ram:RateApplicablePercent>19</ram:RateApplicablePercent>
            </ram:ApplicableTradeTax>
            <ram:SpecifiedTradePaymentTerms>
                <ram:Description>Zahlbar sofort rein netto</ram:Description>
            </ram:SpecifiedTradePaymentTerms>
            <ram:SpecifiedTradeSettlementHeaderMonetarySummation>
                <ram:LineTotalAmount>252.1</ram:LineTotalAmount>
                <ram:ChargeTotalAmount>0</ram:ChargeTotalAmount>
                <ram:AllowanceTotalAmount>0</ram:AllowanceTotalAmount>
                <ram:TaxBasisTotalAmount>252.1</ram:TaxBasisTotalAmount>
                <ram:TaxTotalAmount currencyID="EUR">47.9</ram:TaxTotalAmount>
                <ram:GrandTotalAmount>300</ram:GrandTotalAmount>
                <ram:TotalPrepaidAmount>0</ram:TotalPrepaidAmount>
                <ram:DuePayableAmount>300</ram:DuePayableAmount>
            </ram:SpecifiedTradeSettlementHeaderMonetarySummation>
            <ram:ReceivableSpecifiedTradeAccountingAccount>
                <ram:ID>420</ram:ID>
            </ram:ReceivableSpecifiedTradeAccountingAccount>
        </ram:ApplicableHeaderTradeSettlement>
    </rsm:SupplyChainTradeTransaction>
</rsm:CrossIndustryInvoice>
XML;

// Print text using writeHTMLCell()
$pdf->writeHTMLCell(0, 0, '', '', $html, 0, 1, 0, true, '', true);

$pdf->EmbedFileFromString('factur-x.xml', $invoiceXml);

$pdf->setExtraXMPRDF(
    '<rdf:Description xmlns:fx="urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#" rdf:about="">
  <fx:DocumentType>INVOICE</fx:DocumentType>
  <fx:DocumentFileName>factur-x.xml</fx:DocumentFileName>
  <fx:Version>1.0</fx:Version>
  <fx:ConformanceLevel>EN 16931</fx:ConformanceLevel>
</rdf:Description>');

$pdf->setExtraXMPPdfaextension(
    '<rdf:li rdf:parseType="Resource">
    <pdfaSchema:schema>Factur-X PDFA Extension Schema</pdfaSchema:schema>
    <pdfaSchema:namespaceURI>urn:factur-x:pdfa:CrossIndustryDocument:invoice:1p0#</pdfaSchema:namespaceURI>
    <pdfaSchema:prefix>fx</pdfaSchema:prefix>
    <pdfaSchema:property>
      <rdf:Seq>
        <rdf:li rdf:parseType="Resource">
          <pdfaProperty:name>DocumentFileName</pdfaProperty:name>
          <pdfaProperty:valueType>Text</pdfaProperty:valueType>
          <pdfaProperty:category>external</pdfaProperty:category>
          <pdfaProperty:description>The name of the embedded XML document</pdfaProperty:description>
        </rdf:li>
        <rdf:li rdf:parseType="Resource">
          <pdfaProperty:name>DocumentType</pdfaProperty:name>
          <pdfaProperty:valueType>Text</pdfaProperty:valueType>
          <pdfaProperty:category>external</pdfaProperty:category>
          <pdfaProperty:description>The type of the hybrid document in capital letters, e.g. INVOICE or ORDER</pdfaProperty:description>
        </rdf:li>
        <rdf:li rdf:parseType="Resource">
          <pdfaProperty:name>Version</pdfaProperty:name>
          <pdfaProperty:valueType>Text</pdfaProperty:valueType>
          <pdfaProperty:category>external</pdfaProperty:category>
          <pdfaProperty:description>The actual version of the standard applying to the embedded XML document</pdfaProperty:description>
        </rdf:li>
        <rdf:li rdf:parseType="Resource">
          <pdfaProperty:name>ConformanceLevel</pdfaProperty:name>
          <pdfaProperty:valueType>Text</pdfaProperty:valueType>
          <pdfaProperty:category>external</pdfaProperty:category>
          <pdfaProperty:description>The conformance level of the embedded XML document</pdfaProperty:description>
        </rdf:li>
      </rdf:Seq>
    </pdfaSchema:property>
</rdf:li>'
);


// ---------------------------------------------------------

// Close and output PDF document
// This method has several options, check the source code documentation for more information.
$pdf->Output('example_068.pdf', 'I');
