<?php

/*

+-----------------------------------------------------------------+
|     Created by Chirag Mehta - http://chir.ag/projects/pdfb      |
|-----------------------------------------------------------------|
|                      For PDFB Library                           |
+-----------------------------------------------------------------+

*/

  error_reporting (E_ALL);  // remove this from Production Environment
  require('pdfb/pdfb.php'); // Must include this

  // Recommended way to use PDFB Library
  // - create your own PDF class
  // - instantiate it wherever necessary
  // - you can create multiple classes extending from PDFB
  //   for each different report

  class PDF extends PDFB
  {
    function Header()
    {
      // Add your code here to generate Headers on every page
    }

    function Footer()
    {
      // Replace this with your code to generate Footers on every page

      // PDFB Library made this dynamic PDF :)
      // Remember to use '$this->' instead of '$pdf->'
      $this->Text(402, 735, "Dynamic PDF: PDFB Library!");
    }

    // You can create your own methods to make printing easier
    // Here is a method to print information about a product on the packing slip
    function printProduct($p)
    {
      // Product Name
      $this->Write(0, $p["product"]);

      // Quantity
      $this->SetX(300);
      $this->Write(0, $p["qty"]);

      // UPC-A Barcode
      $this->BarCode($p["upc"], "UPCA", 400, $this->GetY()-10, 288, 108, 0.5, 0.5, 2, 5, "", "PNG");

      // Draw a separator line
      $lineY = $this->GetY() + 50;
      $this->Line(80, $lineY, 540, $lineY);

      // Move cursor down
      $this->SetXY(80, $lineY + 24);
    }

  }

  // Create a PDF object and set up the properties
  $pdf = new PDF("p", "pt", "letter");
  $pdf->SetAuthor("PDFB Library");
  $pdf->SetTitle("Packing Slip");

  // Add custom font
  // Read: http://www.fpdf.org/en/tutorial/tuto7.htm for more info
  // $pdf->AddFont("Trebuchet");
  // $pdf->SetFont("Trebuchet", "", 10);
  $pdf->SetFont("Times", "", 10);

  // Set line drawing defaults
  $pdf->SetDrawColor(224);
  $pdf->SetLineWidth(1);

  // Load the base PDF into template
  $pdf->setSourceFile("demo.pdf");
  $tplidx = $pdf->ImportPage(1);

  // Add new page & use the base PDF as template
  $pdf->AddPage();
  $pdf->useTemplate($tplidx);

  // Probably load Packing Slip information from a database.
  $pkgslip = rand(1000,9999);

  // Packing Slip # and date
  $pdf->Text(140, 163, date("M. d, Y, g:i a"));
  $pdf->Text(500, 162, $pkgslip);

  // Print Ship To Information
  $pdf->SetXY(140, 184);
  $pdf->MultiCell(0, 14, "ACME Inc.\n123rd Street\nCool City, Funworld\nAtlantis - 1234");

  // See pdfb/pdfb.php for parameters on BarCode()
  $pdf->BarCode("PKG" . $pkgslip, "C128B", 298, 180, 288, 88, 1, 1, 2, 5, "", "PNG");

  // Load product information
  $products[] = array("product" => "Whisker City - Catnip Mist", "qty" => 12, "upc" => "737257358829");
  $products[] = array("product" => "Pounce - Tuna Flavor Moist", "qty" => 18, "upc" => "079100001606");

  $pdf->SetXY(80, 330);

  // Print product information
  if($products)
    foreach($products as $product)
      $pdf->printProduct($product);

  // Add signature & timestamp for the 'Approved' section
  $pdf->Image("demo.jpg", 430, 640, 137, 41);
  $pdf->Text(440, 702, date("M. d, Y, g:i a"));

  $pdf->Output();
  $pdf->closeParsers();

?>