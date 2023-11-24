The PDFB Library by Chirag Mehta ( http://chir.ag/projects/pdfb ) combines the following three PHP libraries and makes it very easy for even novice PHP programmers to generate high-quality dynamic PDF files with barcodes (UPC, C39, C128, I25):

1. FPDF by Olivier Plathey - v1.53   : http://www.fpdf.org/
2. FPDI by Jan Slabon      - v1.1    : http://fpdi.setasign.de/
3. Barcode by Karim Mribti - v0.0.8a : http://www.mribti.com/barcode/

Each of these libraries have their own licensing and copyright/copyleft schemes. For help, documentation, and server requirements for each of these libraries, refer to the URLs above.

For help and documentation on how to use the Barcode functions of PDFB, view the source-code of demo.php file. Feel free to contact Chirag Mehta at http://chir.ag/about for further information.

The PDFB Library by Chirag Mehta is released under the Creative Commons License: http://creativecommons.org/licenses/by/2.5/

1. FPDF:

The contents of this library have been extracted into the pdfb/fpdf_fpdi folder WITHOUT ANY CHANGES. This includes all the library files, fonts, tutorials/examples, documentation, license information, readme files etc.

Unless FPDF drastically changes their file-folder layout, it can be assumed that future versions of FPDF can simply be extracted into the pdfb/fpdf_fpdi folder to upgrade the FPDF component of PDFB to the latest version without breaking your code.

All files within the FPDF library must be distributed along with the PDFB Library, however, it is recommended that the following be deleted in production environments as they do not contribute towards the PDFB Library:

-> pdfb/fpdf_fpdi/doc/
-> pdfb/fpdf_fpdi/font/makefont/
-> pdfb/fpdf_fpdi/tutorial/
-> pdfb/fpdf_fpdi/fpdf.css
-> pdfb/fpdf_fpdi/FAQ.htm
-> pdfb/fpdf_fpdi/histo.htm
-> pdfb/fpdf_fpdi/install.txt

2. FPDI:

The contents of this library have been extracted into the pdfb/fpdf_fpdi folder WITHOUT ANY CHANGES. This includes all the library files, decoders, templates, examples, documentation, license information, readme files etc.

Since FPDI requires (via fpdf_tpl.php -> require_once("fpdf.php"); ) that FPDF be in the same folder as itself, the two libraries have been extracted into the pdfb/fpdf_fpdi folder.

Unless FPDI drastically changes their file-folder layout, it can be assumed that future versions of FPDI can simply be extracted into the pdfb/fpdf_fpdi folder to upgrade the FPDI component of PDFB to the latest version without breaking your code.

It can further be assumed that you can upgrade both FPDF and FPDI independently as long as the two versions are said to work together.

All files within the FPDI library must be distributed along with the PDFB Library, however, it is recommended that the following be deleted in production environments as they do not contribute towards the PDFB Library:

-> pdfb/fpdf_fpdi/changelog.txt
-> pdfb/fpdf_fpdi/demo.php
-> pdfb/fpdf_fpdi/LICENSE
-> pdfb/fpdf_fpdi/NOTICE
-> pdfb/fpdf_fpdi/pdfdoc.pdf

3. Barcode:

To work with PDFB, considerable changes were made to the original Barcode Render Class for PHP using the GD graphics library created by Karim Mribti - Copyright (C) 2001. Many files were removed from the downloadable library ( http://www.mribti.com/barcode/barcode-0.0.8a.zip ) as it also contained pages and files that do not contribute towards PDFB library.

The contents of this library have been extracted into the pdfb/barcode folder WITH MANY CHANGES.

All debugging and tracing information from Barcode Library was removed for performance reasons. The following files were changed by Chirag Mehta for the PDFB Library:

-> pdfb/barcode/barcode.php      - Base Barcode Class
-> pdfb/barcode/c128a.php        - Code 128 A
-> pdfb/barcode/c128b.php        - Code 128 B
-> pdfb/barcode/c128c.php        - Code 128 C
-> pdfb/barcode/c39.php          - Code 3 of 9
-> pdfb/barcode/i25.php          - Interleaved 2 of 5

The following files were created by Chirag Mehta for the PDFB Library:

-> pdfb/barcode/upca.php         - UPC-A
-> pdfb/barcode/barcodeimage.php - Generate Barcode Function Module

It is recommended that the following files be deleted in production environments as they do not contribute towards the PDFB Library:

-> pdfb/barcode/lesser.txt