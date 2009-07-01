<?php if (is_admin()) { ?>
  <?php if (!empty($plugin_wonderful->publisher_info)) { ?>
    <h3><?php _e('Adbox Information', 'plugin-wonderful') ?></h3>
    <form action="" method="post">
      <input type="hidden" name="_pw_nonce" value="<?php echo $this->_pw_nonce ?>" />
      <input type="hidden" name="_pw_action" value="change-adbox-settings" />
      <table class="widefat post fixed">
        <tr>
          <th width="12%" class="manage-column"><?php _e('Site Name', 'plugin-wonderful') ?></th>
          <th width="13%" class="manage-column"><?php _e('Description', 'plugin-wonderful') ?></th>
          <th class="manage-column" align="center"><?php _e('Size &amp; Dimensions', 'plugin-wonderful') ?></th>
          <th width="100" class="manage-column" align="center"><?php _e('Template Tag Identifier', 'plugin-wonderful') ?></th>
          <th class="manage-column" align="center"><?php _e('Use in RSS Feed?', 'plugin-wonderful') ?></th>
          <th style="text-align: right !important" width="35%" class="manage-column"><?php _e('Raw Template Tag <em>(for direct use in theme)</em>', 'plugin-wonderful') ?></th>
        </tr>
        <?php
          foreach ($plugin_wonderful->publisher_info->adboxes as $adbox) { ?>
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
              <td><input type="text" style="width: 100px" name="template_tag_id[<?php echo $adbox->adboxid ?>]" value="<?php echo $adbox->template_tag_id ?>" /></td>
              <td align="center"><input type="checkbox" name="in_rss_feed[<?php echo $adbox->adboxid ?>]" value="yes" <?php echo !empty($adbox->in_rss_feed) ? " checked='checked'" : "" ?> /></td>
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
  <?php } ?>
<?php } ?>