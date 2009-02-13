<form id="pw-handler" action="" method="post">
  <input type="hidden" name="action" value="change-memberid" />
  <table class="form-table">
    <tr>
      <th scope="row"><?php _e('Your member number', 'plugin-wonderful') ?></th>
      <td>
        <input id="memberid" name="memberid" value="<?php echo get_option("plugin-wonderful-memberid") ?>" />
        <em><?php _e('(you can find your member number by logging in to Project Wonderful and clicking on your profile image in the upper right of the page)', 'plugin-wonderful') ?></em>
      </td>
    </tr>
    <tr>
      <th scope="row"><?php _e('Use Standard Adboxes?', 'plugin-wonderful') ?></th>
      <td>
        <label>
          <input type="checkbox"
                 name="use-standardcode"
                 value="yes"
                 <?php echo (get_option("plugin-wonderful-use-standardcode") == 1) ? "checked" : "" ?> />
          <em><?php _e('(If you want to use standard code adboxes instead of advanced code, enable this option)', 'plugin-wonderful') ?></em>
        </label>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>
        <input type="submit" value="<?php _e('Change', 'plugin-wonderful') ?>" class="button" />
      </td>
    </tr>
  </table>
</form>
<?php if ($this->publisher_info !== false) { ?>
  <h3><?php _e('Adbox Information', 'plugin-wonderful') ?></h3>
  <form action="" method="post">
    <input type="hidden" name="action" value="change-adbox-settings" />
    <table class="widefat post fixed">
      <tr>
        <th width="15%" class="manage-column"><?php _e('Site Name', 'plugin-wonderful') ?></th>
        <th width="15%" class="manage-column"><?php _e('Description', 'plugin-wonderful') ?></th>
        <th class="manage-column" align="center"><?php _e('Size &amp; Dimensions', 'plugin-wonderful') ?></th>
        <th class="manage-column" align="center"><?php _e('Category', 'plugin-wonderful') ?></th>
        <th class="manage-column" align="center"><?php _e('Template Tag Identifier', 'plugin-wonderful') ?></th>
        <th class="manage-column" align="center"><?php _e('Use in RSS Feed?', 'plugin-wonderful') ?></th>
        <th style="text-align: right !important" width="25%" class="manage-column"><?php _e('Raw Template Tag <em>(for direct use in theme)</em>', 'plugin-wonderful') ?></th>
      </tr>
      <?php
        $first_adboxid = null;
        foreach ($this->publisher_info->adboxes as $adbox) {
          if (empty($first_adboxid)) {
            if (!empty($adbox->template_tag_id)) {
              $first_adboxid =  "'" . $adbox->template_tag_id . "'";
            } else {
              $first_adboxid = $adbox->adboxid;
            }
          } ?>
          <tr>
            <td><a href="<?php echo $adbox->url ?>" target="_top" title="<?php printf(__('Ad for use on %s (opens in new window)', 'plugin-wonderful'), $adbox->url) ?>"><?php echo $adbox->sitename ?></a></td>
            <td>
              <?php
                if (strlen($adbox->description) > 70) {
                  echo substr($adbox->description, 0, 70) . '...';
                } else {
                  echo $adbox->description;
                }
              ?>
            </td>
            <td><?php echo $adbox->adtype ?> - <?php echo $adbox->dimensions ?></td>
            <td><?php echo $adbox->category ?></td>
            <td><input type="text" size="8" name="template_tag_id[<?php echo $adbox->adboxid ?>]" value="<?= $adbox->template_tag_id ?>" /></td>
            <td align="center"><input type="checkbox" name="in_rss_feed[<?php echo $adbox->adboxid ?>]" value="yes" <?php echo !empty($adbox->in_rss_feed) ? " checked" : "" ?> /></td>
            <td align="right">
              <tt>the_project_wonderful_ad(<?php
                                             if (!empty($adbox->template_tag_id)) {
                                               echo "'" . $adbox->template_tag_id . "'";
                                             } else {
                                               echo $adbox->adboxid;
                                             }
                                           ?>)</tt>
            </td>
          </tr>
          <?php
        }
      ?>
    </table>
    <div style="text-align: center">
      <input type="submit" class="button" value="<?php _e('Submit Adbox Changes', 'plugin-wonderful') ?>" />
    </div>
  </form>

  <h3><?php _e('Using Widgets to Put Ads on Your Site', 'plugin-wonderful') ?></h3>
  <p>
    <?php _e('Visit <a href="widgets.php">Appearance -> Widgets</a> to quickly add Project Wonderful advertisements to your site. Plugin Wonderful widgets start with &quot;PW&quot;.', 'plugin-wonderful') ?>
  </p>

  <h3><?php _e('Using the Template Tags in Your Theme', 'plugin-wonderful') ?></h3>
  <p>
    <?php _e('Find the location in your theme where you want the ad to appear. Type in the template tag for that ad, surrounded in PHP tags, like this:', 'plugin-wonderful') ?>
  </p>

  <tt>
    &lt;?php the_project_wonderful_ad(<?php echo $first_adboxid ?>) ?&gt;
  </tt>

  <h3><?php _e('Inserting Ads Into Your RSS Feeds <em>(experimental)</em>', 'plugin-wonderful') ?></h3>
  <p>
    <?php _e('You can insert your Project Wonderful ads into you RSS feeds. The ads you insert into your feed also need to be crawlable by the Project Wonderful ad checking robot, so it\'s recommended that you put ads into your RSS feed that you\'re already showing on your site. Not all RSS feed readers support displaying the embedded ads.', 'plugin-wonderful') ?>
  </p>

  <?php if (isset($_GET['allow-destroy'])) { ?>
    <h3><?php _e('Rebuilding Your Project Wonderful Ad Database', 'plugin-wonderful') ?></h3>
    <p>
      <?php _e('If you are having issues with your ads not downloading correctly from Project Wonderful, click this button to destroy and rebuild the database that stores ad info.', 'plugin-wonderful') ?>
    </p>

    <form id="pw-handler" action="" method="post">
      <input type="hidden" name="action" value="rebuild-database" />
      <input type="submit" value="<?php _e('Destroy and Rebuild Database', 'plugin-wonderful') ?>" class="button" />
    </form>
  <?php } ?>
<?php } ?>