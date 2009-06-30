<?php if ($plugin_wonderful->publisher_info !== false) { ?>
  <h3><?php _e('Using Widgets to Put Ads on Your Site', 'plugin-wonderful') ?></h3>
  <p>
    <?php _e('Visit <a href="widgets.php">Appearance -> Widgets</a> to quickly add Project Wonderful advertisements to your site. Having multiple widgets on your site only works in WordPress 2.8 and above.', 'plugin-wonderful') ?>
  </p>

  <h3><?php _e('Using the Template Tags in Your Theme', 'plugin-wonderful') ?></h3>
  <p>
    <?php _e('Find the location in your theme where you want the ad to appear. Type in the template tag for that ad, surrounded in PHP tags, like this:', 'plugin-wonderful') ?>
  </p>

  <tt>
    &lt;?php the_project_wonderful_ad(<?php echo $this->first_adboxid ?>) ?&gt;
  </tt>

  <h3><?php _e('Embedding Ads Directly In Body Copy', 'plugin-wonderful') ?></h3>
  <p>
    <?php _e('In your blog post, use a PW() tag with either the adbox ID or the template tag name to embed the adbox directly in your entry:', 'plugin-wonderful') ?>
  </p>

  <tt>PW(<?php echo $this->first_adboxid ?>)</tt>
  
  <p><?php _e('If you need to display the PW() code in your post, escape the parenthesis:', 'plugin-wonderful') ?></p>
  
  <tt>PW\(<?php echo $this->first_adboxid ?>\)</tt>

  <h3><?php _e('Inserting Ads Into Your RSS Feeds <em>(experimental)</em>', 'plugin-wonderful') ?></h3>
  <p>
    <?php _e('You can insert your Project Wonderful ads into you RSS feeds. The ads you insert into your feed also need to be crawlable by the Project Wonderful ad checking robot, so it\'s recommended that you put ads into your RSS feed that you\'re already showing on your site. Not all RSS feed readers support displaying the embedded ads.', 'plugin-wonderful') ?>
  </p>

  <?php if (isset($_GET['allow-destroy'])) { ?>
    <h3><?php _e('Rebuilding Your Project Wonderful Ad Database', 'plugin-wonderful') ?></h3>
    <p>
      <?php _e('If you are having issues with your ads not downloading correctly from Project Wonderful, click this button to destroy and rebuild the database that stores ad info.', 'plugin-wonderful') ?>
    </p>

    <form id="allow-destroy-handler" action="" method="post">
      <input type="hidden" name="_pw_nonce" value="<?php echo $this->_pw_nonce ?>" />
      <input type="hidden" name="action" value="rebuild-database" />
      <input type="submit" value="<?php _e('Destroy and Rebuild Database', 'plugin-wonderful') ?>" class="button" />
    </form>
  <?php } ?>
<?php } ?>
