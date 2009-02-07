<form id="pw-handler" action="" method="post">
  <input type="hidden" name="action" value="change-memberid" />
  <table class="form-table">
    <tr>
      <th scope="row">Your advertiser number</th>
      <td>
        <input id="memberid" name="memberid" value="<?php echo get_option("plugin-wonderful-memberid") ?>" />
      </td>
    </tr>
    <tr>
      <th scope="row">Use Standard Adboxes?</th>
      <td>
        <label>
          <input type="checkbox"
                 name="use-standardcode"
                 value="yes"
                 <?php echo (get_option("plugin-wonderful-use-standardcode") == 1) ? "checked" : "" ?> />
          <em>(If you want to use standard code adboxes instead of advanced code, enable this option)</em>
        </label>
      </td>
    </tr>
    <tr>
      <td>&nbsp;</td>
      <td>
        <input type="submit" value="Change" class="button" />
      </td>
    </tr>
  </table>
</form>
<?php if ($this->publisher_info !== false) { ?>
  <h3>Adbox Information</h3>
  <table class="widefat post fixed">
    <tr>
      <th width="20%" class="manage-column">Site Name</th>
      <th width="30%" class="manage-column">Description</th>
      <th class="manage-column" align="center">Dimensions</th>
      <th class="manage-column" align="center">Category</th>
      <th style="text-align: right !important" width="25%" class="manage-column">Template Tag <em>(for direct use in theme)</em></th>
    </tr>
    <?php
      $first_adboxid = null;
      foreach ($this->publisher_info->adboxes as $adbox) {
        $first_adboxid = $adbox->adboxid; ?>
        <tr>
          <td><a href="<?php echo $adbox->url ?>" target="_top" title="Ad for use on <?php echo $adbox->url ?> (opens in new window)"><?php echo $adbox->sitename ?></a></td>
          <td><?php echo $adbox->description ?></td>
          <td><?php echo $adbox->dimensions ?></td>
          <td><?php echo $adbox->category ?></td>
          <td align="right"><tt>the_project_wonderful_ad(<?php echo $adbox->adboxid ?>)</tt></td>
        </tr>
        <?php
      }
    ?>
  </table>

  <h3>Using the Template Tags in Your Theme</h3>
  <p>
    Find the location in your theme where you want the ad to appear. Type in the template tag, surrounded in PHP tags, like this:
  </p>

  <tt>
    &lt;?php the_project_wonderful_ad(<?php echo $first_adboxid ?>) ?&gt;
  </tt>
<?php } ?>