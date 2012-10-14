Bubble
============================

An alternative to base64 functions, with eye-candy result !

Origin
----------------------------
Bubble Babble Binary Data Encoding - PHP5 Library

See http://en.wikipedia.org/wiki/Bubble_Babble for details.

Usage
----------------------------

Load Spark :

    $this->load->spark('bubble/x.x.x');

Encode with :

    $this->bubble->encode($raw_string);
    // return something like "xigak-nyryk-humil-bosek-sonax"
    
Decode with :

    $this->bubble->decode($encoded_string);
    // return decoded result
    
Detect encode with :

	$this->bubble->detect($string);
    // return true or false
----------------------------

Changelog
----------------------------

**1.0.0**

* Initial Release