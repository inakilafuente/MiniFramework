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

/**
* Class for generating Excel Spreadsheets
*
* @autor Xavier Noguer <xnoguer@rezebra.com>
*/

class Format
  {
  /**
  * Constructor
  *
  * @param array $properties array with properties to be set on initialization.
  */
  function __construct($index = 0,$properties =  array())
    {
    $this->xf_index       = $index;

    $this->font_index     = 0;
    $this->font           = 'Arial';
    $this->size           = 10;
    $this->bold           = 0x0190;
    $this->italic         = 0;
    $this->color          = 0x7FFF;
    $this->underline      = 0;
    $this->font_strikeout = 0;
    $this->font_outline   = 0;
    $this->font_shadow    = 0;
    $this->font_script    = 0;
    $this->font_family    = 0;
    $this->font_charset   = 0;

    $this->num_format     = 0;

    $this->hidden         = 0;
    $this->locked         = 1;

    $this->text_h_align   = 0;
    $this->text_wrap      = 0;
    $this->text_v_align   = 2;
    $this->text_justlast  = 0;
    $this->rotation       = 0;

    $this->fg_color       = 0x40;
    $this->bg_color       = 0x41;

    $this->pattern        = 0;

    $this->bottom         = 0;
    $this->top            = 0;
    $this->left           = 0;
    $this->right          = 0;

    $this->bottom_color   = 0x40;
    $this->top_color      = 0x40;
    $this->left_color     = 0x40;
    $this->right_color    = 0x40;

    // Set properties passed to Workbook::addformat()
    foreach($properties as $property => $value)
        {
        if(method_exists($this,"set_$property"))
	    {
	    $aux = 'set_'.$property;
            $this->$aux($value);
	    }
        }
    }

/*
###############################################################################
#
# copy($format)
#
# Copy the attributes of another Spreadsheet::WriteExcel::Format object.
#
sub copy {
    my $this  = shift;
    my $other = $_[0];

    return unless defined $other;
    return unless (ref($this) eq ref($other));

    my $xf = $this->{_xf_index};    # Store XF index assigned by Workbook.pm
    %$this = %$other;               # Copy properties
    $this->{_xf_index} = $xf;       # Restore XF index
}*/

/**
* Generate an Excel BIFF XF record.
*
* @param $style The type of the XF record.
*/
  function get_xf($style)
    {
    // Set the type of the XF record and some of the attributes.
    if ($style == "style") {
        $style = 0xFFF5;
        }
    else {
        $style   = $this->locked;
        $style  |= $this->hidden << 1;
        }

    // Flags to indicate if attributes have been set.
    $atr_num     = ($this->num_format != 0)?1:0;
    $atr_fnt     = ($this->font_index != 0)?1:0;
    $atr_alc     = ($this->text_wrap)?1:0;
    $atr_bdr     = ($this->bottom   ||
                    $this->top      ||
                    $this->left     ||
                    $this->right)?1:0;
    $atr_pat     = (($this->fg_color != 0x40) ||
                    ($this->bg_color != 0x41) ||
                    $this->pattern)?1:0;
    $atr_prot    = 0;

    // Zero the default border colour if the border has not been set.
    if ($this->bottom == 0)
        $this->bottom_color = 0;
    if ($this->top  == 0)
        $this->top_color = 0;
    if ($this->right == 0)
        $this->right_color = 0;
    if ($this->left == 0)
        $this->left_color = 0;

    $record         = 0x00E0;              // Record identifier
    $length         = 0x0010;              // Number of bytes to follow
                                           
    $ifnt           = $this->font_index;   // Index to FONT record
    $ifmt           = $this->num_format;   // Index to FORMAT record

    $align          = $this->text_h_align;       // Alignment
    $align         |= $this->text_wrap     << 3;
    $align         |= $this->text_v_align  << 4;
    $align         |= $this->text_justlast << 7;
    $align         |= $this->rotation      << 8;
    $align         |= $atr_num                << 10;
    $align         |= $atr_fnt                << 11;
    $align         |= $atr_alc                << 12;
    $align         |= $atr_bdr                << 13;
    $align         |= $atr_pat                << 14;
    $align         |= $atr_prot               << 15;

    $icv            = $this->fg_color;           // fg and bg pattern colors
    $icv           |= $this->bg_color      << 7;

    $fill           = $this->pattern;            // Fill and border line style
    $fill          |= $this->bottom        << 6;
    $fill          |= $this->bottom_color  << 9;

    $border1        = $this->top;                // Border line style and color
    $border1       |= $this->left          << 3;
    $border1       |= $this->right         << 6;
    $border1       |= $this->top_color     << 9;

    $border2        = $this->left_color;         // Border color
    $border2       |= $this->right_color   << 7;

    $header      = pack("vv",       $record, $length);
    $data        = pack("vvvvvvvv", $ifnt, $ifmt, $style, $align,
                                    $icv, $fill,
                                    $border1, $border2);
    return($header.$data);
    }

/**
* Generate an Excel BIFF FONT record.
*/
  function get_font()
    {
    $dyHeight   = $this->size * 20;    // Height of font (1/20 of a point)
    $icv        = $this->color;        // Index to color palette
    $bls        = $this->bold;         // Bold style
    $sss        = $this->font_script;  // Superscript/subscript
    $uls        = $this->underline;    // Underline
    $bFamily    = $this->font_family;  // Font family
    $bCharSet   = $this->font_charset; // Character set
    $rgch       = $this->font;         // Font name

    $cch        = strlen($rgch);       // Length of font name
    $record     = 0x31;                // Record identifier
    $length     = 0x0F + $cch;         // Record length
    $reserved   = 0x00;                // Reserved
    $grbit      = 0x00;                // Font attributes
    if ($this->italic)
        $grbit     |= 0x02;
    if ($this->font_strikeout)
        $grbit     |= 0x08;
    if ($this->font_outline)
        $grbit     |= 0x10;
    if ($this->font_shadow)
        $grbit     |= 0x20;

    $header  = pack("vv",         $record, $length);
    $data    = pack("vvvvvCCCCC", $dyHeight, $grbit, $icv, $bls,
                                  $sss, $uls, $bFamily,
                                  $bCharSet, $reserved, $cch);
    return($header . $data. $this->font);
    }

/**
* Returns a unique hash key for a font. Used by Workbook->_store_all_fonts()
*
* The elements that form the key are arranged to increase the probability of
* generating a unique key. Elements that hold a large range of numbers
* (eg. _color) are placed between two binary elements such as _italic
*/
  function get_font_key()
    {
    $key  = "$this->font$this->size";
    $key .= "$this->font_script$this->underline";
    $key .= "$this->font_strikeout$this->bold$this->font_outline";
    $key .= "$this->font_family$this->font_charset";
    $key .= "$this->font_shadow$this->color$this->italic";
    $key  = str_replace(" ","_",$key);
    return ($key);
    }

/**
* Returns the used by Worksheet->XF()
*/
  function get_xf_index()
    {
    return($this->xf_index);
    }

/**
* Used in conjunction with the set_xxx_color methods to convert a color
* string into a number. Color range is 0..63 but we will restrict it
* to 8..63 to comply with Gnumeric. Colors 0..7 are repeated in 8..15.
*/
  function _get_color($name_color = '')
    {
    $colors = array(
                    'aqua'    => 0x0F,
                    'cyan'    => 0x0F,
                    'black'   => 0x08,
                    'blue'    => 0x0C,
                    'brown'   => 0x10,
                    'magenta' => 0x0E,
                    'fuchsia' => 0x0E,
                    'gray'    => 0x17,
                    'grey'    => 0x17,
                    'green'   => 0x11,
                    'lime'    => 0x0B,
                    'navy'    => 0x12,
                    'orange'  => 0x35,
                    'purple'  => 0x14,
                    'red'     => 0x0A,
                    'silver'  => 0x16,
                    'white'   => 0x09,
                    'yellow'  => 0x0D
                   );

    // Return the default color, 0x7FFF, if undef,
    if($name_color == '')
        return(0x7FFF);

    // or the color string converted to an integer,
    if(isset($colors[$name_color]))
        return($colors[$name_color]);

    // or the default color if string is unrecognised,
    if(preg_match("/\D/",$name_color))
        return(0x7FFF);

    // or an index < 8 mapped into the correct range,
    if($name_color < 8)
        return($name_color + 8);

    // or the default color if arg is outside range,
    if($name_color > 63)
        return(0x7FFF);

    // or an integer in the valid range
    return($name_color);
    }

/**
* Set cell alignment.
*/
  function set_align($location)
    {
    //return if not defined $location;  # No default
    if (preg_match("/\d/",$location))
        return(0);                      // Ignore numbers

    $location = strtolower($location);

    if ($location == 'left')
        $this->text_h_align = 1;
    if ($location == 'centre')
        $this->text_h_align = 2; 
    if ($location == 'center')
        $this->text_h_align = 2; 
    if ($location == 'right')
        $this->text_h_align = 3; 
    if ($location == 'fill')
        $this->text_h_align = 4; 
    if ($location == 'justify')
        $this->text_h_align = 5;
    if ($location == 'merge')
        $this->text_h_align = 6;
    if ($location == 'equal_space') // For T.K.
        $this->text_h_align = 7; 
    if ($location == 'top')
        $this->text_v_align = 0; 
    if ($location == 'vcentre')
        $this->text_v_align = 1; 
    if ($location == 'vcenter')
        $this->text_v_align = 1; 
    if ($location == 'bottom')
        $this->text_v_align = 2; 
    if ($location == 'vjustify')
        $this->text_v_align = 3; 
    if ($location == 'vequal_space') // For T.K.
        $this->text_v_align = 4; 
    }

/**
* This is an alias for the unintuitive set_align('merge')
*/
  function set_merge()
    {
    $this->set_align('merge');
    }

/**
* Bold has a range 0x64..0x3E8.
* 0x190 is normal. 0x2BC is bold.
*/
  function set_bold($weight = 1)
    {
    if(!isset($weight))
        $weight = 0x2BC;  // Bold text
    if($weight == 1)
        $weight = 0x2BC;  // Bold text
    if($weight == 0)
        $weight = 0x190;  // Normal text
    if($weight <  0x064)
        $weight = 0x190;  // Lower bound
    if($weight >  0x3E8)
        $weight = 0x190;  // Upper bound
    $this->bold = $weight;
    }


/************************************
* FUNCTIONS FOR SETTING CELLS BORDERS
*/

/**
* Sets the bottom border of the cell
*
* @param $style style of the cell border
*/
  function set_bottom($style)
    {
    $this->bottom = $style;
    }

/**
* Sets the top border of the cell
*
* @param $style style of the cell border
*/
  function set_top($style)
    {
    $this->top = $style;
    }

/**
* Sets the left border of the cell
*
* @param $style style of the cell border
*/
  function set_left($style)
    {
    $this->left = $style;
    }

/**
* Sets the right border of the cell
*
* @param $style style of the cell border
*/
  function set_right($style)
    {
    $this->right = $style;
    }


/**
* Set cells borders to the same style
*
* @param $style style of the cell border
*/
  function set_border($style)
    {
    $this->set_bottom($style);
    $this->set_top($style);
    $this->set_left($style);
    $this->set_right($style);
    }


/*******************************************
* FUNCTIONS FOR SETTING CELLS BORDERS COLORS
*/

/**
* Set cells border to the same color
*
* @param $color The color we are setting
*/
  function set_border_color($color)
    {
    $this->set_bottom_color($color);
    $this->set_top_color($color);
    $this->set_left_color($color);
    $this->set_right_color($color);
    }

  function set_bottom_color($arg)
    {
    $value = $this->_get_color($arg);
    $this->bottom_color = $value;
    }

  function set_top_color($arg)
    {
    $value = $this->_get_color($arg);
    $this->top_color = $value;
    }

  function set_left_color($arg)
    {
    $value = $this->_get_color($arg);
    $this->left_color = $value;
    }

  function set_right_color($arg)
    {
    $value = $this->_get_color($arg);
    $this->right_color = $value;
    }

/*********/

  function set_fg_color($arg)
    {
    $value = $this->_get_color($arg);
    $this->fg_color = $value;
    }
  
  function set_bg_color($arg)
    {
    $value = $this->_get_color($arg);
    $this->bg_color = $value;
    }

  function set_color($arg)
    {
    $value = $this->_get_color($arg);
    $this->color = $value;
    }

  function set_pattern($arg = 1)
    {
    $this->pattern = $arg;
    }

  function set_underline($underline)
    {
    $this->underline = $underline;
    }

  function set_size($size)
    {
    $this->size = $size;
    }

  function set_wrap($text_wrap)
  {
    $this->text_wrap = $text_wrap;
  }


/*
###############################################################################
#
# AUTOLOAD. Deus ex machina.
#
sub AUTOLOAD {

    my $this = shift;

    # Ignore calls to DESTROY
    return if $AUTOLOAD =~ /::DESTROY$/;

    # Check for a valid method names, ie. "set_xxx_yyy".
    $AUTOLOAD =~ /.*::set(\w+)/ or die "Unknown method: $AUTOLOAD\n";

    # Match the attribute, ie. "_xxx_yyy".
    my $attribute = $1;

    # Check that the attribute exists
    exists $this->{$attribute}  or die "Unknown method: $AUTOLOAD\n";

    # The attribute value
    my $value;

    # Determine the value of the attribute
    if ($AUTOLOAD =~ /.*::set\w+color$/) {
        $value =  _get_color($_[0]); # For "set_xxx_color" methods
    }
    elsif (not defined($_[0])) {
        $value = 1; # Default is 1
    }
    else {
        $value = $_[0];
    }

    $this->{$attribute} = $value;
}*/

  }
?>