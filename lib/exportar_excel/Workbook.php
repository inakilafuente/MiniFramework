<?php
/*
*  Module written/ported by Xavier Noguer <xnoguer@rezebra.com>
*
*  The majority of this is _NOT_ my code.  I simply ported it from the
*  PERL Spreadsheet::WriteExcel module.
*
*  The author of the Spreadsheet::WriteExcel module is John McNamara 
*  <jmcnamara@cpan.org>
*
*  I _DO_ maintain this code, and John McNamara has nothing to do with the
*  porting of this code to PHP.  Any questions directly related to this
*  class library should be directed to me.
*
*  License Information:
*
*    Spreadsheet::WriteExcel:  A library for generating Excel Spreadsheets
*    Copyright (C) 2002 Xavier Noguer xnoguer@rezebra.com
*
*    This library is free software; you can redistribute it and/or
*    modify it under the terms of the GNU Lesser General Public
*    License as published by the Free Software Foundation; either
*    version 2.1 of the License, or (at your option) any later version.
*
*    This library is distributed in the hope that it will be useful,
*    but WITHOUT ANY WARRANTY; without even the implied warranty of
*    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the GNU
*    Lesser General Public License for more details.
*
*    You should have received a copy of the GNU Lesser General Public
*    License along with this library; if not, write to the Free Software
*    Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

require_once('Format.php');

/**
* Class for generating Excel Spreadsheets
*
* @autor Xavier Noguer <xnoguer@rezebra.com>
*/

class Workbook extends BIFFwriter
  {
  function __construct($filename)
    {
        parent::__construct(); // It needs to call its parent's constructor explicitly
    //my $parser      = Spreadsheet::WriteExcel::Formula->new();

    $this->_filename          = $filename;
    //$this->parser            = $parser;
    $this->_1904             = 0;
    $this->activesheet       = 0;
    $this->firstsheet        = 0;
    $this->selected          = 0;
    $this->xf_index          = 16; # 15 style XF's and 1 cell XF.
    $this->fileclosed        = 0;
    $this->biffsize          = 0;
    $this->sheetname         = "Sheet";
    $this->tmp_format        = new Format();// $tmp_format;
    $this->worksheets        = array();
    $this->sheetnames        = array();
    $this->formats           = array();
    $this->palette           = array();

    // Add the default format for hyperlinks
    $this->url_format =& $this->addformat(array('color' => 'blue', 'underline' => 1));

    // Check for a filename
    if ($this->_filename == '') {
        die('Filename required by Spreadsheet::WriteExcel->new()');
        }


    # Try to open the named file and see if it throws any errors.
    # If the filename is a reference it is assumed that it is a valid
    # filehandle and ignore
    #
    /*if (not ref $this->{_filename}) {
        open  TMP, '>'. $this->{_filename} or do {
            carp "Can't open " . $this->{_filename} . ". " .
                 "It may be in use or protected";
            return undef;
        };
        close TMP;
    }*/
    # Warn if tmpfiles can't be used.
    //$this->tmpfile_warning();
    $this->_set_palette_xl97();
    }

/**
* Calls finalization methods and explicitly close the OLEwriter file
* handle.
*/
  function close()
    {
    //return if $this->{_fileclosed}; # Prevent close() from being called twice.
    $this->store_workbook();
    //$this->{_fileclosed} = 1;
    }


###############################################################################
#
# sheets()
#
# An accessor for the _worksheets[] array
#
# Returns: a list of the worksheet objects in a workbook
#
/*sub sheets {

    my $this = shift;

    return @{$this->{_worksheets}};
}*/

/**
* An accessor for the _worksheets[] array.
* This method is now deprecated. Use the sheets() method instead.
*
* Returns: an array reference
*/
  function worksheets()
    {
    return($this->worksheets);
    }

/**
* Add a new worksheet to the Excel workbook.
* TODO: Add accessor for $this->{_sheetname} for international Excel versions.
*
* @param string $name the optional name of the worksheet
* @return reference to a worksheet object
*/
  function &add_worksheet($name = '')
    {
    $index     = count($this->worksheets);
    $sheetname = $this->sheetname;

    if($name == '') {
        $name = $sheetname.($index+1); 
        }

    // Check that the worksheet name doesn't already exist: a fatal Excel error.
    /*foreach my $tmp (@{$this->{_worksheets}}) {
        croak "Worksheet '$name' already exists" if $name eq $tmp->get_name();
    }*/

    $worksheet = new Worksheet($name,$index,$this->activesheet,
                               $this->firstsheet,$this->url_format);
			       //$this->parser);
    $this->worksheets[$index] = &$worksheet;      // Store ref for iterator
    $this->sheetnames[$index] = $name;            // Store EXTERNSHEET names
    //$this->parser->set_ext_sheet($name,$index); // Store names in Formula.php
    return($worksheet);
    }

/**
* DEPRECATED!! Use add_worksheet instead
*/
  function &addworksheet($name = '')
    {
    return($this->add_worksheet($name));
    }

/**
* Add a new format to the Excel workbook. This adds an XF record and
* a FONT record. Also, pass any properties to the Format constructor.
*
* @param array $properties array with properties for initializing the format (see Format.php)
*/
  function &add_format($properties = array())
    {
    $format = new Format($this->xf_index,$properties);
    $this->xf_index += 1;
    $this->formats[] = &$format;
    return($format);
    }

/**
* DEPRECATED!! Use add_format instead
*/
function &addformat($properties = array())
  {
  return($this->add_format($properties));
  }

###############################################################################
#
# set_1904()
#
# Set the date system: 0 = 1900 (the default), 1 = 1904
#
/*sub set_1904 {

    my $this      = shift;

    if (defined($_[0])) {
        $this->{_1904} = $_[0];
    }
    else {
        $this->{_1904} = 1;
    }
}


###############################################################################
#
# get_1904()
#
# Return the date system: 0 = 1900, 1 = 1904
#
sub get_1904 {

    my $this = shift;

    return $this->{_1904};
}*/

/**
* Change the RGB components of the elements in the colour palette.
*
* @param $index colour index
* @param $red   red RGB value [0-255]
* @param $green green RGB value [0-255]
* @param $blue  blue RGB value [0-255]
*/
  function set_custom_color($index,$red,$green,$blue)
    {
    // Match a HTML #xxyyzz style parameter
    /*if (defined $_[1] and $_[1] =~ /^#(\w\w)(\w\w)(\w\w)/ ) {
        @_ = ($_[0], hex $1, hex $2, hex $3);
        }*/

    // Check that the colour index is the right range
    if ($index < 8 or $index > 64) {
        die("Color index $index outside range: 8 <= index <= 64");
        }

    // Check that the colour components are in the right range
    if ( ($red   < 0 or $red   > 255) ||
         ($green < 0 or $green > 255) ||
         ($blue  < 0 or $blue  > 255) )  
        {
        die("Color component outside range: 0 <= color <= 255");
        }

    $index -= 8; // Adjust colour index (wingless dragonfly)
    
    // Set the RGB value
    $this->palette[$index] = array($red, $green, $blue, 0);
    return($index + 8);
    }

/**
* Sets the colour palette to the Excel 97+ default.
*/
  function _set_palette_xl97()
    {
    $this->palette = array(
                       array(0x00, 0x00, 0x00, 0x00),   // 8
                       array(0xff, 0xff, 0xff, 0x00),   // 9
                       array(0xff, 0x00, 0x00, 0x00),   // 10
                       array(0x00, 0xff, 0x00, 0x00),   // 11
                       array(0x00, 0x00, 0xff, 0x00),   // 12
                       array(0xff, 0xff, 0x00, 0x00),   // 13
                       array(0xff, 0x00, 0xff, 0x00),   // 14
                       array(0x00, 0xff, 0xff, 0x00),   // 15
                       array(0x80, 0x00, 0x00, 0x00),   // 16
                       array(0x00, 0x80, 0x00, 0x00),   // 17
                       array(0x00, 0x00, 0x80, 0x00),   // 18
                       array(0x80, 0x80, 0x00, 0x00),   // 19
                       array(0x80, 0x00, 0x80, 0x00),   // 20
                       array(0x00, 0x80, 0x80, 0x00),   // 21
                       array(0xc0, 0xc0, 0xc0, 0x00),   // 22
                       array(0x80, 0x80, 0x80, 0x00),   // 23
                       array(0x99, 0x99, 0xff, 0x00),   // 24
                       array(0x99, 0x33, 0x66, 0x00),   // 25
                       array(0xff, 0xff, 0xcc, 0x00),   // 26
                       array(0xcc, 0xff, 0xff, 0x00),   // 27
                       array(0x66, 0x00, 0x66, 0x00),   // 28
                       array(0xff, 0x80, 0x80, 0x00),   // 29
                       array(0x00, 0x66, 0xcc, 0x00),   // 30
                       array(0xcc, 0xcc, 0xff, 0x00),   // 31
                       array(0x00, 0x00, 0x80, 0x00),   // 32
                       array(0xff, 0x00, 0xff, 0x00),   // 33
                       array(0xff, 0xff, 0x00, 0x00),   // 34
                       array(0x00, 0xff, 0xff, 0x00),   // 35
                       array(0x80, 0x00, 0x80, 0x00),   // 36
                       array(0x80, 0x00, 0x00, 0x00),   // 37
                       array(0x00, 0x80, 0x80, 0x00),   // 38
                       array(0x00, 0x00, 0xff, 0x00),   // 39
                       array(0x00, 0xcc, 0xff, 0x00),   // 40
                       array(0xcc, 0xff, 0xff, 0x00),   // 41
                       array(0xcc, 0xff, 0xcc, 0x00),   // 42
                       array(0xff, 0xff, 0x99, 0x00),   // 43
                       array(0x99, 0xcc, 0xff, 0x00),   // 44
                       array(0xff, 0x99, 0xcc, 0x00),   // 45
                       array(0xcc, 0x99, 0xff, 0x00),   // 46
                       array(0xff, 0xcc, 0x99, 0x00),   // 47
                       array(0x33, 0x66, 0xff, 0x00),   // 48
                       array(0x33, 0xcc, 0xcc, 0x00),   // 49
                       array(0x99, 0xcc, 0x00, 0x00),   // 50
                       array(0xff, 0xcc, 0x00, 0x00),   // 51
                       array(0xff, 0x99, 0x00, 0x00),   // 52
                       array(0xff, 0x66, 0x00, 0x00),   // 53
                       array(0x66, 0x66, 0x99, 0x00),   // 54
                       array(0x96, 0x96, 0x96, 0x00),   // 55
                       array(0x00, 0x33, 0x66, 0x00),   // 56
                       array(0x33, 0x99, 0x66, 0x00),   // 57
                       array(0x00, 0x33, 0x00, 0x00),   // 58
                       array(0x33, 0x33, 0x00, 0x00),   // 59
                       array(0x99, 0x33, 0x00, 0x00),   // 60
                       array(0x99, 0x33, 0x66, 0x00),   // 61
                       array(0x33, 0x33, 0x99, 0x00),   // 62
                       array(0x33, 0x33, 0x33, 0x00),   // 63
                     );
    return(0);
    }


###############################################################################
#
# set_palette_xl5()
#
# Sets the colour palette to the Excel 5 default.
#
/*sub set_palette_xl5 {

    my $this = shift;

    $this->{_palette} = [
                            [0x00, 0x00, 0x00, 0x00],   # 8
                            [0xff, 0xff, 0xff, 0x00],   # 9
                            [0xff, 0x00, 0x00, 0x00],   # 10
                            [0x00, 0xff, 0x00, 0x00],   # 11
                            [0x00, 0x00, 0xff, 0x00],   # 12
                            [0xff, 0xff, 0x00, 0x00],   # 13
                            [0xff, 0x00, 0xff, 0x00],   # 14
                            [0x00, 0xff, 0xff, 0x00],   # 15
                            [0x80, 0x00, 0x00, 0x00],   # 16
                            [0x00, 0x80, 0x00, 0x00],   # 17
                            [0x00, 0x00, 0x80, 0x00],   # 18
                            [0x80, 0x80, 0x00, 0x00],   # 19
                            [0x80, 0x00, 0x80, 0x00],   # 20
                            [0x00, 0x80, 0x80, 0x00],   # 21
                            [0xc0, 0xc0, 0xc0, 0x00],   # 22
                            [0x80, 0x80, 0x80, 0x00],   # 23
                            [0x80, 0x80, 0xff, 0x00],   # 24
                            [0x80, 0x20, 0x60, 0x00],   # 25
                            [0xff, 0xff, 0xc0, 0x00],   # 26
                            [0xa0, 0xe0, 0xe0, 0x00],   # 27
                            [0x60, 0x00, 0x80, 0x00],   # 28
                            [0xff, 0x80, 0x80, 0x00],   # 29
                            [0x00, 0x80, 0xc0, 0x00],   # 30
                            [0xc0, 0xc0, 0xff, 0x00],   # 31
                            [0x00, 0x00, 0x80, 0x00],   # 32
                            [0xff, 0x00, 0xff, 0x00],   # 33
                            [0xff, 0xff, 0x00, 0x00],   # 34
                            [0x00, 0xff, 0xff, 0x00],   # 35
                            [0x80, 0x00, 0x80, 0x00],   # 36
                            [0x80, 0x00, 0x00, 0x00],   # 37
                            [0x00, 0x80, 0x80, 0x00],   # 38
                            [0x00, 0x00, 0xff, 0x00],   # 39
                            [0x00, 0xcf, 0xff, 0x00],   # 40
                            [0x69, 0xff, 0xff, 0x00],   # 41
                            [0xe0, 0xff, 0xe0, 0x00],   # 42
                            [0xff, 0xff, 0x80, 0x00],   # 43
                            [0xa6, 0xca, 0xf0, 0x00],   # 44
                            [0xdd, 0x9c, 0xb3, 0x00],   # 45
                            [0xb3, 0x8f, 0xee, 0x00],   # 46
                            [0xe3, 0xe3, 0xe3, 0x00],   # 47
                            [0x2a, 0x6f, 0xf9, 0x00],   # 48
                            [0x3f, 0xb8, 0xcd, 0x00],   # 49
                            [0x48, 0x84, 0x36, 0x00],   # 50
                            [0x95, 0x8c, 0x41, 0x00],   # 51
                            [0x8e, 0x5e, 0x42, 0x00],   # 52
                            [0xa0, 0x62, 0x7a, 0x00],   # 53
                            [0x62, 0x4f, 0xac, 0x00],   # 54
                            [0x96, 0x96, 0x96, 0x00],   # 55
                            [0x1d, 0x2f, 0xbe, 0x00],   # 56
                            [0x28, 0x66, 0x76, 0x00],   # 57
                            [0x00, 0x45, 0x00, 0x00],   # 58
                            [0x45, 0x3e, 0x01, 0x00],   # 59
                            [0x6a, 0x28, 0x13, 0x00],   # 60
                            [0x85, 0x39, 0x6a, 0x00],   # 61
                            [0x4a, 0x32, 0x85, 0x00],   # 62
                            [0x42, 0x42, 0x42, 0x00],   # 63
                        ];

    return 0;
}*/


###############################################################################
#
# _tmpfile_warning()
#
# Check that tmp files can be created for use in Worksheet.pm. A CGI, mod_perl
# or IIS might not have permission to create tmp files. The test is here rather
# than in Worksheet.pm so that only one warning is given.
#
/*sub _tmpfile_warning {

    my $fh = IO::File->new_tmpfile();

    if ((not defined $fh) && ($^W)) {
        carp("Unable to create tmp files via IO::File->new_tmpfile(). " .
             "Storing data in memory")
    }
}*/

/**
* Assemble worksheets into a workbook and send the BIFF data to an OLE
* storage.
*/
  function store_workbook()
    {
    // Ensure that at least one worksheet has been selected.
    if ($this->activesheet == 0)
        {
        $this->worksheets[0]->selected = 1;
        }

    // Calculate the number of selected worksheet tabs and call the finalization
    // methods for each worksheet
    for($i=0; $i < count($this->worksheets); $i++)
        {
	if($this->worksheets[$i]->selected)
          $this->selected++;
        $this->worksheets[$i]->close($this->sheetnames);
        }

    // Add Workbook globals
    $this->_store_bof(0x0005);
    $this->_store_externs();    // For print area and repeat rows
    $this->_store_names();      // For print area and repeat rows
    $this->_store_window1();
    $this->_store_1904();
    $this->_store_all_fonts();
    $this->_store_all_num_formats();
    $this->store_all_xfs();
    $this->store_all_styles();
    $this->store_palette();
    $this->calc_sheet_offsets();

    // Add BOUNDSHEET records
    for($i=0; $i < count($this->worksheets); $i++) {
        $this->store_boundsheet($this->worksheets[$i]->name,$this->worksheets[$i]->offset);
        }

    // End Workbook globals
    $this->_store_eof();

    // Store the workbook in an OLE container
    $this->store_OLE_file();
    }

/**
* Store the workbook in an OLE container if the total size of the workbook data
* is less than ~ 7MB.
*/
  function store_OLE_file()
    {
    $OLE  = new OLEwriter($this->_filename);
    // Write Worksheet data if data <~ 7MB
    if ($OLE->set_size($this->biffsize))
        {
        $OLE->write_header();
        $OLE->write($this->data);
        foreach($this->worksheets as $sheet) 
	    {
            while ($tmp = $sheet->get_data()) {
                $OLE->write($tmp);
                }
            }
        }
    $OLE->close();
    }

/**
* Calculate offsets for Worksheet BOF records.
*/
  function calc_sheet_offsets()
    {
    $BOF     = 11;
    $EOF     = 4;
    $offset  = $this->_datasize;
    for($i=0; $i < count($this->worksheets); $i++) {
        $offset += $BOF + strlen($this->worksheets[$i]->name);
        }
    $offset += $EOF;
    for($i=0; $i < count($this->worksheets); $i++) {
        $this->worksheets[$i]->offset = $offset;
        $offset += $this->worksheets[$i]->_datasize;
        }
    $this->biffsize = $offset;
    }

/**
* Store the Excel FONT records.
*/
  function _store_all_fonts()
    {
    // tmp_format is added by new(). We use this to write the default XF's
    $format = $this->tmp_format;
    $font   = $format->get_font();

    // Note: Fonts are 0-indexed. According to the SDK there is no index 4,
    // so the following fonts are 0, 1, 2, 3, 5
    //
    for($i=1; $i <= 5; $i++){
        $this->_append($font);
        }

    // Iterate through the XF objects and write a FONT record if it isn't the
    // same as the default FONT and if it hasn't already been used.
    //
    $fonts = array();
    $index = 6;                  // The first user defined FONT

    $key = $format->get_font_key(); // The default font from _tmp_format
    $fonts[$key] = 0;               // Index of the default font

    for($i=0; $i < count($this->formats); $i++) {
        $key = $this->formats[$i]->get_font_key();
        if (isset($fonts[$key])) {
            // FONT has already been used
            $this->formats[$i]->font_index = $fonts[$key];
            }
        else {
            // Add a new FONT record
            $fonts[$key]        = $index;
            $this->formats[$i]->font_index = $index;
            $index++;
            $font = $this->formats[$i]->get_font();
            $this->_append($font);
            }
        }
    }

/**
* Store user defined numerical formats i.e. FORMAT records
*/
  function _store_all_num_formats()
    {
    // Leaning num_format syndrome
    $hash_num_formats = array();
    $num_formats = array();
    $index = 164;

    // Iterate through the XF objects and write a FORMAT record if it isn't a
    // built-in format type and if the FORMAT string hasn't already been used.
    //
    foreach ($this->formats as $format) {
        $num_format = $format->num_format;

        // Check if $num_format is an index to a built-in format.
        // Also check for a string of zeros, which is a valid format string
        // but would evaluate to zero.
        //
        if (!preg_match("/^0+\d/",$num_format)) {
            if (preg_match("/^\d+$/",$num_format)); // built-in
                continue;
	}

        if (isset($hash_num_formats[$num_format])) {
            // FORMAT has already been used
            $format->num_format = $hash_num_formats[$num_format];
            }
        else{
            // Add a new FORMAT
            $hash_num_formats[$num_format] = $index;
            $format->num_format       = $index;
            array_push($num_formats,$num_format);
            $index++;
            }
        }

    // Write the new FORMAT records starting from 0xA4
    $index = 164;
    foreach ($num_formats as $num_format) {
        $this->store_num_format($num_format,$index);
        $index++;
        }
    }

/**
* Write all XF records.
*/
  function store_all_xfs()
    {
    // tmp_format is added by new(). We use this to write the default XF's
    // The default font index is 0
    //
    $format = $this->tmp_format;
    for ($i=0; $i <= 14; $i++) {
        $xf = $format->get_xf('style'); // Style XF
        $this->_append($xf);
        }

    $xf = $format->get_xf('cell');      // Cell XF
    $this->_append($xf);

    // User defined XFs
    for($i=0; $i < count($this->formats); $i++) {
        $xf = $this->formats[$i]->get_xf('cell');
        $this->_append($xf);
        }
    }

/**
* Write all STYLE records.
*/
  function store_all_styles()
    {
    $this->store_style();
    }

/**
* Write the EXTERNCOUNT and EXTERNSHEET records. These are used as indexes for
* the NAME records.
*/
  function _store_externs()
    {
    // Create EXTERNCOUNT with number of worksheets
    $this->store_externcount(count($this->worksheets));

    // Create EXTERNSHEET for each worksheet
    foreach ($this->sheetnames as $sheetname) {
        $this->store_externsheet($sheetname);
        }
    }

/**
* Write the NAME record to define the print area and the repeat rows and cols.
*/
  function _store_names()
    {
    // Create the print area NAME records
    foreach ($this->worksheets as $worksheet) {
        // Write a Name record if the print area has been defined
        if (isset($worksheet->print_rowmin))
	    {
            $this->store_name_short(
                $worksheet->index,
                0x06, // NAME type
                $worksheet->print_rowmin,
                $worksheet->print_rowmax,
                $worksheet->print_colmin,
                $worksheet->print_colmax
                );
            }
        }

    // Create the print title NAME records
    foreach ($this->worksheets as $worksheet)
        {
        $rowmin = $worksheet->_title_rowmin;
        $rowmax = $worksheet->_title_rowmax;
        $colmin = $worksheet->_title_colmin;
        $colmax = $worksheet->_title_colmax;

        // Determine if row + col, row, col or nothing has been defined
        // and write the appropriate record
        //
        if (isset($rowmin) && isset($colmin)) {
            // Row and column titles have been defined.
            // Row title has been defined.
            $this->store_name_long(
                $worksheet->index,
                0x07, // NAME type
                $rowmin,
                $rowmax,
                $colmin,
                $colmax
                );
            }
        elseif (isset($rowmin)) {
            // Row title has been defined.
            $this->store_name_short(
                $worksheet->index,
                0x07, // NAME type
                $rowmin,
                $rowmax,
                0x00,
                0xff
                );
            }
        elseif (isset($colmin)) {
            // Column title has been defined.
            $this->store_name_short(
                $worksheet->index,
                0x07, // NAME type
                0x0000,
                0x3fff,
                $colmin,
                $colmax
                );
            }
        else {
            // Print title hasn't been defined.
            }
        }
    }




/******************************************************************************
*
* BIFF RECORDS
*
*/

/**
* Write Excel BIFF WINDOW1 record.
*/
  function _store_window1()
    {
    $record    = 0x003D;                 // Record identifier
    $length    = 0x0012;                 // Number of bytes to follow

    $xWn       = 0x0000;                 // Horizontal position of window
    $yWn       = 0x0000;                 // Vertical position of window
    $dxWn      = 0x25BC;                 // Width of window
    $dyWn      = 0x1572;                 // Height of window

    $grbit     = 0x0038;                 // Option flags
    $ctabsel   = $this->selected;        // Number of workbook tabs selected
    $wTabRatio = 0x0258;                 // Tab to scrollbar ratio

    $itabFirst = $this->firstsheet;   // 1st displayed worksheet
    $itabCur   = $this->activesheet;  // Active worksheet

    $header    = pack("vv",        $record, $length);
    $data      = pack("vvvvvvvvv", $xWn, $yWn, $dxWn, $dyWn,
                                   $grbit,
                                   $itabCur, $itabFirst,
                                   $ctabsel, $wTabRatio);
    $this->_append($header.$data);
    }

/**
* Writes Excel BIFF BOUNDSHEET record.
*
* @param $sheetname Worksheet name
* @param $offsetLocation of worksheet BOF
*/
  function store_boundsheet($sheetname,$offset)
    {
    $record    = 0x0085;                    // Record identifier
    $length    = 0x07 + strlen($sheetname); // Number of bytes to follow

    $grbit     = 0x0000;                    // Sheet identifier
    $cch       = strlen($sheetname);        // Length of sheet name

    $header    = pack("vv",  $record, $length);
    $data      = pack("VvC", $offset, $grbit, $cch);
    $this->_append($header.$data.$sheetname);
    }

/**
* Write Excel BIFF STYLE records.
*/
  function store_style()
    {
    $record    = 0x0293;   // Record identifier
    $length    = 0x0004;   // Bytes to follow
                           
    $ixfe      = 0x8000;   // Index to style XF
    $BuiltIn   = 0x00;     // Built-in style
    $iLevel    = 0xff;     // Outline style level

    $header    = pack("vv",  $record, $length);
    $data      = pack("vCC", $ixfe, $BuiltIn, $iLevel);
    $this->_append($header.$data);
    }


/**
* Writes Excel FORMAT record for non "built-in" numerical formats.
*
* @param $format Custom format string
* @param $ifmt   Format index code
*/
  function store_num_format($format,$ifmt)
    {
    $record    = 0x041E;                      // Record identifier
    $length    = 0x03 + strlen($format);      // Number of bytes to follow

    $cch       = strlen($format);             // Length of format string

    $header    = pack("vv", $record, $length);
    $data      = pack("vC", $ifmt, $cch);
    $this->_append($header.$data.$format);
    }

/**
* Write Excel 1904 record to indicate the date system in use.
*/
  function _store_1904()
    {
    $record    = 0x0022;         // Record identifier
    $length    = 0x0002;         // Bytes to follow

    $f1904     = $this->_1904;   // Flag for 1904 date system

    $header    = pack("vv", $record, $length);
    $data      = pack("v", $f1904);
    $this->_append($header.$data);
    }


/**
* Write BIFF record EXTERNCOUNT to indicate the number of external sheet
* references in the workbook.
*
* Excel only stores references to external sheets that are used in NAME.
* The workbook NAME record is required to define the print area and the repeat
* rows and columns.
*
* A similar method is used in Worksheet.pm for a slightly different purpose.
*
* @param $cxals Number of external references
*/
  function store_externcount($cxals)
    {
    $record   = 0x0016;          // Record identifier
    $length   = 0x0002;          // Number of bytes to follow

    $header   = pack("vv", $record, $length);
    $data     = pack("v",  $cxals);
    $this->_append($header.$data);
    }


/**
* Writes the Excel BIFF EXTERNSHEET record. These references are used by
* formulas. NAME record is required to define the print area and the repeat
* rows and columns.
*
* A similar method is used in Worksheet.pm for a slightly different purpose.
*
* @param $sheetname Worksheet name
*/
  function store_externsheet($sheetname)
    {
    $record      = 0x0017;                     // Record identifier
    $length      = 0x02 + strlen($sheetname);  // Number of bytes to follow
                                               
    $cch         = strlen($sheetname);         // Length of sheet name
    $rgch        = 0x03;                       // Filename encoding

    $header      = pack("vv",  $record, $length);
    $data        = pack("CC", $cch, $rgch);
    $this->_append($header.$data.$sheetname);
    }


###############################################################################
#
# _store_name_short()
#
#
# Store the NAME record in the short format that is used for storing the print
# area, repeat rows only and repeat columns only.
#
  function store_name_short($index,$type,$rowmin,$rowmax,$colmin,$colmax)
    {
    $record          = 0x0018;       # Record identifier
    $length          = 0x0024;       # Number of bytes to follow

    //$index           = shift;        # Sheet index
    //$type            = shift;

    $grbit           = 0x0020;       # Option flags
    $chKey           = 0x00;         # Keyboard shortcut
    $cch             = 0x01;         # Length of text name
    $cce             = 0x0015;       # Length of text definition
    $ixals           = $index +1;    # Sheet index
    $itab            = $ixals;       # Equal to ixals
    $cchCustMenu     = 0x00;         # Length of cust menu text
    $cchDescription  = 0x00;         # Length of description text
    $cchHelptopic    = 0x00;         # Length of help topic text
    $cchStatustext   = 0x00;         # Length of status bar text
    $rgch            = $type;        # Built-in name type

    $unknown03       = 0x3b;
    $unknown04       = 0xffff-$index;
    $unknown05       = 0x0000;
    $unknown06       = 0x0000;
    $unknown07       = 0x1087;
    $unknown08       = 0x8005;

    //my $rowmin          = $_[0];        # Start row
    //my $rowmax          = $_[1];        # End row
    //my $colmin          = $_[2];        # Start column
    //my $colmax          = $_[3];        # end column


    $header             = pack("vv", $record, $length);
    $data               = pack("v", $grbit);
    $data              .= pack("C", $chKey);
    $data              .= pack("C", $cch);
    $data              .= pack("v", $cce);
    $data              .= pack("v", $ixals);
    $data              .= pack("v", $itab);
    $data              .= pack("C", $cchCustMenu);
    $data              .= pack("C", $cchDescription);
    $data              .= pack("C", $cchHelptopic);
    $data              .= pack("C", $cchStatustext);
    $data              .= pack("C", $rgch);
    $data              .= pack("C", $unknown03);
    $data              .= pack("v", $unknown04);
    $data              .= pack("v", $unknown05);
    $data              .= pack("v", $unknown06);
    $data              .= pack("v", $unknown07);
    $data              .= pack("v", $unknown08);
    $data              .= pack("v", $index);
    $data              .= pack("v", $index);
    $data              .= pack("v", $rowmin);
    $data              .= pack("v", $rowmax);
    $data              .= pack("C", $colmin);
    $data              .= pack("C", $colmax);
    $this->_append($header.$data);
    }


###############################################################################
#
# _store_name_long()
#
#
# Store the NAME record in the long format that is used for storing the repeat
# rows and columns when both are specified. This share a lot of code with
# _store_name_short() but we use a separate method to keep the code clean.
# Code abstraction for reuse can be carried too far, and I should know. ;-)
#
  function store_name_long($index,$type,$rowmin,$rowmax,$colmin,$colmax)
    {
    $record          = 0x0018;       # Record identifier
    $length          = 0x003d;       # Number of bytes to follow
    //$index           = shift;        # Sheet index
    //$type            = shift;
    $grbit           = 0x0020;       # Option flags
    $chKey           = 0x00;         # Keyboard shortcut
    $cch             = 0x01;         # Length of text name
    $cce             = 0x002e;       # Length of text definition
    $ixals           = $index +1;    # Sheet index
    $itab            = $ixals;       # Equal to ixals
    $cchCustMenu     = 0x00;         # Length of cust menu text
    $cchDescription  = 0x00;         # Length of description text
    $cchHelptopic    = 0x00;         # Length of help topic text
    $cchStatustext   = 0x00;         # Length of status bar text
    $rgch            = $type;        # Built-in name type

    $unknown01       = 0x29;
    $unknown02       = 0x002b;
    $unknown03       = 0x3b;
    $unknown04       = 0xffff-$index;
    $unknown05       = 0x0000;
    $unknown06       = 0x0000;
    $unknown07       = 0x1087;
    $unknown08       = 0x8008;

    //my $rowmin          = $_[0];        # Start row
    //my $rowmax          = $_[1];        # End row
    //my $colmin          = $_[2];        # Start column
    //my $colmax          = $_[3];        # end column


    $header             = pack("vv",  $record, $length);
    $data               = pack("v", $grbit);
    $data              .= pack("C", $chKey);
    $data              .= pack("C", $cch);
    $data              .= pack("v", $cce);
    $data              .= pack("v", $ixals);
    $data              .= pack("v", $itab);
    $data              .= pack("C", $cchCustMenu);
    $data              .= pack("C", $cchDescription);
    $data              .= pack("C", $cchHelptopic);
    $data              .= pack("C", $cchStatustext);
    $data              .= pack("C", $rgch);
    $data              .= pack("C", $unknown01);
    $data              .= pack("v", $unknown02);
    # Column definition
    $data              .= pack("C", $unknown03);
    $data              .= pack("v", $unknown04);
    $data              .= pack("v", $unknown05);
    $data              .= pack("v", $unknown06);
    $data              .= pack("v", $unknown07);
    $data              .= pack("v", $unknown08);
    $data              .= pack("v", $index);
    $data              .= pack("v", $index);
    $data              .= pack("v", 0x0000);
    $data              .= pack("v", 0x3fff);
    $data              .= pack("C", $colmin);
    $data              .= pack("C", $colmax);
    # Row definition
    $data              .= pack("C", $unknown03);
    $data              .= pack("v", $unknown04);
    $data              .= pack("v", $unknown05);
    $data              .= pack("v", $unknown06);
    $data              .= pack("v", $unknown07);
    $data              .= pack("v", $unknown08);
    $data              .= pack("v", $index);
    $data              .= pack("v", $index);
    $data              .= pack("v", $rowmin);
    $data              .= pack("v", $rowmax);
    $data              .= pack("C", 0x00);
    $data              .= pack("C", 0xff);
    # End of data
    $data              .= pack("C", 0x10);
    $this->_append($header.$data);
    }


###############################################################################
#
# _store_palette()
#
# Stores the PALETTE biff record.
#
  function store_palette()
    {
    $aref            = $this->palette;

    $record          = 0x0092;                 # Record identifier
    $length          = 2 + 4 * count($aref);   # Number of bytes to follow
    $ccv             =         count($aref);   # Number of RGB values to follow
    $data = '';                                     # The RGB data

    # Pack the RGB data
    foreach($aref as $color)
      {
      foreach($color as $byte)
        $data .= pack("C",$byte);
      }

    $header = pack("vvv",  $record, $length, $ccv);
    $this->_append($header.$data);
    }
  }
?>