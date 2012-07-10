<?php
  header('Content-Type: text/html; charset=utf-8');
?>
<!doctype html>
<?php
  error_reporting(E_ALL);

  include('config.php');
  include('functions.php');
  include('php-markdown-extra-math/markdown.php');

  try {
    $db = new PDO(get_database_location());
  }
  catch(PDOException $e) {
    echo $e->getMessage();
  }

  function get_comments($tag) {
    assert(is_valid_tag($tag));

    global $db;
    $comments = array();
    try {
      $sql = $db->prepare('SELECT id, tag, author, date, comment, site FROM comments WHERE tag = :tag');
      $sql->bindParam(':tag', $tag);

      if ($sql->execute()) {
        while ($row = $sql->fetch()) array_push($comments, $row);
      }
    }
    catch(PDOException $e) {
      echo $e->getMessage();

      return array();
    }

    return $comments;
  }

  function section_exists($id) {
    // if $id is empty we don't check, $id was generated by splitting a full chapter.section.subsection.result reference, but if only a chapter is present $id is empty
    if (empty($id)) {
      return true;
    }

    global $db;

    try {
      $sql = $db->prepare('SELECT COUNT(*) FROM sections WHERE number = :id');
      $sql->bindParam(':id', $id);

      if ($sql->execute()) {
        return intval($sql->fetchColumn()) > 0;
      }
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  
    return false;
  }

  function tag_to_integer($tag) {
    assert(is_valid_tag($tag));

    $result = 0;
    for ($i = 0; $i < strlen($tag); $i++)
      $result += ((ord($tag[$i]) < 58) ? ord($tag[$i]) - 48 : ord($tag[$i]) - 55) * pow(36, 3 - $i);

    return $result;
  }

  function integer_to_tag($value) {
    $tag = '';

    for ($i = 0; $i < 4; $i++) {
      $tag .= ($value % 36 < 10) ? chr(($value % 36) + 48) : chr(($value % 36) + 55);
      $value = (int) ($value / 36);
    }

    return strrev($tag);
  }

  function get_section($id) {
    assert(section_exists($id));

    global $db;

    try {
      $sql = $db->prepare('SELECT number, title, filename FROM sections WHERE number = :id');
      $sql->bindParam(':id', $id);

      if ($sql->execute()) {
        while ($row = $sql->fetch()) return $row;
      }
    }
    catch(PDOException $e) {
      echo $e->getMessage();
    }
  }

  function print_captcha() {
    print("<p>In order to prevent bots from posting comments, we would like you to prove that you are human. You can do this by <em>filling in the name of the current tag</em> in the following box. So in case this is tag <var>0321</var> you just have to write <var>0321</var>. This <abbr title='Completely Automated Public Turing test to tell Computers and Humans Apart'>captcha</abbr> seems more appropriate than the usual illegible gibberish, right?</p>\n");
?>
      <label for="check">Tag:</label>
      <input type="text" name="check" id="check"><br>
<?php
  }

  function print_comment_input($tag) {
?>
    <h2>Add a comment on tag <var><?php print(htmlspecialchars($_GET['tag'])); ?></var></h2>
    <p>Your email address will not be published. Required fields are marked.
  
    <p>In your comment you can use <a href="http://daringfireball.net/projects/markdown/">Markdown</a> and LaTeX style mathematics (enclose it like <code>$\pi$</code>). A preview option is available if you wish to see how it works out (just click on the eye in the lower-right corner).
  
    <form name="comment" id="comment-form" action="<?php print(full_url('post.php')); ?>" method="post">
      <label for="name">Name<sup>*</sup>:</label>
      <input type="text" name="name" id="name"><br>
  
      <label for="mail">E-mail<sup>*</sup>:</label>
      <input type="text" name="email" id="mail"><br>
  
      <label for="site">Site:</label>
      <input type="text" name="site" id="site"><br>
  
      <label>Comment:</label> <span id="epiceditor-status"></span>
      <textarea name="comment" id="comment-textarea" cols="80" rows="10"></textarea>
      <div id="epiceditor" style="border: 1px solid black;"></div>
      <script type='text/javascript'>
        // Chromium (and Chrome too I presume) adds a bogus character when a space follows after a line break (or something like that)
        // remove this by hand for now TODO fix EpicEditor
        function sanitize(s) {
          var output = '';
          for (c in s) {
            if (s.charCodeAt(c) != 160) output += s[c];
          }
         
          return output;
        }

        var fullscreenNotice = false;

        var editor = new EpicEditor(options).load(function() {
            // TODO find out why this must be a callback in the loader, editor.on('load', ...) doesn't seem to be working?!
            // hide textarea, EpicEditor will take over
            document.getElementById('comment-textarea').style.display = 'none';
            // when the form is submitted copy the contents from EpicEditor to textarea
            document.getElementById('comment-form').onsubmit = function() {
              document.getElementById('comment-textarea').value = sanitize(editor.exportFile());
            };

            // add a notice on how to get out the fullscreen mode
            var wrapper = this.getElement('wrapper');
            var button = wrapper.getElementsByClassName('epiceditor-fullscreen-btn')[0];
            button.onclick = function() {
              if (!fullscreenNotice) {
                alert('To get out the fullscreen mode, press Escape.');
                fullscreenNotice = true;
              }
            }

            // inform the user he is in preview mode
            document.getElementById('epiceditor-status').innerHTML = '(editing)';
        });

        function preview(iframe) {
          var mathjax = iframe.contentWindow.MathJax;
  
          mathjax.Hub.Config({
            tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]}
          });
  
          var previewer = iframe.contentDocument.getElementById('epiceditor-preview');
          mathjax.Hub.Queue(mathjax.Hub.Typeset(previewer));
        }
  
        editor.on('preview', function() {
            var iframe = editor.getElement('previewerIframe');
  
            if (iframe.contentDocument.getElementById('previewer-mathjax') == null) {
              var script = iframe.contentDocument.createElement('script');
              script.type = 'text/javascript';
              script.src = 'http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS_HTML';
              script.setAttribute('id', 'previewer-mathjax');
              iframe.contentDocument.head.appendChild(script);
            }

            // inform the user he is in preview mode
            document.getElementById('epiceditor-status').innerHTML = '(previewing)';

            // wait a little for MathJax to initialize
            // TODO might this be possible through a callback?
            if (iframe.contentWindow.MathJax == null) {
              setTimeout(function() { preview(iframe) }, 500);
            }
            else {
              preview(iframe);
            };
        });

        editor.on('edit', function() {
            // inform the user he is in preview mode
            document.getElementById('epiceditor-status').innerHTML = '(editing)';
        });
        
      </script>

      <?php print_captcha(); ?>
  
      <input type="hidden" name="tag" value="<?php print($tag); ?>">
  
      <input type="submit" id="comment-submit" value="Post comment">
    </form>
<?php
  }

  function print_comment($comment) {
    print("    <div class='comment' id='comment-" . $comment['id'] . "'>\n");
    $date = date_create($comment['date'], timezone_open('GMT'));
    print("      <a href='#comment-" . $comment['id'] . "'>Comment #" . $comment['id'] . "</a> by <cite class='comment-author'>" . htmlspecialchars($comment['author']) . "</cite> ");
    if (!empty($comment['site'])) {
      print(" (<a href='" . htmlspecialchars($comment['site']) . "'>site</a>)\n");
    }
    print("on " . date_format($date, 'F j, Y \a\t g:i a e') . "\n");
    print("      <blockquote>" . str_replace("\xA0", ' ', Markdown(htmlspecialchars($comment['comment']))) . "</blockquote>\n");
    print("    </div>\n\n");
  }

  function print_comments($tag) {
    print("    <h2>Comments</h2>\n");

    $comments = get_comments($tag);
    if (count($comments) == 0) {
      print("    <p>There are no comments yet for this tag.</p>\n\n");
    }
    else {
      foreach ($comments as $comment) {
        print_comment($comment);
      }
    }
  }

  function print_tag($tag) {
    $results = get_tag($tag);
    
    print("    <h2>Tag: <var>" . $tag . "</var></h2>\n");

    $parts = explode('.', $results['book_id']);
    # the identification of the result relative to the local section
    $relative_id = implode('.', array_slice($parts, 1));
    # the identification of the (sub)section of the result
    $section_id = implode('.', array_slice($parts, 0, -1));
    # the id of the chapter, the first part of the full identification
    $chapter_id = $parts[0];

    # get all information about the current section and chapter
    if (!section_exists($section_id) or !section_exists($chapter_id)) {
      print("    <p>This tag has label <var>" . $results['label'] . "</var> but there is something wrong in the database because it doesn't belong to a correct section of the project.\n");
      return;
    }
    $section_information = get_section($section_id);
    $chapter_information = get_section($chapter_id);

    if (empty($results['name'])) {
      print("    <p>This tag has label <var>" . $results['label'] . "</var> and it references\n");
    }
    else {
      print("    <p>This tag has label <var>" . $results['label'] . "</var>, it is called <strong>" . $results['name'] . "</strong> in the Stacks project and it references\n");
    }
    print("    <ul>\n");
    // the tag refers to a result in a chapter, not contained in a (sub)section, i.e. don't display that information
    if ($section_id == $chapter_id) {
      print("      <li><a href='" . full_url('tex/' . $chapter_information['filename'] . ".pdf#" . $tag) . "'>" . ucfirst($results['type']) . " " . $relative_id . " on page " . $results['chapter_page'] . "</a> of Chapter " . $chapter_id . ": " . $chapter_information['title'] . "\n");
    }
    else {
      print("      <li><a href='" . full_url('tex/' . $chapter_information['filename'] . ".pdf#" . $tag) . "'>" . ucfirst($results['type']) . " " . $relative_id . " on page " . $results['chapter_page'] . "</a> of Section " . implode('.', array_slice(explode('.', $section_id), 1)) . ": " . $section_information['title'] . ", in Chapter " . $chapter_id . ": " . $chapter_information['title'] . "\n");
    }
    print("      <li><a href='" . full_url('tex/book.pdf#' . $tag) . "'>". ucfirst($results['type']) . " " . $results['book_id'] . " on page " . $results['book_page'] . "</a> of the book version\n");
    print("    </ul>\n\n");
    if(empty($results['value'])) {
      print("    <p>There is no LaTeX code associated to this tag.\n");
    }
    else {
      print("    <p>The LaTeX code of the corresponding environment is:\n");
      print("    <pre>\n" . trim($results['value']) . "\n    </pre>\n");
    }
  }

  function print_inactive_tag($tag) {
    print("    <h2>Inactive tag: <var>" . $tag . "</var></h2>\n");
    print("    <p>The tag you requested did at some point in time belong to the Stacks project, but it was removed.\n");
  }

  function print_missing_tag($tag) {
    print("    <h2>Missing tag: <var>" . $tag . "</var></h2>\n");
    print("    <p>The tag you requested does not exist.\n");
  }
?>
<html>
  <head>
<?php
  if (isset($_GET['tag']) and is_valid_tag($_GET['tag']))
    print("    <title>Stacks Project -- Tag " . $_GET['tag'] . "</title>\n");
  else
    print("    <title>Stacks Project -- Tag lookup</title>\n");
?>
    <link rel="stylesheet" type="text/css" href="<?php print(full_url('style.css')); ?>">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <link rel="icon" type="image/vnd.microsoft.icon" href="<?php print(full_url('stacks.ico')); ?>"> 
    <meta charset="utf-8">

    <script type="text/javascript" src="http://cdn.mathjax.org/mathjax/latest/MathJax.js?config=TeX-AMS-MML_HTMLorMML"></script>
    <script type="text/x-mathjax-config">
      MathJax.Hub.Config({
        tex2jax: {inlineMath: [['$','$'], ['\\(','\\)']]}
      });
    </script>

    <script type="text/javascript" src="<?php print(full_url('EpicEditor/epiceditor/js/epiceditor.js')); ?>"></script>
    <script type="text/javascript">
      var options = {
        basePath: '<?php print(full_url('EpicEditor/epiceditor')); ?>',
        file: {
          name: '<?php print(htmlspecialchars($_GET['tag'])); ?>',
        },
        theme: {
          editor: '/themes/editor/epic-light.css',
          preview: '/themes/preview/github.css',
        },
      }
    </script>
  </head>
  <body>
    <h1><a href="<?php print(full_url('')); ?>">The Stacks Project</a></h1>

    <h2>Look for a tag</h2>

    <form action="<?php print(full_url('search.php')); ?>" method="post">
      <label>Tag: <input type="text" name="tag"></label>
      <input type="submit" value="locate">
    </form>

    <p>For more information we refer to the <a href="<?php print(full_url('tags')); ?>">tags explained</a> page.

<?php
  if (!empty($_GET['tag'])) {
    $_GET['tag'] = strtoupper($_GET['tag']);

    if (is_valid_tag($_GET['tag'])) {
      // from here on it's safe to ignore the fact it is user input
      $tag = $_GET['tag'];

      if (tag_exists($tag)) {
        if (tag_is_active($tag)) {
          print_tag($tag);
          print_comments($tag);

          print_comment_input($tag);
        }
        else {
          print_inactive_tag($tag);
        }
      }
      else {
        print_missing_tag($tag);
      }
    }
    else {
      print("    <h2>Error</h2>\n");
      print("    The tag you provided (i.e. <var>" . htmlspecialchars($_GET['tag']) . "</var>) is not in the correct format. See <a href=\"" . full_url('tags') . "\">tags explained</a> for an overview of the tag system and a description of the format. A summary: four characters, either digits or capital letters, e.g. <var>03DF</var>.\n");
    }
  }
?>
    <p id="backlink">Back to the <a href="<?php print(full_url('')); ?>">main page</a>.
  </body>
</html>
