<?php if (!defined('BASEPATH')) exit('No direct script access allowed');

/*
    // Original
    Bubble Babble Binary Data Encoding - PHP5 Library

    See http://en.wikipedia.org/wiki/Bubble_Babble for details.

    Copyright 2011 BohwaZ - http://bohwaz.net/

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <http://www.gnu.org/licenses/>.
    
    // For CodeIgniter
    Bubble Babble for CodeIgniter, by Akira : http://www.akibatech.fr
    Licence : WTFPL (http://en.wikipedia.org/wiki/WTFPL)

    Using with CodeIgniter :
        
        Copy Bubble.php in your library folder.

        Then, you need to load this library in CodeIgniter :
        $this->load->library('bubble');
        
        Encode :
        $this->bubble->encode('Pineapple');
        // => xigak-nyryk-humil-bosek-sonax

        Decode :
        $this->bubble->decode('xigak-nyryk-humil-bosek-sonax');
        // => Pineapple

        Detect BubbleBabble's encoding :
        $this->bubble->detect($string);
        // => true of false
*/

class bubble 
{
    protected $vowels = 'aeiouy';
    protected $consonants = 'bcdfghklmnprstvzx';

    public function encode($src)
    {
        $src = (string) $src;
        $out = 'x';
        $c = 1;

        for ($i = 0;; $i += 2)
        {
            if ($i >= strlen($src))
            {
                $out .= $this->vowels[$c%6] . $this->consonants[16] . $this->vowels[$c/6];
                break;
            }

            $byte1 = ord($src[$i]);

            $out .= $this->vowels[((($byte1>>6)&3)+$c)%6];
            $out .= $this->consonants[($byte1>>2)&15];
            $out .= $this->vowels[(($byte1&3)+($c/6))%6];

            if ($i+1 >= strlen($src))
                break;

            $byte2 = ord($src[$i + 1]);
            $out .= $this->consonants[($byte2>>4)&15];
            $out .= '-';
            $out .= $this->consonants[$byte2&15];

            $c = ($c * 5 + $byte1 * 7 + $byte2) % 36;
        }

        $out .= 'x';
        return $out;
    }

    protected function _decode2WayByte($a1, $a2, $offset)
    {
        if ($a1 > 16)
            show_error("Corrupt string at offset ".$offset);

        if ($a2 > 16)
            show_error("Corrupt string at offset ".($offset+2));

        return ($a1 << 4) | $a2;
    }

    protected function _decode3WayByte($a1, $a2, $a3, $offset, $c)
    {
        $high2 = ($a1 - ($c%6) + 6) % 6;

        if ($high2 >= 4)
            show_error("Corrupt string at offset ".$offset);

        if ($a2 > 16)
            show_error("Corrupt string at offset ".($offset+1));

        $mid4 = $a2;
        $low2 = ($a3 - ($c/6%6) + 6) % 6;

        if ($low2 >= 4)
            show_error("Corrupt string at offset ".($offset+2));

        return $high2<<6 | $mid4<<2 | $low2;
    }

    protected function _decodeTuple($src, $pos)
    {
        $tuple = array(
            strpos($this->vowels, $src[0]),
            strpos($this->consonants, $src[1]),
            strpos($this->vowels, $src[2])
        );

        if (isset($src[3]))
        {
            $tuple[] = strpos($this->consonants, $src[3]);
            $tuple[] = '-';
            $tuple[] = strpos($this->consonants, $src[5]);
        }

        return $tuple;
    }

    public function decode($src)
    {
        $src = (string) $src;

        $c = 1;

        if ($src[0] != 'x')
            show_error("Corrupt string at offset 0: must begin with a 'x'");

        if (substr($src, -1) != 'x')
            show_error("Corrupt string at offset 0: must end with a 'x'");

        if (strlen($src) != 5 && strlen($src)%6 != 5)
            show_error("Corrupt string at offset 0: wrong length");

        $src = str_split(substr($src, 1, -1), 6);
        $last_tuple = count($src) - 1;
        $out = '';

        foreach ($src as $k=>$tuple)
        {
            $pos = $k * 6;
            $tuple = $this->_decodeTuple($tuple, $pos);

            if ($k == $last_tuple)
            {
                if ($tuple[1] == 16)
                {
                    if ($tuple[0] != $c % 6)
                        show_error("Corrupt string at offset $pos (checksum)");
                    if ($tuple[2] != (int)($c / 6))
                        show_error("Corrupt string at offset ".($pos+2)." (checksum)");
                }
                else
                {
                    $byte = $this->_decode3WayByte($tuple[0], $tuple[1], $tuple[2], $pos, $c);
                    $out .= chr($byte);
                }
            }
            else
            {
                $byte1 = $this->_decode3WayByte($tuple[0], $tuple[1], $tuple[2], $pos, $c);
                $byte2 = $this->_decode2WayByte($tuple[3], $tuple[5], $pos);

                $out .= chr($byte1);
                $out .= chr($byte2);

                $c = ($c * 5 + $byte1 * 7 + $byte2) % 36;
            }
        }

        return $out;
    }

    public function detect($string)
    {
        if ($string[0] != 'x' || substr($string, -1) != 'x')
            return false;

        if (strlen($string) != 5 && strlen($string)%6 != 5)
            return false;

        if (!preg_match('/^(['.$this->consonants.$this->vowels.']{5})(-(?1))*$/', $string))
            return false;

        return true;
    }
}

?>