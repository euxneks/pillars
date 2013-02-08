pillars
=======

Pillars is a simple PHP framework to allow you to build a site *very* quickly, and it uses clean urls.


License
======
The MIT License (MIT)
Copyright (c) 2013 Chris Tooley <euxneks@gmail.com>

Permission is hereby granted, free of charge, to any person obtaining a copy of this software and associated documentation files (the "Software"), to deal in the Software without restriction, including without limitation the rights to use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of the Software, and to permit persons to whom the Software is furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.


Note
====
Please note I am providing this software in good faith that you will help me make it better. If you find something interesting and want to modify it for your own use, you are free to do as stipulated in the MIT license above, but I would ask that you tell me what you've changed. Perhaps your change is good enough to put up online :)
-Chris

Installation
-----------
> cp .htaccess\_template .htaccess

Modify .htaccess file so that RewriteBase is the correct base URL for where you've installed pillars

> cd /includes && cp configuration.template.php configuration.php

Modify configuration.php with the various values you need. For a basic blog, you need to set all 'db\_config' parameters.  So far only MySQL and MariaDB are implemented - postgres is a future option and so is making the DB abstract. So far I am not entirely impressed with the various db abstraction classes out there (too complicated, too large, pain in the ass to install, etc.)

Hopefully this source makes some sense. I'll be doing more extensive documentation at a later date.
