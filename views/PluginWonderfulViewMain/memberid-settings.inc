<?php if (is_admin()) { ?>
  <form id="pw-handler" action="" method="post">
    <input type="hidden" name="_pw_nonce" value="<?php echo $this->_pw_nonce ?>" />
    <input type="hidden" name="_pw_action" value="change-memberid" />
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
                   <?php echo (get_option("plugin-wonderful-use-standardcode") == 1) ? "checked='checked'" : "" ?> />
            <em><?php _e('(If you want to use standard code adboxes instead of advanced code, enable this option)', 'plugin-wonderful') ?></em>
          </label>
        </td>
      </tr>
      <tr>
        <th scope="row"><?php _e('Enable Body Copy Embedding?', 'plugin-wonderful') ?></th>
        <td>
          <label>
            <input type="checkbox"
                   name="enable-body-copy-embedding"
                   value="yes"
                   <?php echo (get_option("plugin-wonderful-enable-body-copy-embedding") == 1) ? "checked='checked'" : "" ?> />
            <em><?php _e('(When enabled, you can embed ads directly in body copy using a PW() tag. Read below for more details.)', 'plugin-wonderful') ?></em>
          </label>
        </td>
      </tr>    <tr>
        <td>&nbsp;</td>
        <td>
          <input type="submit" value="<?php _e('Change and Redownload Adbox Information', 'plugin-wonderful') ?>" class="button" /> <em>(<?php _e('if you\'ve modified adbox settings on Project Wonderful, just click this button to refresh your adbox code.', 'plugin-wonderful') ?>)</em>
        </td>
      </tr>
    </table>
  </form>
<?php } ?>