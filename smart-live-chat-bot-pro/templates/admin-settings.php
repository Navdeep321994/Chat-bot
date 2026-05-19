<div class="wrap">
    <h1>Smart Live Chat Bot Pro - Settings</h1>
    <form method="post" action="options.php">
        <?php
        settings_fields( 'slcbp_option_group' );
        do_settings_sections( 'slcbp_option_group' );
        ?>
        <table class="form-table">
            <tr valign="top">
                <th scope="row">OpenAI API Key</th>
                <td><input type="text" name="slcbp_openai_api_key" value="<?php echo esc_attr( get_option('slcbp_openai_api_key') ); ?>" class="regular-text" /></td>
            </tr>
            <tr valign="top">
                <th scope="row">OpenAI Model</th>
                <td>
                    <select name="slcbp_openai_model">
                        <option value="gpt-4o-mini" <?php selected(get_option('slcbp_openai_model'), 'gpt-4o-mini'); ?>>GPT-4o Mini</option>
                        <option value="gpt-4o" <?php selected(get_option('slcbp_openai_model'), 'gpt-4o'); ?>>GPT-4o</option>
                        <option value="gpt-4" <?php selected(get_option('slcbp_openai_model'), 'gpt-4'); ?>>GPT-4</option>
                    </select>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">System Prompt</th>
                <td><textarea name="slcbp_system_prompt" rows="5" class="large-text"><?php echo esc_textarea( get_option('slcbp_system_prompt') ); ?></textarea></td>
            </tr>
        </table>
        <?php submit_button(); ?>
    </form>
</div>
