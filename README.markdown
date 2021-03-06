Description
-----------
This is a new version of the website for the [Stacks project](http://stacks.math.columbia.edu), enabling a comment system, improved tag lookup and a full-powered online view of its contents.


Configuration
-------------
The following configuration is to remind me of what I have to do to get an instance running. It is by no means complete, let alone finished. In due time this will be extended.


1. clone the `stacks-website` project using
`git clone https://github.com/pbelmans/stacks-website.git`

2. change directories to `stacks-website/`and initialize the submodules using `git submodule init`, `git submodule update` and either `git submodule foreach git pull origin master` if you want to update all submodules (including EpicEditor, MathJax and XyJax) or just `cd tex/`, `git checkout master`,  `git pull` if you wish to play it safe

3. create all necessary information about tags in the project by running `make tags` in `stacks-website/tex/`, make sure that the URLs in `stacks-website/tex/scripts/tag_up.py` are correct

4. create the database by calling `python scripts/create_database.py` in `stacks-website/`

5. put the file `stacks.sqlite` (the newly created database) in a directory that is not accessible from outside, apply `chmod 777 stacks.sqlite` and `chmod 777 ../` from the location of the database (PHP requires that both the database and the directory containing it have these...)

6. make the required modifications to `stacks-website/config.php` and `stacks-website/scripts/config.py`, mostly concerning the location of the website and the database on the server

7. run `python scripts/update.py`

8. get the correct styling in EpicEditor by executing `ln -s css/stacks-editor.css EpicEditor/epiceditor/themes/editor/stacks-editor.css` and `ln -s css/stacks-preview.css EpicEditor/epiceditor/themes/preview/stacks-preview.css` from the `stacks-website` directory

9. make MathJax aware of XyJax by executing `ln -s ../../XyJax/extensions/fp.js MathJax/extensions/fp.js` and `ln -s ../../../XyJax/extensions/TeX/xypic.js MathJax/extensions/TeX/xypic.js` from the `stacks-website` directory


Updating the website
--------------------
1. Update the `tex/` folder using `git pull` in `tex/`

2. Run steps 3 and 7 of the above
